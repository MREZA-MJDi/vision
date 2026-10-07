<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class AdminSiteContentController extends Controller
{
    public function about(): View
    {
        $defaults = $this->aboutDefaults();

        $about = collect($defaults)
            ->mapWithKeys(
                fn ($default, $key) => [$key => SiteSetting::getValue("about.$key", $default)]
            )
            ->all();

        return view('admin.content.about', compact('about'));
    }

    public function updateAbout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hero_title' => ['required', 'string', 'max:180'],
            'hero_description' => ['required', 'string', 'max:1000'],
            'story_title' => ['required', 'string', 'max:240'],
            'story_text_1' => ['required', 'string', 'max:1500'],
            'story_text_2' => ['nullable', 'string', 'max:1500'],
            'principle_1_title' => ['required', 'string', 'max:100'],
            'principle_1_text' => ['required', 'string', 'max:700'],
            'principle_2_title' => ['required', 'string', 'max:100'],
            'principle_2_text' => ['required', 'string', 'max:700'],
            'principle_3_title' => ['required', 'string', 'max:100'],
            'principle_3_text' => ['required', 'string', 'max:700'],
            'cta_title' => ['required', 'string', 'max:140'],
            'cta_text' => ['required', 'string', 'max:500'],
        ]);

        try {
            foreach ($data as $key => $value) {
                SiteSetting::setValue("about.$key", trim($value));
            }

            return back()->with('success', 'محتوای درباره ما با موفقیت ذخیره شد.');
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'ذخیره محتوای درباره ما انجام نشد.');
        }
    }

    public function contact(Request $request): View
    {
        $messages = ContactMessage::query()
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = trim((string) $request->input('q'));

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $contact = [
            'phone' => SiteSetting::getValue('contact.phone', env('JANAN_STORE_PHONE')),
            'email' => SiteSetting::getValue('contact.email', env('JANAN_STORE_EMAIL')),
            'address' => SiteSetting::getValue('contact.address', env('JANAN_STORE_ADDRESS')),
            'working_hours' => SiteSetting::getValue('contact.working_hours', env('JANAN_STORE_WORKING_HOURS')),
        ];

        return view('admin.contact.index', compact('messages', 'contact'));
    }

    public function updateContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'working_hours' => ['nullable', 'string', 'max:180'],
        ]);

        try {
            foreach ($data as $key => $value) {
                SiteSetting::setValue("contact.$key", trim((string) $value));
            }

            return back()->with('success', 'اطلاعات تماس فروشگاه ذخیره شد.');
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'ذخیره اطلاعات تماس انجام نشد.');
        }
    }

    public function showContact(ContactMessage $message): View
    {
        if ($message->status === ContactMessage::STATUS_NEW) {
            $message->markAsRead();
        }

        return view('admin.contact.show', compact('message'));
    }

    public function updateContactStatus(Request $request, ContactMessage $message): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,read,replied'],
        ]);

        try {
            match ($data['status']) {
                ContactMessage::STATUS_REPLIED => $message->markAsReplied(),
                ContactMessage::STATUS_READ => $message->markAsRead(),
                default => $message->forceFill([
                    'status' => ContactMessage::STATUS_NEW,
                    'read_at' => null,
                    'replied_at' => null,
                ])->save(),
            };

            return back()->with('success', 'وضعیت پیام به‌روزرسانی شد.');
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'تغییر وضعیت پیام انجام نشد.');
        }
    }

    private function aboutDefaults(): array
    {
        return [
            "hero_title" => "انتخاب خوب،
از شناخت شروع می‌شود.",
            'hero_description' => 'جانان یک فروشگاه آنلاین برای انتخاب آگاهانه‌تر است؛ محصول را واضح می‌بینی، اطلاعاتش را مقایسه می‌کنی و بدون پیچیدگی به خرید می‌رسی.',
            'story_title' => 'قرار نیست برای پیدا کردن یک محصول خوب، بین صفحه‌های شلوغ گم شوی.',
            'story_text_1' => 'جانان با یک ایده ساده ساخته شده: تجربه خرید باید سریع، قابل فهم و قابل اعتماد باشد. برای همین ساختار فروشگاه حول سه چیز می‌چرخد؛ ارائه روشن اطلاعات، مسیر ساده انتخاب و اتصال مستقیم به داده‌های واقعی فروشگاه.',
            'story_text_2' => 'از محصول و دسته‌بندی تا برند، سبد خرید و پرداخت، هر بخش بخشی از یک مسیر واحد است؛ نه چند صفحه جدا از هم.',
            'principle_1_title' => 'شفافیت',
            'principle_1_text' => 'نام، قیمت، موجودی، مشخصات و مسیر خرید باید همان‌جایی دیده شوند که کاربر به آن‌ها نیاز دارد.',
            'principle_2_title' => 'سادگی',
            'principle_2_text' => 'کم کردن مراحل اضافه، پیدا کردن محصول را سریع‌تر می‌کند و تصمیم‌گیری را سبک‌تر نگه می‌دارد.',
            'principle_3_title' => 'جزئیات',
            'principle_3_text' => 'فاصله‌ها، تایپوگرافی، حالت‌های تعاملی و بازخوردهای کوچک بخشی از خود محصول دیجیتال هستند.',
            'cta_title' => 'از کشف شروع کن.',
            'cta_text' => 'محصولی که دنبالش هستی را پیدا کن یا مستقیم با تیم جانان در ارتباط باش.',
        ];
    }
}
