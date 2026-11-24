            @if(session('status'))<p class="commerce-alert commerce-alert--success" role="status">{{ session('status') }}</p>@endif
            @if($errors->any())<div class="commerce-alert commerce-alert--error" role="alert"><strong>لطفاً موارد زیر را بررسی کن:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form method="POST" action="{{ route('contact.store') }}">
                @csrf
                @if($onHome ?? false)<input type="hidden" name="source" value="home">@endif
                <div class="vision-contact__fields">
                    <label class="commerce-field">نام و نام خانوادگی <input name="name" value="{{ old('name') }}" maxlength="120" required autocomplete="name"></label>
                    <label class="commerce-field">ایمیل <input name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email"></label>
                    <label class="commerce-field">شماره تماس <input name="phone" type="tel" value="{{ old('phone') }}" maxlength="30" autocomplete="tel"></label>
                    <label class="commerce-field">موضوع <input name="subject" value="{{ old('subject') }}" maxlength="180" required></label>
                </div>
                <label class="commerce-field">متن پیام <textarea name="message" rows="6" minlength="10" maxlength="5000" required>{{ old('message') }}</textarea></label>
                <p class="commerce-muted">حداقل یکی از ایمیل یا شماره تماس را وارد کن تا امکان پاسخ‌گویی وجود داشته باشد.</p>
                <button type="submit" class="commerce-button commerce-button--primary">ثبت و ارسال پیام ↗</button>
            </form>
