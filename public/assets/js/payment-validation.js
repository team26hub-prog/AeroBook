(() => {
    document.querySelectorAll('form[data-payment-validation]').forEach(form => {
        const fields = [...form.querySelectorAll('input:not([type="hidden"]), select, textarea')];
        const errors = new Map();
        const started = performance.now();
        // Treat the server's wall clock as UTC solely for arithmetic; browser time zones
        // must not change the payment date limits displayed by the application.
        const serverTime = Date.parse(`${form.dataset.serverNow}Z`);
        const updateMaximum = () => {
            const date = form.elements.namedItem('paid_at');
            if (date && Number.isFinite(serverTime)) {
                date.max = new Date(serverTime + performance.now() - started).toISOString().slice(0, 16);
            }
        };
        const length = value => Array.from(value).length;
        const messageFor = field => {
            const value = field.value.trim();
            if (field.validity.badInput) return field.name === 'amount' ? 'Enter a valid paid amount.' : 'Enter a valid payment date and time.';
            if (!value && field.required) return {
                method: 'Choose a payment method.',
                sender_name: 'Enter the sender or account holder name.',
                transaction_reference: 'Enter the transaction or reference ID.',
                paid_at: 'Enter the payment date and time.',
                amount: 'Enter the paid amount.'
            }[field.name] || 'This field is required.';
            switch (field.name) {
                case 'method':
                    if (!['bank_transfer', 'bank_deposit', 'mobile_wallet', 'cash', 'other'].includes(value)) return 'Choose one of the available payment methods.';
                    break;
                case 'sender_name':
                    if (length(value) > 150) return 'Sender / account name must be 150 characters or fewer.';
                    if (!/^[\p{L}\p{M} .'’&()-]+$/u.test(value) || (value.match(/\p{L}/gu) || []).length < 2) return 'Enter a valid sender / account name with at least two letters (for example, Daniel Harris).';
                    break;
                case 'transaction_reference':
                    if (length(value) > 100) return 'Transaction / reference ID must be 100 characters or fewer.';
                    if (!/^[\p{L}\p{N} ._\/-]+$/u.test(value) || !/[\p{L}\p{N}]/u.test(value)) return 'Enter a valid transaction / reference ID containing letters or numbers (for example, TXN-123456).';
                    break;
                case 'paid_at': {
                    const date = new Date(`${value}:00Z`);
                    if (!/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(value) || !Number.isFinite(date.getTime()) || date.toISOString().slice(0, 16) !== value) return 'Enter a valid payment date and time.';
                    if (field.min && value < field.min) return 'Payment date and time cannot be earlier than the booking.';
                    if (field.max && value > field.max) return 'Payment date and time cannot be in the future.';
                    break;
                }
                case 'amount':
                    if (!/^\d{1,8}(?:\.\d{1,2})?$/.test(value) || Number(value) < 0.01 || Number(value) > 99999999.99) return 'Enter an amount from 0.01 to 99,999,999.99 with at most two decimal places.';
                    break;
                case 'remarks':
                    if (length(value) > 2000) return 'Remarks must be 2,000 characters or fewer.';
                    break;
            }
            if (field.validity.tooLong) return `Use ${field.maxLength} characters or fewer.`;
            return '';
        };
        const validate = field => {
            updateMaximum();
            field.setCustomValidity('');
            const message = messageFor(field);
            field.setCustomValidity(message);
            field.setAttribute('aria-invalid', String(Boolean(message)));
            errors.get(field).textContent = message;
            return !message;
        };
        fields.forEach(field => {
            const error = document.createElement('small');
            error.id = `payment-${field.name}-error`;
            error.className = 'payment-field-error';
            error.setAttribute('aria-live', 'polite');
            field.after(error);
            field.setAttribute('aria-describedby', [field.getAttribute('aria-describedby'), error.id].filter(Boolean).join(' '));
            errors.set(field, error);
            ['input', 'change', 'blur'].forEach(event => field.addEventListener(event, () => validate(field)));
        });
        // Native validation remains the fallback when JavaScript is unavailable.
        form.noValidate = true;
        form.addEventListener('submit', event => {
            const invalid = fields.filter(field => !validate(field));
            if (!invalid.length) return;
            event.preventDefault();
            // Do not open the shared confirmation dialog for an invalid submission.
            event.stopImmediatePropagation();
            delete form.dataset.confirmed;
            invalid[0].focus();
        }, true);
    });
})();
