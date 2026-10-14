(() => {
    const enums = {
        airlines: ['active', 'inactive'], airports: ['active', 'inactive'],
        flights: ['scheduled', 'boarding', 'departed', 'completed', 'cancelled'],
        seat_status: ['available', 'blocked', 'unavailable'],
        booking_status: ['pending', 'confirmed', 'cancelled', 'completed', 'expired']
    };
    const validDate = value => {
        if (!/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(value)) return false;
        const date = new Date(`${value}:00Z`);
        return Number.isFinite(date.getTime()) && date.toISOString().slice(0, 16) === value;
    };
    const validateAdminField = ({ kind, name, value, values = {}, selectedLetters = [], zones = [], originalStatus = '' }) => {
        const text = value.trim();
        const bytes = new TextEncoder().encode(text).length;
        const title = { name: 'Name', city: 'City', country: 'Country', timezone: 'Timezone' }[name];
        const limit = name === 'name' ? (kind === 'airlines' ? 150 : 180) : ['city', 'country'].includes(name) ? 120 : name === 'timezone' ? 64 : 0;
        if (limit && (!text || bytes > limit)) return `Enter ${title.toLowerCase()} using no more than ${limit} bytes.`;
        if (name === 'iata_code' || name === 'icao_code') {
            const length = (kind === 'airlines' ? 2 : 3) + (name === 'icao_code' ? 1 : 0);
            if (name === 'icao_code' && !text) return '';
            if (!new RegExp(`^[A-Z]{${length}}$`).test(text.toUpperCase())) return `Enter a ${length}-letter ${name === 'iata_code' ? 'IATA' : 'ICAO'} code.`;
        }
        if (name === 'timezone') {
            if (zones.some(zone => zone.toLowerCase() === text.toLowerCase())) return '';
            if (/^[+-](?:[01]\d|2[0-3]):[0-5]\d$/.test(text)) return '';
            return 'Choose a valid timezone, such as Asia/Karachi.';
        }
        if (name === 'rows' && (!/^\d+$/.test(value) || Number(value) < 1 || Number(value) > 100)) return 'Enter a whole number of rows from 1 to 100.';
        if (name === 'letters[]' && (!selectedLetters.length || selectedLetters.length > 11 || selectedLetters.some(letter => !/^[A-K]$/i.test(letter)))) return 'Choose at least one valid seat letter (A–K).';
        if (name === 'flight_id' && (!/^[1-9]\d*$/.test(value) || !Number.isSafeInteger(Number(value)))) return 'Choose a flight from the list.';
        if (name === 'cabin_class' && !['economy', 'premium_economy', 'business', 'first'].includes(value)) return 'Choose one of the listed cabin classes.';
        if (name === 'base_fare' && (!/^\d{1,8}(?:\.\d{1,2})?$/.test(value) || Number(value) <= 0)) return 'Enter a positive fare up to 99,999,999.99, with at most two decimal places.';
        if (name === 'departure_at' || name === 'arrival_at') {
            if (!validDate(value)) return 'Enter a valid date and time.';
            if (name === 'arrival_at' && validDate(values.departure_at) && value <= values.departure_at) return 'Arrival must be later than departure.';
        }
        if (name === 'status') {
            if (!enums[kind]?.includes(value)) return 'Choose one of the listed statuses.';
            if (kind === 'booking_status' && originalStatus) {
                const allowed = { pending: ['pending', 'cancelled', 'expired'], confirmed: ['confirmed', 'cancelled', 'completed'], completed: ['completed'], cancelled: ['cancelled'], expired: ['expired'] }[originalStatus] || [];
                if (!allowed.includes(value)) return originalStatus === 'pending' && value === 'confirmed' ? 'Confirm this booking by verifying its payment on the Review payments page.' : `A ${originalStatus} booking cannot change to ${value}.`;
            }
        }
        return '';
    };
    if (typeof module !== 'undefined') module.exports = { validateAdminField };
    if (typeof document === 'undefined') return;
    const zones = JSON.parse(document.getElementById('admin-timezone-values')?.textContent || '[]');
    const period = document.getElementById('booking-trend-period');
    if (period) {
        const error = document.createElement('small'); error.id = 'admin-chart-period-error'; error.className = 'admin-choice-error'; error.hidden = true; error.setAttribute('aria-live', 'polite'); period.after(error);
        period.setAttribute('aria-describedby', error.id);
        period.addEventListener('change', () => {
            const message = ['daily', 'weekly', 'monthly'].includes(period.value) ? '' : 'Choose daily, weekly or monthly grouping.';
            period.setCustomValidity(message); period.setAttribute('aria-invalid', String(Boolean(message))); error.textContent = message; error.hidden = !message;
        });
    }
    document.querySelectorAll('.admin-content form').forEach((form, index) => {
        const kind = form.elements.namedItem('kind')?.value;
        if (!['airlines', 'airports', 'flights', 'seats', 'booking_status', 'seat_status'].includes(kind) || (kind === 'flights' && form.closest('.admin-create'))) return;
        // form.elements includes editable controls associated through their form attribute.
        const fields = [...form.elements].filter(field => field.name && field.matches('input:not([type=hidden]),select,textarea'));
        if (!fields.length) return;
        const originalStatus = form.elements.namedItem('status')?.value || '';
        const errors = new Map();
        const context = () => ({ kind, originalStatus, zones, values: Object.fromEntries(fields.map(field => [field.name, field.value])), selectedLetters: fields.filter(field => field.name === 'letters[]' && field.checked).map(field => field.value) });
        const validate = field => {
            field.setCustomValidity('');
            let message = field.disabled ? '' : validateAdminField({ ...context(), name: field.name, value: field.value });
            if (!message && !field.disabled && field.tagName === 'SELECT' && ![...field.options].some(option => option.value === field.value && !option.disabled)) message = 'Choose an available option from the list.';
            if (!message && field.validity.badInput) message = 'Enter a valid value.';
            field.setCustomValidity(message);
            field.setAttribute('aria-invalid', String(Boolean(message)));
            errors.get(field).textContent = message;
            return !message;
        };
        let lettersError;
        fields.forEach((field, position) => {
            let error = field.name === 'letters[]' ? lettersError : null;
            if (!error) {
                error = document.createElement('small'); error.id = `admin-field-${index}-${position}-error`; error.className = 'admin-field-error'; error.setAttribute('aria-live', 'polite');
                if (field.name === 'letters[]') { field.closest('.seat-letters').append(error); lettersError = error; }
                else field.after(error);
            }
            errors.set(field, error);
            field.setAttribute('aria-describedby', [field.getAttribute('aria-describedby'), error.id].filter(Boolean).join(' '));
            ['input', 'change', 'blur'].forEach(event => field.addEventListener(event, () => {
                validate(field);
                if (field.name === 'departure_at') { const arrival = form.elements.namedItem('arrival_at'); if (arrival?.value) validate(arrival); }
                if (field.name === 'letters[]') fields.filter(item => item.name === 'letters[]').forEach(validate);
            }));
        });
        form.noValidate = true;
        form.addEventListener('submit', event => {
            const invalid = fields.filter(field => !validate(field));
            if (!invalid.length) return;
            event.preventDefault(); event.stopImmediatePropagation(); delete form.dataset.confirmed; invalid[0].focus();
        }, true);
        form.addEventListener('reset', () => setTimeout(() => {
            fields.forEach(field => { field.setCustomValidity(''); field.removeAttribute('aria-invalid'); errors.get(field).textContent = ''; });
            delete form.dataset.confirmed;
        }, 0));
    });
})();
