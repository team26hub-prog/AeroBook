(() => {
    document.querySelectorAll('.customer-app input[type="date"], .admin-section-flights input[type="datetime-local"]').forEach((input, index) => {
    const withTime = input.type === 'datetime-local';
    const purpose = withTime ? `${input.name === 'arrival_at' ? 'arrival' : 'departure'} date and time` : (input.name.endsWith('[date_of_birth]') ? 'date of birth' : 'travel date');
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
    let draftDate = '';
    let hours = '12';
    let minutes = '00';
    const allowed = date => (!input.min || format(date) >= input.min.slice(0, 10)) && (!input.max || format(date) <= input.max.slice(0, 10));
    const close = (restoreFocus = false) => {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        field.classList.remove('calendar-open');
        if (restoreFocus) toggle.focus();
    };
    const select = date => {
        if (withTime && date) { draftDate = format(date); render(); return; }
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
        const firstYear = parse(input.min?.slice(0, 10))?.getFullYear() || 1000;
        const lastYear = parse(input.max?.slice(0, 10))?.getFullYear() || Math.max(today().getFullYear() + 100, month.getFullYear());
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
        previous.disabled = Boolean(input.min && format(new Date(month.getFullYear(), month.getMonth(), 0)) < input.min.slice(0, 10));
        next.disabled = Boolean(input.max && format(new Date(month.getFullYear(), month.getMonth() + 1, 1)) > input.max.slice(0, 10));
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
            item.setAttribute('aria-pressed', String(format(date) === (withTime ? draftDate : input.value)));
            if (format(date) === format(today())) item.setAttribute('aria-current', 'date');
            days.append(item);
        }
        const footer = document.createElement('div');
        footer.className = 'flight-date-footer';
        const current = button('Today', null, () => select(today()));
        current.disabled = !allowed(today());
        footer.append(button('Clear', null, () => select(null)), current);
        panel.append(header, days, footer);
        if (withTime) {
            const time = document.createElement('div');
            time.className = 'flight-date-time';
            const caption = document.createElement('span');
            caption.textContent = 'Time (24-hour)';
            const hour = document.createElement('input');
            const minute = document.createElement('input');
            [hour, minute].forEach(control => { control.type = 'number'; control.inputMode = 'numeric'; control.step = '1'; control.min = '0'; });
            hour.max = '23'; minute.max = '59'; hour.value = hours; minute.value = minutes;
            hour.setAttribute('aria-label', 'Hour (0–23)');
            minute.setAttribute('aria-label', 'Minute (0–59)');
            hour.addEventListener('input', () => { hours = hour.value; });
            minute.addEventListener('input', () => { minutes = minute.value; });
            const separator = document.createElement('span'); separator.textContent = ':';
            time.append(caption, hour, separator, minute);
            const error = document.createElement('small');
            error.className = 'flight-date-time-error'; error.setAttribute('role', 'status');
            const apply = () => {
                const validHour = /^\d{1,2}$/.test(hours) && Number(hours) <= 23;
                const validMinute = /^\d{1,2}$/.test(minutes) && Number(minutes) <= 59;
                if (!validHour || !validMinute) {
                    error.textContent = 'Enter an hour from 0–23 and minutes from 0–59.';
                    (!validHour ? hour : minute).focus(); return;
                }
                const value = `${draftDate}T${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
                if ((input.min && value < input.min) || (input.max && value > input.max)) {
                    error.textContent = 'Choose a date and time within the allowed range.'; return;
                }
                input.value = value;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                close(true);
            };
            [hour, minute].forEach(control => control.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); apply(); } }));
            const applyButton = button('Apply date and time', null, apply);
            applyButton.className = 'flight-date-apply';
            panel.append(time, error, applyButton);
        }
    };
    const open = () => {
        if (input.disabled) return;
        month = parse(input.value.slice(0, 10)) || today();
        if (input.min && format(month) < input.min.slice(0, 10)) month = parse(input.min.slice(0, 10));
        if (input.max && format(month) > input.max.slice(0, 10)) month = parse(input.max.slice(0, 10));
        if (withTime) {
            draftDate = format(month);
            [hours, minutes] = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(input.value) ? input.value.slice(11).split(':') : ['12', '00'];
        }
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
    input.form?.addEventListener('reset', () => close());
    });
})();
