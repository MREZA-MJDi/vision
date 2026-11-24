const studio = document.querySelector('.fr-studio');

if (studio) {
    const files = new WeakMap();
    const sizeFormatter = new Intl.NumberFormat('fa-IR');

    function updateCard(assignment) {
        const option = assignment.querySelector('[data-fr-variant]').selectedOptions[0];
        const preview = assignment.querySelector('[data-fr-variant-preview]');
        preview.hidden = !option?.value;
        if (!option?.value) return;

        const image = preview.querySelector('[data-fr-card-image]');
        const newFile = files.get(assignment.querySelector('[data-fr-preview-input]'));
        const source = newFile?.valid ? newFile.url : option.dataset.image;
        image.hidden = !source;
        if (source) image.src = source;
        else image.removeAttribute('src');
        preview.querySelector('[data-fr-card-empty]').hidden = Boolean(source);
        preview.querySelector('[data-fr-card-name]').textContent = option.dataset.name;
        preview.querySelector('[data-fr-card-price]').textContent = option.dataset.price;
        preview.querySelector('[data-fr-card-stock]').textContent = option.dataset.stock;
    }

    studio.querySelectorAll('.fr-assignment').forEach(updateCard);

    studio.addEventListener('change', (event) => {
        const input = event.target;
        const assignment = input.closest('.fr-assignment');
        if (input.matches('[data-fr-variant]')) {
            updateCard(assignment);
            return;
        }
        if (!input.matches('[data-fr-preview-input]')) return;

        const container = input.closest('label');
        const image = container.querySelector('[data-fr-preview]');
        const status = container.querySelector('[data-fr-preview-status]');
        const previous = files.get(input);
        if (previous) URL.revokeObjectURL(previous.url);
        files.delete(input);
        image.hidden = true;
        image.removeAttribute('src');
        input.setCustomValidity('');
        status.textContent = '';

        const file = input.files[0];
        if (!file) {
            if (assignment) updateCard(assignment);
            return;
        }

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 8 * 1024 * 1024) {
            status.textContent = 'فایل JPG، PNG یا WebP تا ۸ مگابایت انتخاب کنید.';
            input.setCustomValidity(status.textContent);
            input.reportValidity();
            if (assignment) updateCard(assignment);
            return;
        }

        const entry = { url: URL.createObjectURL(file), valid: false };
        files.set(input, entry);
        input.setCustomValidity('در حال بررسی فایل انتخابی.');
        image.onload = () => {
            if (files.get(input) !== entry) return;
            entry.valid = Boolean(assignment) || image.naturalWidth * 3 === image.naturalHeight * 2;
            status.textContent = entry.valid
                ? sizeFormatter.format(image.naturalWidth) + ' × ' + sizeFormatter.format(image.naturalHeight) + ' پیکسل'
                : 'نسبت عکس مانکن یا صحنه باید ۲ به ۳ باشد.';
            input.setCustomValidity(entry.valid ? '' : status.textContent);
            image.hidden = false;
            if (!entry.valid) input.reportValidity();
            if (assignment) updateCard(assignment);
        };
        image.onerror = () => {
            if (files.get(input) !== entry) return;
            status.textContent = 'این تصویر قابل خواندن نیست.';
            input.setCustomValidity(status.textContent);
            input.reportValidity();
            if (assignment) updateCard(assignment);
        };
        image.src = entry.url;
    });
}
