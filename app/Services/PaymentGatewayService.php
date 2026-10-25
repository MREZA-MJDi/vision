<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class PaymentGatewayService
{
    private const GATEWAYS = [
        'snappay' => [
            'label' => 'اسنپ‌پی',
            'description' => 'پرداخت اعتباری از طریق صفحهٔ رسمی اسنپ‌پی',
            'domain' => 'snapppay.ir',
        ],
        'torobpay' => [
            'label' => 'ترب‌پی',
            'description' => 'پرداخت اعتباری از طریق صفحهٔ رسمی ترب‌پی',
            'domain' => 'torobpay.com',
        ],
        'digipay' => [
            'label' => 'دیجی‌پی',
            'description' => 'پرداخت از طریق درگاه یکپارچهٔ دیجی‌پی',
            'domain' => 'mydigipay.com',
        ],
    ];

    /**
     * Only show a gateway when it is explicitly enabled and has the necessary
     * merchant credentials. Credentials belong in environment secrets, never
     * in Blade, JavaScript, or a committed source file.
     */
    public function availableGateways(): array
    {
        $available = [];

        foreach (self::GATEWAYS as $name => $definition) {
            $config = config("services.payment_gateways.{$name}", []);
            if (!(bool) ($config['enabled'] ?? false)) {
                continue;
            }

            foreach (['client_id', 'client_secret', 'username', 'password'] as $field) {
                if (!is_string($config[$field] ?? null) || trim($config[$field]) === '') {
                    continue 2;
                }
            }

            if ($name === 'digipay'
                && in_array((int) ($config['ticket_type'] ?? 11), [5, 13], true)
                && collect(['seller_id', 'supplier_id', 'category_id'])
                    ->contains(fn (string $field) => trim((string) ($config[$field] ?? '')) === '')) {
                continue;
            }

            $available[$name] = [
                'value' => $name,
                'label' => $definition['label'],
                'description' => $definition['description'],
            ];
        }

        return $available;
    }

    public function isAvailable(string $gateway): bool
    {
        return array_key_exists($gateway, $this->availableGateways());
    }

    /**
     * Create the provider-side payment and return its hosted checkout URL.
     * Order amounts are stored/displayed in Toman; provider APIs use Rial.
     */
    public function initiate(Order $order, string $gateway): string
    {
        if (!$this->isAvailable($gateway)) {
            throw new RuntimeException('این روش پرداخت هنوز برای فروشگاه فعال نشده است.');
        }

        $order->loadMissing(['items']);
        $payment = $order->payments()->latest('id')->firstOrFail();
        if ($payment->gateway !== $gateway || $payment->status !== 'pending') {
            throw new RuntimeException('وضعیت پرداخت سفارش معتبر نیست.');
        }

        $redirect = match ($gateway) {
            'snappay' => $this->initiateSnappPay($order),
            'torobpay' => $this->initiateTorobPay($order),
            'digipay' => $this->initiateDigiPay($order),
            default => throw new RuntimeException('درگاه پرداخت پشتیبانی نمی‌شود.'),
        };

        $this->assertSafeRedirect($gateway, $redirect['url']);

        $payment->forceFill([
            'authority' => $redirect['token'],
            'transaction_id' => $redirect['transaction_id'] ?? $order->order_number,
            'metadata' => array_merge((array) $payment->metadata, $redirect['metadata'] ?? []),
        ])->save();

        return $redirect['url'];
    }

    /**
     * Verify with the provider server-to-server. Browser redirects alone never
     * mark an order paid. Returns the verified reference and transaction ids.
     */
    public function verifyCallback(
        string $gateway,
        Order $order,
        Payment $payment,
        Request $request
    ): array {
        if (!isset(self::GATEWAYS[$gateway]) || $payment->gateway !== $gateway) {
            throw new RuntimeException('درگاه پرداخت با سفارش مطابقت ندارد.');
        }

        if ($payment->status === 'paid' && $order->payment_status === 'paid') {
            return [
                'transaction_id' => $payment->transaction_id,
                'reference_number' => $payment->reference_number,
                'ticket_type' => data_get($payment->metadata, 'ticket_type'),
            ];
        }

        if ($payment->status !== 'pending' || $order->payment_status !== 'pending') {
            throw new RuntimeException('این پرداخت دیگر در انتظار تأیید نیست.');
        }

        return match ($gateway) {
            'snappay' => $this->verifySnappPay($order, $payment, $request),
            'torobpay' => $this->verifyTorobPay($order, $payment, $request),
            'digipay' => $this->verifyDigiPay($order, $payment, $request),
            default => throw new RuntimeException('درگاه پرداخت پشتیبانی نمی‌شود.'),
        };
    }

    private function initiateSnappPay(Order $order): array
    {
        $config = config('services.payment_gateways.snappay');
        $accessToken = $this->oauthToken('snappay');
        $payload = [
            'amount' => $this->rial($order->total),
            'mobile' => $this->internationalPhone($order->customer_phone),
            'paymentMethodTypeDto' => 'INSTALLMENT',
            'transactionId' => $order->order_number,
            'returnURL' => $this->callbackUrl('snappay', $order),
            'discountAmount' => $this->rial($order->discount),
            'externalSourceAmount' => 0,
            'cartList' => [[
                'cartId' => (int) $order->getKey(),
                'totalAmount' => $this->rial($order->total),
                'shippingAmount' => $this->rial($order->shipping_cost),
                'taxAmount' => $this->rial($order->tax),
                'isTaxIncluded' => true,
                'isShipmentIncluded' => true,
                'cartItems' => $this->providerCartItems($order),
            ]],
        ];

        $response = Http::timeout(15)->withToken($accessToken)->acceptJson()
            ->post($this->baseUrl('snappay') . '/api/online/payment/v1/token', $payload);
        $data = $response->json();

        if (!$response->successful()
            || !is_array($data)
            || ($data['successful'] ?? false) !== true
            || !is_string(data_get($data, 'response.paymentToken'))
            || !is_string(data_get($data, 'response.paymentPageUrl'))) {
            throw new RuntimeException('اسنپ‌پی نتوانست درخواست پرداخت را ایجاد کند.');
        }

        return [
            'url' => data_get($data, 'response.paymentPageUrl'),
            'token' => data_get($data, 'response.paymentToken'),
            'transaction_id' => $order->order_number,
        ];
    }

    private function initiateTorobPay(Order $order): array
    {
        $accessToken = $this->oauthToken('torobpay');
        $payload = [
            'amount' => $this->rial($order->total),
            'mobile' => $this->internationalPhone($order->customer_phone),
            'paymentMethodTypeDto' => 'ONLINE_CREDIT',
            'transactionId' => $order->order_number,
            'returnURL' => $this->callbackUrl('torobpay', $order),
            'cartList' => [[
                'cartId' => (string) $order->getKey(),
                'totalAmount' => $this->rial($order->total),
                'shipping_amount' => $this->rial($order->shipping_cost),
                'tax_amount' => $this->rial($order->tax),
                'is_tax_included' => true,
                'is_shipment_included' => true,
                'cartItems' => $this->providerCartItems($order),
            ]],
        ];

        $response = Http::timeout(15)->withToken($accessToken)->acceptJson()
            ->post($this->baseUrl('torobpay') . '/api/online/payment/v1/token', $payload);
        $data = $response->json();

        if (!$response->successful()
            || !is_array($data)
            || ($data['successful'] ?? false) !== true
            || !is_string(data_get($data, 'response.paymentToken'))
            || !is_string(data_get($data, 'response.paymentPageUrl'))) {
            throw new RuntimeException('ترب‌پی نتوانست درخواست پرداخت را ایجاد کند.');
        }

        return [
            'url' => data_get($data, 'response.paymentPageUrl'),
            'token' => data_get($data, 'response.paymentToken'),
            'transaction_id' => $order->order_number,
        ];
    }

    private function initiateDigiPay(Order $order): array
    {
        $config = config('services.payment_gateways.digipay');
        $accessToken = $this->oauthToken('digipay');
        $ticketType = (int) ($config['ticket_type'] ?? 11);
        $payload = [
            'amount' => $this->rial($order->total),
            'cellNumber' => $this->localPhone($order->customer_phone),
            'providerId' => $order->order_number,
            'callbackUrl' => $this->callbackUrl('digipay', $order),
        ];

        if (in_array($ticketType, [5, 13], true)) {
            $payload['basketDetailsDto'] = [
                'basketId' => $order->order_number,
                'items' => $order->items->map(fn ($item) => [
                    'sellerId' => (string) $config['seller_id'],
                    'supplierId' => (string) $config['supplier_id'],
                    'productCode' => (string) ($item->sku ?: $item->product_variant_id),
                    'brand' => 'VISION',
                    'productType' => 1,
                    'count' => (int) $item->quantity,
                    'categoryId' => (string) $config['category_id'],
                ])->values()->all(),
            ];
        }

        $response = Http::timeout(15)->withToken($accessToken)
            ->withHeaders([
                'Agent' => 'WEB',
                'Digipay-Version' => '2022-02-02',
            ])->acceptJson()
            ->post($this->baseUrl('digipay') . '/digipay/api/tickets/business?type=' . $ticketType, $payload);
        $data = $response->json();

        if (!$response->successful()
            || !is_array($data)
            || (string) data_get($data, 'result.status', '') !== '0'
            || !is_string($data['ticket'] ?? null)
            || !is_string($data['redirectUrl'] ?? null)) {
            throw new RuntimeException('دیجی‌پی نتوانست درخواست پرداخت را ایجاد کند.');
        }

        return [
            'url' => $data['redirectUrl'],
            'token' => $data['ticket'],
            'transaction_id' => $order->order_number,
            'metadata' => ['ticket_type' => $ticketType],
        ];
    }

    private function verifySnappPay(Order $order, Payment $payment, Request $request): array
    {
        $this->assertCallbackTokenMatches($payment, $request, ['paymentToken', 'payment_token', 'token']);
        $token = (string) $payment->authority;
        if ($token === '') {
            throw new RuntimeException('توکن پرداخت اسنپ‌پی موجود نیست.');
        }

        $response = Http::timeout(15)->withToken($this->oauthToken('snappay'))->acceptJson()
            ->post($this->baseUrl('snappay') . '/api/online/payment/v1/verify', ['paymentToken' => $token]);
        $data = $response->json();

        if (!$response->successful() || !is_array($data) || ($data['successful'] ?? false) !== true) {
            throw new RuntimeException('اسنپ‌پی پرداخت را تأیید نکرد.');
        }

        $this->assertProviderAmount($order, data_get($data, 'response.amount'), 'اسنپ‌پی');

        // SnappPay requires settlement after verification; without it the
        // provider may reverse an otherwise verified payment automatically.
        $settle = Http::timeout(15)->withToken($this->oauthToken('snappay'))->acceptJson()
            ->post($this->baseUrl('snappay') . '/api/online/payment/v1/settle', ['paymentToken' => $token]);
        $settleData = $settle->json();
        if (!$settle->successful() || !is_array($settleData) || ($settleData['successful'] ?? false) !== true) {
            throw new RuntimeException('پرداخت اسنپ‌پی تأیید شد، اما تسویهٔ آن کامل نشد. سفارش را پشتیبانی بررسی کند.');
        }

        return [
            'transaction_id' => (string) (data_get($data, 'response.transactionId') ?: $order->order_number),
            'reference_number' => data_get($data, 'response.referenceNumber'),
        ];
    }

    private function verifyTorobPay(Order $order, Payment $payment, Request $request): array
    {
        $this->assertCallbackTokenMatches($payment, $request, ['paymentToken', 'payment_token', 'token']);
        $token = (string) $payment->authority;
        if ($token === '') {
            throw new RuntimeException('توکن پرداخت ترب‌پی موجود نیست.');
        }

        $accessToken = $this->oauthToken('torobpay');
        $response = Http::timeout(15)->withToken($accessToken)->acceptJson()
            ->post($this->baseUrl('torobpay') . '/api/online/payment/v1/verify', ['paymentToken' => $token]);
        $data = $response->json();

        if (!$response->successful() || !is_array($data) || ($data['successful'] ?? false) !== true) {
            throw new RuntimeException('ترب‌پی پرداخت را تأیید نکرد.');
        }

        $this->assertProviderAmount($order, data_get($data, 'response.amount'), 'ترب‌پی');

        if ((bool) config('services.payment_gateways.torobpay.auto_settle', false)) {
            $settle = Http::timeout(15)->withToken($accessToken)->acceptJson()
                ->post($this->baseUrl('torobpay') . '/api/online/payment/v1/settle', ['paymentToken' => $token]);
            $settleData = $settle->json();
            if (!$settle->successful() || !is_array($settleData) || ($settleData['successful'] ?? false) !== true) {
                throw new RuntimeException('تأیید پرداخت ترب‌پی انجام شد، اما تسویه به نتیجه نرسید.');
            }
        }

        return [
            'transaction_id' => (string) (data_get($data, 'response.transactionId') ?: $order->order_number),
            'reference_number' => data_get($data, 'response.referenceNumber'),
        ];
    }

    private function verifyDigiPay(Order $order, Payment $payment, Request $request): array
    {
        $providerId = trim((string) $request->input('providerId', ''));
        $trackingCode = trim((string) $request->input('trackingCode', ''));
        $result = strtoupper(trim((string) $request->input('result', '')));
        $callbackAmount = $request->input('amount');

        if ($providerId === '' || !hash_equals((string) $order->order_number, $providerId)) {
            throw new RuntimeException('شناسهٔ سفارش برگشتی از دیجی‌پی معتبر نیست.');
        }
        if ($result !== 'SUCCESS' || $trackingCode === '') {
            throw new RuntimeException('دیجی‌پی پرداخت را موفق اعلام نکرد.');
        }
        $this->assertProviderAmount($order, $callbackAmount, 'دیجی‌پی');

        $ticketType = filter_var($request->input('type'), FILTER_VALIDATE_INT);
        if ($ticketType === false || $ticketType < 0) {
            $ticketType = (int) data_get($payment->metadata, 'ticket_type', config('services.payment_gateways.digipay.ticket_type', 11));
        }

        $accessToken = $this->oauthToken('digipay');
        $url = $this->baseUrl('digipay') . '/digipay/api/purchases/verify/' . rawurlencode($trackingCode) . '?type=' . $ticketType;
        $response = Http::timeout(15)->withToken($accessToken)
            ->withHeaders([
                'Agent' => 'WEB',
                'Digipay-Version' => '2022-02-02',
            ])->acceptJson()->post($url);
        $data = $response->json();

        if (!$response->successful()
            || !is_array($data)
            || (string) data_get($data, 'result.status', '') !== '0') {
            throw new RuntimeException('دیجی‌پی پرداخت را در تأیید نهایی تأیید نکرد.');
        }

        if (data_get($data, 'amount') !== null) {
            $this->assertProviderAmount($order, data_get($data, 'amount'), 'دیجی‌پی');
        }
        $reference = (string) (data_get($data, 'rrn') ?: $request->input('rrn', ''));

        $payment->forceFill([
            'metadata' => array_merge((array) $payment->metadata, ['ticket_type' => $ticketType]),
        ])->save();

        return [
            'transaction_id' => (string) (data_get($data, 'trackingCode') ?: $trackingCode),
            'reference_number' => $reference !== '' ? $reference : null,
            'ticket_type' => $ticketType,
        ];
    }

    private function oauthToken(string $gateway): string
    {
        $config = config("services.payment_gateways.{$gateway}");
        $base = $this->baseUrl($gateway);
        $url = match ($gateway) {
            'snappay', 'torobpay' => $base . '/api/online/v1/oauth/token',
            'digipay' => $base . '/digipay/api/oauth/token',
            default => throw new RuntimeException('درگاه پرداخت پشتیبانی نمی‌شود.'),
        };

        $form = [
            'grant_type' => 'password',
            'username' => $config['username'],
            'password' => $config['password'],
        ];
        if ($gateway === 'snappay') {
            $form['scope'] = 'online-merchant';
        }

        $request = Http::timeout(12)->withBasicAuth($config['client_id'], $config['client_secret'])
            ->acceptJson();
        $response = $gateway === 'digipay'
            ? $request->asMultipart()->post($url, $form)
            : $request->asForm()->post($url, $form);
        $data = $response->json();

        if (!$response->successful() || !is_array($data) || !is_string($data['access_token'] ?? null) || $data['access_token'] === '') {
            throw new RuntimeException('احراز هویت فروشگاه نزد درگاه پرداخت ناموفق بود.');
        }

        return $data['access_token'];
    }

    private function providerCartItems(Order $order): array
    {
        return $order->items->map(fn ($item) => [
            'id' => (string) ($item->sku ?: $item->product_variant_id ?: $item->product_id),
            'name' => trim((string) $item->product_name . ' ' . (string) $item->variant_name),
            'count' => (int) $item->quantity,
            'amount' => $this->rial($item->unit_price),
            'category' => 'OTHER',
        ])->values()->all();
    }

    private function assertProviderAmount(Order $order, mixed $reportedAmount, string $gatewayLabel): void
    {
        if (!is_numeric($reportedAmount)) {
            throw new RuntimeException("مبلغ تأییدشده از {$gatewayLabel} دریافت نشد.");
        }

        $expected = $this->rial($order->total);
        if ((int) round((float) $reportedAmount) !== $expected) {
            throw new RuntimeException("مبلغ تأییدشدهٔ {$gatewayLabel} با مبلغ سفارش یکسان نیست.");
        }
    }

    private function assertCallbackTokenMatches(Payment $payment, Request $request, array $keys): void
    {
        foreach ($keys as $key) {
            $candidate = trim((string) $request->input($key, ''));
            if ($candidate !== '') {
                if (!is_string($payment->authority) || !hash_equals($payment->authority, $candidate)) {
                    throw new RuntimeException('توکن برگشتی از درگاه با پرداخت سفارش مطابقت ندارد.');
                }
                return;
            }
        }
        // Some providers return to the merchant without a token in the URL.
        // The saved token is still verified server-to-server before marking paid.
    }

    private function callbackUrl(string $gateway, Order $order): string
    {
        return route('payments.callback', ['gateway' => $gateway, 'token' => $order->public_token], true);
    }

    private function baseUrl(string $gateway): string
    {
        $url = rtrim((string) config("services.payment_gateways.{$gateway}.base_url", ''), '/');
        if ($gateway === 'digipay'
            && (bool) config('services.payment_gateways.digipay.sandbox', false)
            && $url === 'https://api.mydigipay.com') {
            return 'https://uat.mydigipay.info';
        }
        if (!str_starts_with($url, 'https://')) {
            throw new RuntimeException('نشانی درگاه پرداخت باید HTTPS باشد.');
        }
        return $url;
    }

    private function assertSafeRedirect(string $gateway, string $url): void
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if ($scheme !== 'https' || $host === '') {
            throw new RuntimeException('نشانی انتقال به صفحهٔ پرداخت معتبر نیست.');
        }

        $domains = match ($gateway) {
            'snappay' => ['snapppay.ir'],
            'torobpay' => ['torobpay.com'],
            'digipay' => ['mydigipay.com', 'mydigipay.info', 'digipay.ir'],
            default => [],
        };

        foreach ($domains as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return;
            }
        }

        throw new RuntimeException('دامنهٔ صفحهٔ پرداخت با دامنهٔ مورد انتظار درگاه مطابقت ندارد.');
    }

    private function rial(mixed $tomanAmount): int
    {
        return (int) round((float) $tomanAmount * 10);
    }

    private function localPhone(?string $phone): string
    {
        $digits = $this->asciiDigits((string) $phone);
        if (str_starts_with($digits, '0098')) $digits = '0' . substr($digits, 4);
        if (str_starts_with($digits, '98')) $digits = '0' . substr($digits, 2);
        if (str_starts_with($digits, '9') && strlen($digits) === 10) $digits = '0' . $digits;
        return $digits;
    }

    private function internationalPhone(?string $phone): string
    {
        $digits = $this->localPhone($phone);
        if (str_starts_with($digits, '0')) return '98' . substr($digits, 1);
        return $digits;
    }

    private function asciiDigits(string $value): string
    {
        $ascii = strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return preg_replace('/\D/', '', $ascii) ?? '';
    }
}
