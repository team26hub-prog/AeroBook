(() => {
    document.querySelectorAll('.admin-section-flights .admin-create select').forEach((select, index) => {
        const label = select.closest('label');
        const caption = [...label.childNodes].filter(node => node.nodeType === 3).map(node => node.textContent.trim()).join(' ');
        const wrapper = document.createElement('div');
        wrapper.className = 'admin-flight-select';
        select.before(wrapper);
        wrapper.append(select);
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.id = `admin-flight-select-${index}`;
        toggle.className = 'admin-flight-select-toggle';
        toggle.setAttribute('role', 'combobox');
        toggle.setAttribute('aria-haspopup', 'listbox');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-autocomplete', 'none');
        const text = document.createElement('span');
        toggle.append(text);
        const list = document.createElement('div');
        list.className = 'admin-flight-select-options';
        list.id = `${toggle.id}-options`;
        list.hidden = true;
        list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', caption);
        toggle.setAttribute('aria-controls', list.id);
        label.setAttribute('for', toggle.id);
        wrapper.append(toggle, list);
        const options = [...select.options].map((option, position) => {
            const item = document.createElement('div');
            item.id = `${list.id}-${position}`;
            item.className = 'admin-flight-select-option';
            item.textContent = option.textContent;
            item.setAttribute('role', 'option');
            item.setAttribute('aria-disabled', String(option.disabled));
            list.append(item);
            return { option, item };
        });
        let active = -1;
        let search = '';
        let lastKey = 0;
        const available = () => options.map((entry, position) => entry.option.disabled ? -1 : position).filter(position => position >= 0);
        const highlight = position => {
            active = position;
            options.forEach(({ item }, i) => item.classList.toggle('is-active', i === active));
            if (active >= 0) {
                toggle.setAttribute('aria-activedescendant', options[active].item.id);
                options[active].item.scrollIntoView({ block: 'nearest' });
            }
        };
        const close = () => {
            list.hidden = true;
            wrapper.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.removeAttribute('aria-activedescendant');
            search = '';
        };
        const sync = () => {
            const selected = options.find(({ option }) => option.selected);
            text.textContent = selected?.option.textContent || 'Choose an option';
            toggle.setAttribute('aria-label', `${caption}: ${text.textContent}`);
            toggle.setAttribute('aria-required', String(select.required));
            toggle.disabled = select.disabled;
            for (const attribute of ['aria-invalid', 'aria-describedby']) {
                const value = select.getAttribute(attribute);
                if (value !== null) toggle.setAttribute(attribute, value);
                else toggle.removeAttribute(attribute);
            }
            options.forEach(({ option, item }) => item.setAttribute('aria-selected', String(option.selected)));
        };
        const open = () => {
            if (select.disabled) return;
            document.dispatchEvent(new Event('admin-flight-select-open'));
            list.hidden = false;
            wrapper.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            const selected = options.findIndex(({ option }) => option.selected && !option.disabled);
            highlight(selected >= 0 ? selected : (available()[0] ?? -1));
        };
        const choose = position => {
            if (position < 0 || options[position].option.disabled) return;
            select.value = options[position].option.value;
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
            sync(); close(); toggle.focus();
        };
        toggle.addEventListener('click', () => list.hidden ? open() : close());
        toggle.addEventListener('keydown', event => {
            if (event.key === 'Escape' || event.key === 'Tab') { close(); return; }
            if (['Enter', ' '].includes(event.key)) {
                event.preventDefault();
                if (list.hidden) open(); else choose(active);
                return;
            }
            if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
                event.preventDefault();
                const wasClosed = list.hidden;
                if (wasClosed) open();
                const allowed = available();
                if (!allowed.length) return;
                if (event.key === 'Home') highlight(allowed[0]);
                else if (event.key === 'End') highlight(allowed.at(-1));
                else if (!wasClosed) highlight(allowed[(allowed.indexOf(active) + (event.key === 'ArrowDown' ? 1 : -1) + allowed.length) % allowed.length]);
                return;
            }
            if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
                event.preventDefault();
                if (list.hidden) open();
                const now = performance.now();
                search = now - lastKey > 700 ? event.key : search + event.key;
                lastKey = now;
                const match = available().find(position => options[position].option.textContent.trim().toLowerCase().startsWith(search.toLowerCase()));
                if (match !== undefined) highlight(match);
            }
        });
        options.forEach(({ item }, position) => item.addEventListener('click', event => { event.preventDefault(); choose(position); }));
        list.addEventListener('pointerdown', event => event.preventDefault());
        select.addEventListener('change', sync);
        select.addEventListener('focus', () => toggle.focus());
        toggle.addEventListener('blur', () => select.dispatchEvent(new Event('blur')));
        wrapper.addEventListener('focusout', event => { if (!wrapper.contains(event.relatedTarget)) close(); });
        document.addEventListener('pointerdown', event => { if (!wrapper.contains(event.target)) close(); });
        document.addEventListener('admin-flight-select-open', close);
        select.form?.addEventListener('reset', () => setTimeout(() => { sync(); close(); }, 0));
        new MutationObserver(sync).observe(select, { attributes: true, attributeFilter: ['aria-invalid', 'aria-describedby', 'disabled', 'required'] });
        select.classList.add('admin-flight-native-select');
        select.tabIndex = -1;
        sync();
    });
})();
