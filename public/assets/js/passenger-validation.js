(() => {
    const form = document.getElementById('passenger-form');
    if (!form) return;
    const count = document.getElementById('passenger-count');
    const groups = [...form.querySelectorAll('[data-passenger]')];
    const fields = groups.flatMap(group => [...group.querySelectorAll('input,select')]);
    const errors = new Map();
    const key = field => field.name.match(/\[([^\]]+)\]$/)?.[1];
    const sibling = (field, name) => fields.find(item => item.name === field.name.replace(/\[[^\]]+\]$/, `[${name}]`));
    const messageFor = field => {
        if (field === count) return /^[1-9]$/.test(count.value) ? '' : 'Choose between 1 and 9 passengers.';
        const name = key(field), value = field.value.trim();
        if (name === 'first_name' || name === 'last_name') {
            const label = name === 'first_name' ? 'First name' : 'Last name';
            if (!value) return `Enter the passenger's ${label.toLowerCase()}.`;
            if (Array.from(value).length > 100) return `${label} must be 100 characters or fewer.`;
            if (!/^\p{L}[\p{L}\p{M}]*(?: +\p{L}[\p{L}\p{M}]*)*$/u.test(value)) return `${label} must contain only letters and spaces.`;
        }
        if (name === 'date_of_birth') {
            if (field.validity.badInput) return 'Enter a valid date of birth.';
            if (!value) return "Enter the passenger's date of birth.";
            const date = new Date(`${value}T00:00:00Z`);
            if (!/^\d{4}-\d{2}-\d{2}$/.test(value) || !Number.isFinite(date.getTime()) || date.toISOString().slice(0, 10) !== value || value < field.min) return 'Enter a valid date of birth.';
            if (value > field.max) return 'Date of birth cannot be in the future.';
        }
        if (name === 'gender' && !['female', 'male', 'other', 'unspecified'].includes(value)) return 'Choose one of the listed gender options.';
        if (name === 'passport_number') {
            if (value && !/^[A-Za-z0-9]{1,30}$/.test(value)) return 'Passport number must contain only letters and numbers (maximum 30 characters).';
            if (!value && sibling(field, 'passport_country').value.trim()) return 'Enter a passport number when a country is provided.';
        }
        if (name === 'passport_country') {
            if (value && !/^[A-Za-z]{2}$/.test(value)) return 'Enter a two-letter country code, such as PK.';
            if (!value && sibling(field, 'passport_number').value.trim()) return 'Enter the passport country, such as PK.';
        }
        return '';
    };
    const validate = field => {
        field.setCustomValidity('');
        const message = field.disabled ? '' : messageFor(field);
        field.setCustomValidity(message);
        field.setAttribute('aria-invalid', String(Boolean(message)));
        errors.get(field).textContent = message;
        return !message;
    };
    [count, ...fields].forEach((field, index) => {
        const error = document.createElement('small');
        error.id = `passenger-field-${index}-error`;
        error.className = 'passenger-field-error';
        error.setAttribute('aria-live', 'polite');
        field.after(error);
        field.setAttribute('aria-describedby', [field.getAttribute('aria-describedby'), error.id].filter(Boolean).join(' '));
        errors.set(field, error);
        ['input', 'change', 'blur'].forEach(event => field.addEventListener(event, () => {
            validate(field);
            if (key(field) === 'passport_number') validate(sibling(field, 'passport_country'));
            if (key(field) === 'passport_country') validate(sibling(field, 'passport_number'));
        }));
    });
    count.addEventListener('change', () => {
        const total = /^[1-9]$/.test(count.value) ? Number(count.value) : 1;
        groups.forEach((group, index) => {
            const active = index < total;
            group.hidden = !active;
            group.querySelectorAll('input[name],select[name]').forEach(field => {
                field.disabled = !active;
                if (['first_name', 'last_name', 'date_of_birth'].includes(key(field))) field.required = active;
                if (!active) validate(field);
            });
        });
    });
    form.noValidate = true;
    form.addEventListener('submit', event => {
        const invalid = [count, ...fields].filter(field => !validate(field));
        if (!invalid.length) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        invalid[0].focus();
    }, true);
})();
