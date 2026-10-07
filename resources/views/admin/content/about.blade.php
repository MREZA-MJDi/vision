@extends('layouts.admin')

@section('title', 'مدیریت درباره ما')
@section('page-title', 'محتوای درباره ما')

@section('content')
<div class="admin-page-stack">

    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-head__title">درباره ما</h1>
            <p class="admin-page-head__text">
                متن‌هایی که اینجا می‌بینی دقیقاً در صفحه «درباره ما» سایت نمایش داده می‌شوند.
            </p>
        </div>

        <a href="{{ route('admin.content.about') }}" target="_blank" rel="noopener" class="admin-btn admin-btn--ghost">
            مشاهده صفحه
            <span>↗</span>
        </a>
    </div>

    <form method="POST" action="{{ route('admin.content.about.update') }}" class="admin-form-grid">
        @csrf

        <section class="admin-card admin-form-section admin-form-section-full">
            <header class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">بخش اول صفحه</h2>
                    <p class="admin-card-description">
                        عنوان بزرگ و متن معرفی ابتدای صفحه درباره ما.
                    </p>
                </div>
            </header>

            <div class="admin-form-grid">
                <div class="admin-field admin-field-full">
                    <label for="hero_title">عنوان اصلی *</label>
                    <textarea id="hero_title" name="hero_title" rows="3" required>{{ old('hero_title', $about['hero_title']) }}</textarea>
                    <small class="admin-help">
                        خط جدید را با Enter بساز؛ هر خط در عنوان صفحه به خط بعد می‌رود.
                    </small>
                </div>

                <div class="admin-field admin-field-full">
                    <label for="hero_description">متن معرفی *</label>
                    <textarea id="hero_description" name="hero_description" rows="4" required>{{ old('hero_description', $about['hero_description']) }}</textarea>
                    <small class="admin-help">
                        این متن زیر عنوان بزرگ صفحه نمایش داده می‌شود و کار اصلی آن توضیح کوتاه درباره برند است.
                    </small>
                </div>
            </div>
        </section>

        <section class="admin-card admin-form-section admin-form-section-full">
            <header class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">داستان جانان</h2>
                    <p class="admin-card-description">
                        این بخش توضیح می‌دهد جانان با چه رویکردی ساخته شده است.
                    </p>
                </div>
            </header>

            <div class="admin-form-grid">
                <div class="admin-field admin-field-full">
                    <label for="story_title">تیتر این بخش *</label>
                    <textarea id="story_title" name="story_title" rows="3" required>{{ old('story_title', $about['story_title']) }}</textarea>
                    <small class="admin-help">در سمت اصلی بخش داستان نمایش داده می‌شود.</small>
                </div>

                <div class="admin-field">
                    <label for="story_text_1">متن اول *</label>
                    <textarea id="story_text_1" name="story_text_1" rows="6" required>{{ old('story_text_1', $about['story_text_1']) }}</textarea>
                    <small class="admin-help">پاراگراف اول کنار تیتر.</small>
                </div>

                <div class="admin-field">
                    <label for="story_text_2">متن دوم</label>
                    <textarea id="story_text_2" name="story_text_2" rows="6">{{ old('story_text_2', $about['story_text_2']) }}</textarea>
                    <small class="admin-help">پاراگراف دوم برای کامل کردن داستان برند.</small>
                </div>
            </div>
        </section>

        <section class="admin-card admin-form-section admin-form-section-full">
            <header class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">سه اصل برند</h2>
                    <p class="admin-card-description">
                        هر کارت در بخش تیره صفحه با یک عنوان و توضیح کوتاه نمایش داده می‌شود.
                    </p>
                </div>
            </header>

            <div class="admin-form-grid">
                @for($i = 1; $i <= 3; $i++)
                    <div class="admin-form-section">
                        <div class="admin-field">
                            <label for="principle_{{ $i }}_title">عنوان اصل {{ $i }} *</label>
                            <input id="principle_{{ $i }}_title" name="principle_{{ $i }}_title" type="text" maxlength="100" required value="{{ old('principle_'.$i.'_title', $about['principle_'.$i.'_title']) }}">
                            <small class="admin-help">عنوان کوتاه کارت {{ $i }}.</small>
                        </div>

                        <div class="admin-field" style="margin-top:14px;">
                            <label for="principle_{{ $i }}_text">توضیح اصل {{ $i }} *</label>
                            <textarea id="principle_{{ $i }}_text" name="principle_{{ $i }}_text" rows="6" required>{{ old('principle_'.$i.'_text', $about['principle_'.$i.'_text']) }}</textarea>
                            <small class="admin-help">متن کوتاه زیر عنوان کارت {{ $i }}.</small>
                        </div>
                    </div>
                @endfor
            </div>
        </section>

        <section class="admin-card admin-form-section admin-form-section-full">
            <header class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">دعوت پایانی</h2>
                    <p class="admin-card-description">
                        آخرین پیام و دکمه‌های پایین صفحه درباره ما.
                    </p>
                </div>
            </header>

            <div class="admin-form-grid">
                <div class="admin-field">
                    <label for="cta_title">عنوان *</label>
                    <input id="cta_title" name="cta_title" type="text" maxlength="140" required value="{{ old('cta_title', $about['cta_title']) }}">
                    <small class="admin-help">عنوان بزرگ کارت پایانی.</small>
                </div>

                <div class="admin-field">
                    <label for="cta_text">توضیح *</label>
                    <textarea id="cta_text" name="cta_text" rows="4" required>{{ old('cta_text', $about['cta_text']) }}</textarea>
                    <small class="admin-help">متن کوتاه زیر عنوان کارت پایانی.</small>
                </div>
            </div>
        </section>

        <div class="admin-form-actions admin-form-section-full">
            <button type="submit" class="admin-btn admin-btn--secondary">
                ذخیره تغییرات
                <span>✓</span>
            </button>

            <a href="{{ route('admin.dashboard') }}" class="admin-btn admin-btn--ghost">بازگشت</a>
        </div>
    </form>
</div>
@endsection
