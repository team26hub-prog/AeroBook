(() => {
    const form = document.querySelector('.flight-search-form');
    if (!form) return;
    const fields = [...form.querySelectorAll('select,input[type="date"]')];
    const get = name => form.elements.namedItem(name);
    const errors = new Map();
    const messageFor = field => {
        const value = field.value;
        if (field.name === 'date') {
            const date = new Date(`${value}T00:00:00Z`);
            if (field.validity.badInput || !/^\d{4}-\d{2}-\d{2}$/.test(value) || !Number.isFinite(date.getTime()) || date.toISOString().slice(0, 10) !== value) return 'Choose a valid travel date.';
            if (field.min && value < field.min) return 'Travel date cannot be in the past.';
            if (field.max && value > field.max) return 'Choose a travel date within the available range.';
            return '';
        }
        if (!/^[1-9]\d*$/.test(value) || ![...field.options].some(option => option.value === value && !option.disabled)) return `Choose a ${field.name === 'departure_id' ? 'departure' : 'destination'} airport from the list.`;
        if (field.name === 'arrival_id' && value === get('departure_id').value) return 'Departure and destination must be different airports.';
        return '';
    };
    const validate = field => {
        field.setCustomValidity('');
        const message = messageFor(field);
        field.setCustomValidity(message);
        field.setAttribute('aria-invalid', String(Boolean(message)));
        errors.get(field).textContent = message;
        return !message;
    };
    fields.forEach(field => {
        const error = document.createElement('small');
        error.id = `flight-search-${field.name}-error`;
        error.className = 'customer-field-error';
        error.setAttribute('aria-live', 'polite');
        field.after(error);
        field.setAttribute('aria-describedby', [field.getAttribute('aria-describedby'), error.id].filter(Boolean).join(' '));
        errors.set(field, error);
        ['input', 'change', 'blur'].forEach(event => field.addEventListener(event, () => {
            validate(field);
            if (field.name === 'departure_id' && get('arrival_id').value) validate(get('arrival_id'));
        }));
    });
    form.noValidate = true;
    form.addEventListener('submit', event => {
        const invalid = fields.filter(field => !validate(field));
        if (!invalid.length) return;
        event.preventDefault(); event.stopImmediatePropagation(); invalid[0].focus();
    }, true);
})();
