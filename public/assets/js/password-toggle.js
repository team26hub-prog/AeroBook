(() => {
    const eyeOpen = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    const eyeClosed = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 3l18 18M10.6 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a15 15 0 0 1-3.1 3.8M6.2 6.3C3.5 8.1 2 12 2 12s3.6 7 10 7a10.8 10.8 0 0 0 3.4-.6"></path><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path></svg>';

    document.querySelectorAll('[data-password-toggle]').forEach(button => {
        const input = button.parentElement?.querySelector('input[type="password"], input[type="text"]');
        if (!(input instanceof HTMLInputElement)) return;

        button.innerHTML = eyeClosed;
        button.hidden = false;
        button.addEventListener('click', () => {
            const showPassword = input.type === 'password';
            input.type = showPassword ? 'text' : 'password';
            button.innerHTML = showPassword ? eyeOpen : eyeClosed;
            button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', String(showPassword));
        });
    });
})();
