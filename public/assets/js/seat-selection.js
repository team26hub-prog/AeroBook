(() => {
    const form = document.getElementById('seat-selection-form');
    if (!form) return;
    const activePassenger = document.getElementById('active-passenger');
    const selects = [...form.querySelectorAll('[data-passenger-select]')];
    const buttons = [...form.querySelectorAll('.map-seat')];
    const help = document.getElementById('seat-map-help');
    const progress = document.getElementById('seat-selection-progress');
    const fill = document.getElementById('seat-progress-fill');
    const save = document.getElementById('save-seat-selection');
    const errors = new Map();
    const touched = new Set();
    const validChoice = select => Boolean(select.value && [...select.options].some(option => option.value === select.value));
    selects.forEach((select, index) => {
        const error = document.createElement('small');
        error.id = `seat-passenger-${index}-error`;
        error.className = 'customer-field-error';
        error.setAttribute('aria-live', 'polite');
        select.closest('.seat-passenger-row').append(error);
        select.setAttribute('aria-describedby', [select.getAttribute('aria-describedby'), error.id].filter(Boolean).join(' '));
        errors.set(select, error);
    });
    const validate = select => {
        const duplicate = select.value && selects.some(other => other !== select && other.value === select.value);
        const message = duplicate ? 'Each passenger must have a different seat.' : !validChoice(select) ? 'Choose an available seat for this passenger.' : '';
        select.setCustomValidity(message);
        if (touched.has(select)) {
            select.setAttribute('aria-invalid', String(Boolean(message)));
            errors.get(select).textContent = message;
        }
        return !message;
    };
    const refresh = () => {
        const selected = selects.map(select => select.value).filter(Boolean);
        const complete = selects.length > 0 && selects.every(validChoice) && selected.length === selects.length && new Set(selected).size === selects.length;
        progress.textContent = `${selected.length} of ${selects.length} passengers have seats`;
        fill.style.width = `${selects.length ? selected.length / selects.length * 100 : 0}%`;
        save.disabled = !complete;
        selects.forEach(select => {
            const row = select.closest('.seat-passenger-row');
            const option = select.selectedOptions[0];
            row.querySelector('[data-assignment-label]').textContent = validChoice(select) ? `Seat ${option.dataset.seatNumber}` : 'Not selected';
            validate(select);
            [...select.options].forEach(option => {
                option.disabled = !!option.value && selects.some(other => other !== select && other.value === option.value);
            });
        });
        buttons.forEach(button => {
            const passenger = selects.find(select => select.value === button.dataset.seatId);
            const chosen = !!passenger;
            button.classList.toggle('chosen', chosen);
            button.classList.toggle('assigned', chosen);
            button.setAttribute('aria-pressed', String(chosen));
            const state = chosen ? `selected for ${passenger.closest('.seat-passenger-row').querySelector('strong').textContent}`
                : button.dataset.seatState === 'occupied' ? 'reserved'
                : button.dataset.seatState === 'blocked' ? 'blocked' : 'available';
            button.setAttribute('aria-label', `Seat ${button.dataset.seatNumber}, ${button.dataset.cabin}, ${state}`);
        });
    };
    buttons.forEach(button => button.addEventListener('click', () => {
        if (button.disabled) return;
        const target = selects.find(select => select.dataset.passengerId === activePassenger.value);
        if (!target) { help.textContent = 'Choose a passenger before selecting a seat.'; return; }
        if (selects.some(select => select !== target && select.value === button.dataset.seatId)) {
            help.textContent = `Seat ${button.dataset.seatNumber} is already selected for another passenger. Choose a different seat.`;
            return;
        }
        target.value = button.dataset.seatId;
        touched.add(target);
        help.textContent = `Seat ${button.dataset.seatNumber} selected for ${target.closest('.seat-passenger-row').querySelector('strong').textContent}.`;
        refresh();
        const next = selects.find(select => !select.value);
        if (next) activePassenger.value = next.dataset.passengerId;
    }));
    selects.forEach(select => {
        select.addEventListener('focus', () => { activePassenger.value = select.dataset.passengerId; });
        select.addEventListener('change', () => {
            touched.add(select);
            if (select.value && selects.some(other => other !== select && other.value === select.value)) {
                select.value = '';
                help.textContent = 'Each passenger must have a different seat.';
            }
            refresh();
        });
        select.addEventListener('blur', () => { touched.add(select); validate(select); });
    });
    activePassenger.addEventListener('change', () => {
        const valid = selects.some(select => select.dataset.passengerId === activePassenger.value);
        activePassenger.setAttribute('aria-invalid', String(!valid));
        help.textContent = valid ? 'Choose an available seat for the selected passenger.' : 'Choose a passenger from the list.';
    });
    form.addEventListener('submit', event => {
        selects.forEach(select => touched.add(select));
        const invalid = selects.filter(select => !validate(select));
        const values = selects.map(select => select.value);
        if (!values.length || invalid.length || values.some(value => !value) || new Set(values).size !== values.length) {
            event.preventDefault();
            help.textContent = 'Choose a different available seat for every passenger before saving.';
            save.disabled = true;
            invalid[0]?.focus();
        }
    });
    refresh();
})();
