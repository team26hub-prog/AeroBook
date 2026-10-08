(() => {
    const Swal = window.Swal;
    const toast = Swal?.mixin({
        toast: true,
        position: window.matchMedia('(max-width: 600px)').matches ? 'top' : 'top-end',
        showConfirmButton: false,
        showCloseButton: true,
        timerProgressBar: true,
        heightAuto: false,
        customClass: { popup: 'aerobook-toast' },
        didOpen: element => {
            element.addEventListener('mouseenter', Swal.stopTimer);
            element.addEventListener('mouseleave', Swal.resumeTimer);
            element.addEventListener('focusin', Swal.stopTimer);
            element.addEventListener('focusout', Swal.resumeTimer);
        }
    });
    const toastTitles = {
        success: 'Success',
        error: 'Something went wrong',
        warning: 'Please note',
        info: 'Information'
    };
    const showToast = (type, message, options = {}) => {
        const validType = Object.hasOwn(toastTitles, type) ? type : 'info';
        if (!toast || !String(message ?? '').trim()) return;
        return toast.fire({
            icon: validType,
            titleText: options.title || toastTitles[validType],
            text: String(message),
            timer: options.timer || (validType === 'error' ? 7500 : 5000),
            ...options
        });
    };
    window.AeroBookToast = showToast;

    const messageNodes = [...document.querySelectorAll(
        '.alert-success, .alert-error, .alert-warning, .alert-info, .admin-alert.success, .admin-alert.error, .admin-alert.warning, .admin-alert.info, .customer-notice, [data-toast-type]'
    )];
    const typeOf = node => {
        const declared = node.dataset.toastType;
        if (Object.hasOwn(toastTitles, declared)) return declared;
        if (node.matches('.alert-error, .admin-alert.error')) return 'error';
        if (node.matches('.alert-warning, .admin-alert.warning, .customer-notice.warning')) return 'warning';
        if (node.matches('.alert-info, .admin-alert.info, .customer-notice.info')) return 'info';
        return 'success';
    };
    const textOf = node => node.querySelector('ul')
        ? [...node.querySelectorAll('li')].map(item => item.textContent.trim()).filter(Boolean).join('\n')
        : node.textContent.trim();

    if (toast && messageNodes.length) {
        const typePriority = ['error', 'warning', 'info', 'success'];
        const messages = messageNodes.map(node => ({ type: typeOf(node), text: textOf(node) })).filter(item => item.text);
        const type = typePriority.find(candidate => messages.some(item => item.type === candidate)) || 'info';
        messageNodes.forEach(node => { node.hidden = true; });
        showToast(type, messages.map(item => item.text).join('\n'));
    }

    document.addEventListener('submit', async event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.submitting === 'true') return;
        const submitter = event.submitter instanceof HTMLButtonElement ? event.submitter : null;
        let title = form.dataset.confirmTitle || '';
        let message = form.dataset.confirm || '';
        let confirmLabel = form.dataset.confirmButton || 'Continue';
        let icon = 'warning';

        if (form.matches('.review-actions') && submitter) {
            const rejecting = submitter.value === 'rejected';
            title = rejecting ? 'Reject this payment?' : 'Verify this payment?';
            message = rejecting
                ? 'The customer will need to submit corrected payment details.'
                : 'Verification confirms the booking and prepares an e-ticket for each passenger.';
            confirmLabel = rejecting ? 'Reject payment' : 'Verify payment';
        } else if (form.matches('.customer-signout, .admin-user form, form[action="/logout"], form[action="/admin/logout"]')) {
            title = 'Sign out?';
            message = 'You will need to sign in again to access your account.';
            confirmLabel = 'Sign out';
            icon = 'question';
        }

        if (message && form.dataset.confirmed !== 'true') {
            event.preventDefault();
            let confirmed = false;
            if (window.Swal) {
                const result = await window.Swal.fire({
                    icon,
                    titleText: title || 'Are you sure?',
                    text: message,
                    showCancelButton: true,
                    confirmButtonText: confirmLabel,
                    cancelButtonText: 'Go back',
                    confirmButtonColor: '#597f97',
                    cancelButtonColor: '#e3e9ee',
                    reverseButtons: true,
                    focusCancel: true,
                    heightAuto: false
                });
                confirmed = result.isConfirmed;
            } else {
                confirmed = window.confirm(`${title ? `${title}\n\n` : ''}${message}`);
            }
            if (!confirmed) return;
            form.dataset.confirmed = 'true';
            form.requestSubmit(submitter || undefined);
            return;
        }

        form.dataset.submitting = 'true';
        if (submitter) {
            submitter.dataset.originalText = submitter.textContent || '';
            submitter.disabled = true;
            submitter.setAttribute('aria-busy', 'true');
        }
    }, true);
})();
