(() => {
    document.querySelectorAll('.customer-app input[type="date"]').forEach((input, index) => {
    const purpose = input.name.endsWith('[date_of_birth]') ? 'date of birth' : 'travel date';
    const field = document.createElement('div');
    field.className = 'flight-date-field';
    input.before(field);
    field.append(input);
    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'flight-date-toggle';
    toggle.setAttribute('aria-label', `Choose ${purpose}`);
    toggle.setAttribute('aria-expanded', 'false');
    toggle.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 11h18"/></svg>';
    const panel = document.createElement('div');
    panel.className = 'flight-date-calendar';
    panel.id = `customer-date-calendar-${index}`;
    panel.hidden = true;
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', `Choose ${purpose}`);
    toggle.setAttribute('aria-controls', panel.id);
    field.append(toggle, panel);
    const parse = value => {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return null;
        const [year, month, day] = value.split('-').map(Number);
        const date = new Date(0);
        date.setHours(0, 0, 0, 0);
        date.setFullYear(year, month - 1, day);
        return format(date) === value ? date : null;
    };
    const format = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    const today = () => { const date = new Date(); date.setHours(0, 0, 0, 0); return date; };
    let month = today();
    const allowed = date => (!input.min || format(date) >= input.min) && (!input.max || format(date) <= input.max);
    const close = (restoreFocus = false) => {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        field.classList.remove('calendar-open');
        if (restoreFocus) toggle.focus();
    };
    const select = date => {
        input.value = date ? format(date) : '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        close(true);
    };
    const button = (text, name, action) => {
        const element = document.createElement('button');
        element.type = 'button';
        element.textContent = text;
        if (name) element.setAttribute('aria-label', name);
        element.addEventListener('click', action);
        return element;
    };
    const render = () => {
        panel.replaceChildren();
        const header = document.createElement('div');
        header.className = 'flight-date-header';
        const title = document.createElement('div');
        title.className = 'flight-date-title';
        const monthChoice = document.createElement('select');
        monthChoice.setAttribute('aria-label', 'Calendar month');
        for (let m = 0; m < 12; m++) {
            const option = document.createElement('option');
            option.value = String(m);
            option.textContent = new Date(2000, m, 1).toLocaleDateString('en', { month: 'short' });
            monthChoice.append(option);
        }
        monthChoice.value = String(month.getMonth());
        const yearChoice = document.createElement('select');
        yearChoice.setAttribute('aria-label', 'Calendar year');
        const firstYear = parse(input.min)?.getFullYear() || 1000;
        const lastYear = parse(input.max)?.getFullYear() || Math.max(today().getFullYear() + 100, month.getFullYear());
        for (let year = lastYear; year >= firstYear; year--) {
            const option = document.createElement('option');
            option.value = String(year);
            option.textContent = String(year);
            yearChoice.append(option);
        }
        yearChoice.value = String(month.getFullYear());
        const changeMonth = focus => {
            month = new Date(Number(yearChoice.value), Number(monthChoice.value), 1);
            render();
            panel.querySelector(`[aria-label="Calendar ${focus}"]`).focus();
        };
        monthChoice.addEventListener('change', () => changeMonth('month'));
        yearChoice.addEventListener('change', () => changeMonth('year'));
        title.append(monthChoice, yearChoice);
        const move = direction => {
            month = new Date(month.getFullYear(), month.getMonth() + direction, 1);
            render();
            panel.querySelector(direction < 0 ? '[data-previous]' : '[data-next]').focus();
        };
        const previous = button('‹', 'Previous month', () => move(-1));
        previous.dataset.previous = '';
        const next = button('›', 'Next month', () => move(1));
        next.dataset.next = '';
        previous.disabled = Boolean(input.min && format(new Date(month.getFullYear(), month.getMonth(), 0)) < input.min);
        next.disabled = Boolean(input.max && format(new Date(month.getFullYear(), month.getMonth() + 1, 1)) > input.max);
        header.append(title, previous, next);
        const days = document.createElement('div');
        days.className = 'flight-date-days';
        ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'].forEach(day => {
            const heading = document.createElement('span');
            heading.textContent = day;
            days.append(heading);
        });
        const first = new Date(month.getFullYear(), month.getMonth(), 1);
        for (let i = 0; i < first.getDay(); i++) days.append(document.createElement('span'));
        const count = new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate();
        for (let day = 1; day <= count; day++) {
            const date = new Date(month.getFullYear(), month.getMonth(), day);
            const item = button(String(day), date.toLocaleDateString('en', { dateStyle: 'full' }), () => select(date));
            item.disabled = !allowed(date);
            item.setAttribute('aria-pressed', String(format(date) === input.value));
            if (format(date) === format(today())) item.setAttribute('aria-current', 'date');
            days.append(item);
        }
        const footer = document.createElement('div');
        footer.className = 'flight-date-footer';
        const current = button('Today', null, () => select(today()));
        current.disabled = !allowed(today());
        footer.append(button('Clear', null, () => select(null)), current);
        panel.append(header, days, footer);
    };
    const open = () => {
        if (input.disabled) return;
        month = parse(input.value) || today();
        if (input.min && format(month) < input.min) month = parse(input.min);
        if (input.max && format(month) > input.max) month = parse(input.max);
        month.setDate(1);
        render();
        panel.hidden = false;
        field.classList.add('calendar-open');
        toggle.setAttribute('aria-expanded', 'true');
        (panel.querySelector('[aria-pressed="true"]') || panel.querySelector('.flight-date-days button:not(:disabled)') || panel.querySelector('button')).focus();
    };
    toggle.addEventListener('click', () => panel.hidden ? open() : close());
    input.addEventListener('click', event => {
        event.preventDefault();
        if (panel.hidden) open();
    });
    input.addEventListener('pointerdown', event => {
        // Open our calendar without launching the device's native date dialog.
        event.preventDefault();
        if (panel.hidden) open();
    });
    input.addEventListener('keydown', event => {
        if (event.key === 'Enter' || (event.altKey && event.key === 'ArrowDown')) {
            event.preventDefault();
            open();
        }
    });
    field.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !panel.hidden) { event.preventDefault(); close(true); }
    });
    document.addEventListener('pointerdown', event => { if (!field.contains(event.target)) close(); });
    field.addEventListener('focusout', event => { if (!field.contains(event.relatedTarget)) close(); });
    window.addEventListener('resize', () => close());
    });
})();
