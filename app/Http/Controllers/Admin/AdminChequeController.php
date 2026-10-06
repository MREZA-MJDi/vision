<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChequePayment;
use App\Services\ChequePaymentService;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminChequeController extends Controller
{
    public function index(Request $request): View
    {
        $statusNames = [
            'submitted' => 'ثبت‌شده',
            'under_review' => 'در حال بررسی',
            'accepted' => 'تأیید شده',
            'deposited' => 'واریز شده',
            'cleared' => 'تسویه شده',
            'rejected' => 'رد شده',
            'bounced' => 'برگشتی',
            'cancelled' => 'لغو شده',
        ];

        $filters = $request->validate([
            'status' => ['nullable', 'string', 'in:' . implode(',', array_keys($statusNames))],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $cheques = ChequePayment::query()
            ->with([
                'order.user',
                'payment',
                'reviewedBy',
            ])
            ->when(! empty($filters['status']), function ($query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->when(! empty($filters['q']), function ($query) use ($filters): void {
                $term = trim($filters['q']);

                $query->where(function ($search) use ($term): void {
                    $search
                        ->where('sayad_id', 'like', "%{$term}%")
                        ->orWhere('cheque_number', 'like', "%{$term}%")
                        ->orWhere('bank_name', 'like', "%{$term}%")
                        ->orWhereHas('order.user', function ($userQuery) use ($term): void {
                            $userQuery
                                ->where('name', 'like', "%{$term}%")
                                ->orWhere('phone', 'like', "%{$term}%")
                                ->orWhere('email', 'like', "%{$term}%");
                        });
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.cheques.index', [
            'cheques' => $cheques,
            'statusNames' => $statusNames,
        ]);
    }

    public function image(ChequePayment $chequePayment): BinaryFileResponse
    {
        abort_unless(filled($chequePayment->image_path), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($chequePayment->image_path), 404);

        return response()->file($disk->path($chequePayment->image_path), [
            'Content-Type' => $disk->mimeType($chequePayment->image_path) ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function review(
        ChequePayment $chequePayment,
        Request $request,
        ChequePaymentService $cheques
    ): RedirectResponse {
        $cheques->moveToReview($chequePayment, $request->user());

        return back()->with('success', 'چک وارد مرحله بررسی شد.');
    }

    public function accept(
        ChequePayment $chequePayment,
        Request $request,
        ChequePaymentService $cheques
    ): RedirectResponse {
        $cheques->accept(
            $chequePayment,
            $request->user(),
            $this->note($request)
        );

        return back()->with('success', 'چک پذیرفته شد و سفارش تأیید شد.');
    }

    public function reject(
        ChequePayment $chequePayment,
        Request $request,
        ChequePaymentService $cheques
    ): RedirectResponse {
        $cheques->reject(
            $chequePayment,
            $request->user(),
            $this->note($request)
        );

        return back()->with('success', 'چک رد شد.');
    }

    public function deposit(
        ChequePayment $chequePayment,
        Request $request,
        ChequePaymentService $cheques
    ): RedirectResponse {
        $cheques->markDeposited(
            $chequePayment,
            $request->user()
        );

        return back()->with('success', 'واریز چک ثبت شد.');
    }

    public function clear(
        ChequePayment $chequePayment,
        Request $request,
        ChequePaymentService $cheques
    ): RedirectResponse {
        $cheques->markCleared(
            $chequePayment,
            $request->user()
        );

        return back()->with('success', 'تسویه چک ثبت شد و پرداخت نهایی شد.');
    }

    public function bounce(
        ChequePayment $chequePayment,
        Request $request,
        ChequePaymentService $cheques
    ): RedirectResponse {
        $cheques->markBounced(
            $chequePayment,
            $request->user(),
            $this->note($request)
        );

        return back()->with('success', 'برگشت چک ثبت شد.');
    }

    private function note(Request $request): ?string
    {
        return $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ])['note'] ?? null;
    }
}
