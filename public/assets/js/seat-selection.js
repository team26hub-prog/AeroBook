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
    const refresh = () => {
        const selected = selects.map(select => select.value).filter(Boolean);
        const complete = selects.length > 0 && selected.length === selects.length && new Set(selected).size === selects.length;
        progress.textContent = `${selected.length} of ${selects.length} passengers have seats`;
        fill.style.width = `${selects.length ? selected.length / selects.length * 100 : 0}%`;
        save.disabled = !complete;
        selects.forEach(select => {
            const row = select.closest('.seat-passenger-row');
            const option = select.selectedOptions[0];
            row.querySelector('[data-assignment-label]').textContent = select.value ? `Seat ${option.dataset.seatNumber}` : 'Not selected';
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
        if (!target) return;
        if (selects.some(select => select !== target && select.value === button.dataset.seatId)) {
            help.textContent = `Seat ${button.dataset.seatNumber} is already selected for another passenger. Choose a different seat.`;
            return;
        }
        target.value = button.dataset.seatId;
        help.textContent = `Seat ${button.dataset.seatNumber} selected for ${target.closest('.seat-passenger-row').querySelector('strong').textContent}.`;
        refresh();
        const next = selects.find(select => !select.value);
        if (next) activePassenger.value = next.dataset.passengerId;
    }));
    selects.forEach(select => {
        select.addEventListener('focus', () => { activePassenger.value = select.dataset.passengerId; });
        select.addEventListener('change', () => {
            if (select.value && selects.some(other => other !== select && other.value === select.value)) {
                select.value = '';
                help.textContent = 'Each passenger must have a different seat.';
            }
            refresh();
        });
    });
    form.addEventListener('submit', event => {
        const values = selects.map(select => select.value);
        if (!values.length || values.some(value => !value) || new Set(values).size !== values.length) {
            event.preventDefault();
            help.textContent = 'Choose a different available seat for every passenger before saving.';
            save.disabled = true;
        }
    });
    refresh();
})();
