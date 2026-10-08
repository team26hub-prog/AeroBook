(() => {
    document.querySelectorAll('form[data-auth-validation]').forEach(form => {
        const fields = [...form.querySelectorAll('input[required]')].filter(field => field.name !== '_csrf');
        const errorFor = field => form.querySelector(`[data-error-for="${CSS.escape(field.name)}"]`);

        const messageFor = field => {
            const value = field.value.trim();
            if (!value) return 'This field is required.';

            if (field.type === 'email') {
                if (value.length > 254 || field.validity.typeMismatch || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    return 'Enter a valid email address.';
                }
            }

            if (field.name === 'full_name' && value.length > 150) return 'Your name must be 150 characters or fewer.';

            if (field.name === 'password' && form.action.endsWith('/register')) {
                const bytes = new TextEncoder().encode(field.value).length;
                if (bytes < 8 || bytes > 72) return 'Your password must be between 8 and 72 bytes.';
            }

            if (field.name === 'password_confirmation') {
                const password = form.elements.namedItem('password');
                if (password && field.value !== password.value) return 'The password confirmation does not match.';
                const bytes = new TextEncoder().encode(field.value).length;
                if (bytes < 8 || bytes > 72) return 'Your password must be between 8 and 72 bytes.';
            }

            return '';
        };

        const validate = field => {
            const error = errorFor(field);
            if (!error) return true;
            const message = messageFor(field);
            error.textContent = message;
            field.classList.toggle('input-invalid', Boolean(message));
            field.setAttribute('aria-invalid', String(Boolean(message)));
            return !message;
        };

        fields.forEach(field => {
            field.addEventListener('blur', () => validate(field));
            field.addEventListener('input', () => {
                validate(field);
                if (field.name === 'password' && form.elements.namedItem('password_confirmation')?.value) {
                    validate(form.elements.namedItem('password_confirmation'));
                }
            });
        });

        form.addEventListener('submit', event => {
            const invalid = fields.filter(field => !validate(field));
            if (invalid.length) {
                event.preventDefault();
                invalid[0].focus();
            }
        });
    });
})();
