(() => {
    const form = document.querySelector('.admin-section-flights .admin-create form');
    if (!form) return;
    const fields = [...form.querySelectorAll('input:not([type="hidden"]),select')];
    const errors = new Map();
    const get = name => form.elements.namedItem(name);
    const validDate = value => {
        if (!/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(value)) return false;
        const date = new Date(`${value}:00Z`);
        return Number.isFinite(date.getTime()) && date.toISOString().slice(0, 16) === value;
    };
    const messageFor = field => {
        const value = field.value.trim();
        if (['airline_id', 'departure_airport_id', 'arrival_airport_id'].includes(field.name)) {
            const label = { airline_id: 'an airline', departure_airport_id: 'a departure airport', arrival_airport_id: 'an arrival airport' }[field.name];
            if (!/^[1-9]\d*$/.test(value) || !Number.isSafeInteger(Number(value)) || ![...field.options].some(option => option.value === value && !option.disabled)) return `Choose ${label} from the list.`;
            if (field.name === 'arrival_airport_id' && value === get('departure_airport_id').value) return 'Arrival airport must be different from departure airport.';
        }
        switch (field.name) {
            case 'flight_number':
                if (!value) return 'Enter a flight number, such as AB101.';
                if (!/^[A-Z0-9-]{2,10}$/.test(value.toUpperCase())) return 'Use 2–10 letters, numbers or hyphens, such as AB101.';
                break;
            case 'departure_at':
            case 'arrival_at':
                if (field.validity.badInput || !validDate(value)) return `Enter a valid ${field.name === 'departure_at' ? 'departure' : 'arrival'} date and time.`;
                if (field.name === 'arrival_at' && validDate(get('departure_at').value) && value <= get('departure_at').value) return 'Arrival must be later than departure. Check the date and time.';
                break;
            case 'base_fare':
                if (field.validity.badInput || !/^\d{1,8}(?:\.\d{1,2})?$/.test(value) || Number(value) <= 0) return 'Enter a fare from 0.01 to 99,999,999.99 with at most two decimal places.';
                break;
            case 'currency':
                if (!/^[A-Z]{3}$/.test(value.toUpperCase())) return 'Enter a three-letter currency code, such as PKR or USD.';
                break;
            case 'status':
                if (!['scheduled', 'boarding', 'departed', 'completed', 'cancelled'].includes(value)) return 'Choose one of the listed flight statuses.';
                break;
        }
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
        error.id = `admin-flight-${field.name}-error`;
        error.className = 'admin-flight-field-error';
        error.setAttribute('aria-live', 'polite');
        // Append below the existing help text so labels and controls stay aligned.
        field.closest('label').append(error);
        field.setAttribute('aria-describedby', [field.getAttribute('aria-describedby'), error.id].filter(Boolean).join(' '));
        errors.set(field, error);
        ['input', 'change', 'blur'].forEach(event => field.addEventListener(event, () => {
            validate(field);
            const related = { departure_airport_id: 'arrival_airport_id', departure_at: 'arrival_at' }[field.name];
            if (related && get(related).value) validate(get(related));
        }));
    });
    // Keep native validation as the fallback if this script cannot load.
    form.noValidate = true;
    form.addEventListener('submit', event => {
        const invalid = fields.filter(field => !validate(field));
        if (!invalid.length) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        delete form.dataset.confirmed;
        invalid[0].focus();
    }, true);
    form.addEventListener('reset', () => setTimeout(() => {
        fields.forEach(field => {
            field.setCustomValidity('');
            field.removeAttribute('aria-invalid');
            errors.get(field).textContent = '';
        });
        delete form.dataset.confirmed;
    }, 0));
})();
