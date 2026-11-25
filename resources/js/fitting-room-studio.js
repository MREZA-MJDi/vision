const studio = document.querySelector('.fr-studio');

if (studio) {
    const files = new WeakMap();
    const sizeFormatter = new Intl.NumberFormat('fa-IR');
    const cards = JSON.parse(document.querySelector('#fr-variant-cards').textContent);

    function updateFrame(assignment, frame = null) {
        const imageFrame = assignment.querySelector('.product-media-frame');
        assignment.querySelectorAll('[data-fr-frame]').forEach(input => {
            const key = input.dataset.frFrame;
            if (frame) input.value = frame[key] ?? input.defaultValue;
            input.nextElementSibling.textContent = sizeFormatter.format(input.value) + '٪';
            imageFrame.style.setProperty(`--product-image-${key}`, key === 'scale' ? input.value / 100 : `${input.value}%`);
        });
    }

    function updateCard(assignment) {
        const id = assignment.querySelector('[data-fr-variant]').value;
        const card = cards[id];
        const preview = assignment.querySelector('[data-fr-variant-preview]');
        const image = preview.querySelector('[data-fr-card-image]');
        const newFile = files.get(assignment.querySelector('[data-fr-preview-input]'));
        const controls = assignment.querySelector('[data-fr-frame-controls]');
        controls.hidden = controls.disabled = !(card && (newFile?.valid || card.editable));
        preview.hidden = !card;
        if (!card) {
            image.removeAttribute('src');
            return;
        }

        const source = newFile?.valid ? newFile.url : card.image;
        image.hidden = !source;
        if (source) image.src = source;
        else image.removeAttribute('src');
        preview.querySelector('[data-fr-card-empty]').hidden = Boolean(source);
        preview.querySelector('[data-fr-card-name]').textContent = card.name;
        preview.querySelector('[data-fr-variant-name]').textContent = card.variant;
        preview.querySelector('[data-fr-card-price]').textContent = card.price;
        preview.querySelector('[data-fr-card-stock]').textContent = card.stock;
        updateFrame(assignment, newFile?.valid ? {} : card.frame);
    }

    const clearFile = input => {
        if (!input || !input.files.length) return;
        input.value = '';
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };

    function syncLook(form, changed = null) {
        const model = form.querySelector('[data-fr-model]');
        if (!model) return;
        const audience = model.selectedOptions[0]?.dataset.audience;
        const top = form.querySelector('[name="top_variant_id"]');
        const bottom = form.querySelector('[name="bottom_variant_id"]');
        for (const picker of [top, bottom]) {
            const search = picker.closest('.fr-assignment').querySelector('[data-fr-picker-search]');
            const query = search?.value.trim().toLocaleLowerCase('fa') || '';
            for (const option of picker.options) {
                if (!option.value) continue;
                const compatible = audience === 'all' || option.dataset.audience === audience;
                option.disabled = !compatible;
                option.hidden = !compatible || (option.value !== picker.value && !option.textContent.toLocaleLowerCase('fa').includes(query));
            }
            if (changed === 'model' && picker.selectedOptions[0]?.disabled) {
                picker.value = '';
                clearFile(picker.closest('.fr-assignment').querySelector('[data-fr-preview-input]'));
            }
            if (changed !== 'search' && (changed === null || changed === 'model' || picker.name.startsWith(changed))) {
                updateCard(picker.closest('.fr-assignment'));
            }
        }
        const hasTop = Boolean(top.value), hasBottom = Boolean(bottom.value);
        const requirements = { preview_top: hasTop, preview_bottom: hasBottom, preview_combined: hasTop && hasBottom };
        for (const input of form.querySelectorAll('[data-fr-scene]')) {
            const needed = requirements[input.name];
            const stale = changed === 'model'
                || (changed === 'top' && ['preview_top', 'preview_combined', 'preview_suggested'].includes(input.name))
                || (changed === 'bottom' && ['preview_bottom', 'preview_combined', 'preview_suggested'].includes(input.name));
            if (stale) {
                input.dataset.existing = '0';
                clearFile(input);
                const old = input.closest('label').querySelector('.fr-scene-current');
                if (old) old.hidden = true;
            }
            if (needed !== undefined) {
                input.disabled = !needed;
                input.required = needed && input.dataset.existing !== '1';
                input.closest('label').hidden = !needed;
                if (!needed) clearFile(input);
            }
        }
        top.setCustomValidity(hasTop || hasBottom ? '' : 'حداقل یک لباس انتخاب کنید.');
        bottom.setCustomValidity(hasTop && top.value === bottom.value ? 'دو لباس باید متفاوت باشند.' : '');
    }

    studio.querySelectorAll('form:has([data-fr-model])').forEach(form => syncLook(form));

    studio.addEventListener('input', event => {
        if (event.target.matches('[data-fr-frame]')) updateFrame(event.target.closest('.fr-assignment'));
        if (event.target.matches('[data-fr-picker-search]')) syncLook(event.target.closest('form'), 'search');
    });

    studio.addEventListener('click', event => {
        const reset = event.target.closest('[data-fr-frame-reset]');
        if (reset) updateFrame(reset.closest('.fr-assignment'), {});
    });

    studio.addEventListener('change', (event) => {
        const input = event.target;
        const assignment = input.closest('.fr-assignment');
        if (input.matches('[data-fr-model]')) {
            syncLook(input.closest('form'), 'model');
            return;
        }
        if (input.matches('[data-fr-variant]')) {
            clearFile(assignment.querySelector('[data-fr-preview-input]'));
            syncLook(input.closest('form'), input.name.startsWith('top') ? 'top' : 'bottom');
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
            image.hidden = Boolean(assignment);
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
