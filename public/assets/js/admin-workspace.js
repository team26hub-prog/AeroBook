(() => {
    'use strict';
    const normalize = value => String(value ?? '').toLocaleLowerCase().replace(/\s+/g, ' ').trim();
    const filterRecords = (records, query, status) => {
        const terms = normalize(query).split(' ').filter(Boolean);
        return records.filter(record => terms.every(term => normalize(record.text).includes(term)) &&
            (!status || (status === 'awaiting_review' ? ['pending', 'submitted'].includes(record.status) : record.status === status)));
    };
    const pageRecords = (records, page, size) => {
        const pages = size ? Math.max(1, Math.ceil(records.length / size)) : 1;
        const current = Math.min(Math.max(1, page), pages);
        return { rows: size ? records.slice((current - 1) * size, current * size) : records, page: current, pages };
    };
    const sortRecords = (records, column, descending) => [...records].sort((a, b) =>
        String(a.cells[column] ?? '').localeCompare(String(b.cells[column] ?? ''), undefined, { numeric: true, sensitivity: 'base' }) * (descending ? -1 : 1));
    if (typeof module !== 'undefined') module.exports = { filterRecords, pageRecords, sortRecords };
    if (typeof document === 'undefined') return;

    const create = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    };
    const option = (select, value, label) => { const node = create('option', '', label); node.value = value; select.append(node); };
    const feedback = document.getElementById('admin-action-feedback');
    const announce = message => { if (feedback) feedback.textContent = message; };
    const cellText = cell => {
        const copy = cell.cloneNode(true);
        copy.querySelectorAll('input,select,button,.admin-unsaved').forEach(node => node.remove());
        return [copy.textContent, ...[...cell.querySelectorAll('input:not([type=hidden]),select')].map(node => node.value)].join(' ');
    };
    document.querySelectorAll('[data-admin-table]').forEach((container, tableIndex) => {
        const table = container.querySelector('table');
        if (!table) return;
        const wrapper = container.classList.contains('table-wrap') ? container : container.querySelector('.table-wrap');
        const body = table.tBodies[0];
        const headings = [...table.querySelectorAll('thead th')];
        headings.forEach(heading => heading.scope = 'col');
        const records = [...body.rows].filter(row => !row.querySelector('[colspan]')).map((row, index) => {
            [...row.cells].forEach((cell, column) => {
                cell.dataset.label = headings[column]?.textContent.trim() || '';
                cell.querySelectorAll('input:not([type=hidden]),select').forEach(control => {
                    if (!control.hasAttribute('aria-label')) control.setAttribute('aria-label', `${control.name.replaceAll('_', ' ')} for record ${index + 1}`);
                    const fieldLabels={name:'Name',iata_code:'IATA',icao_code:'ICAO',city:'City',country:'Country',departure_at:'Departs',arrival_at:'Arrives'};
                    if (fieldLabels[control.name] && (cell.querySelectorAll('input:not([type=hidden])').length>1 || control.type==='datetime-local')) {
                        const label=create('label','admin-cell-field',fieldLabels[control.name]);control.before(label);label.append(control);
                    }
                });
            });
            return { row, status: row.dataset.rowStatus || '', text: '', cells: [] };
        });
        const originalEmpty = body.querySelector('td[colspan]')?.parentElement;
        const empty = create('tr', 'admin-filter-empty');
        const emptyCell = create('td', 'empty', 'No matching records. Try another search or clear the filters.');
        emptyCell.colSpan = headings.length; empty.append(emptyCell); empty.hidden = true; body.append(empty);
        const toolbar = create('div', 'admin-table-toolbar');
        const searchLabel = create('label', 'admin-search-label', 'Search records');
        const search = create('input'); search.type = 'search'; search.placeholder = 'Search names, codes, routes…'; search.autocomplete = 'off'; searchLabel.append(search);
        const statusLabel = create('label', '', 'Status'); const status = create('select'); statusLabel.append(status);
        option(status, '', 'All statuses');
        if (document.querySelector('.admin-section-payments')) option(status, 'awaiting_review', 'Awaiting review');
        const section=document.querySelector('.admin-shell')?.className.match(/admin-section-(\w+)/)?.[1];
        const statuses={airlines:['active','inactive'],airports:['active','inactive'],flights:['scheduled','boarding','departed','completed','cancelled'],bookings:['pending','confirmed','cancelled','completed','expired'],payments:['pending','submitted','verified','rejected']};
        [...new Set([...(statuses[section] || []),...records.map(record => record.status).filter(Boolean)])].sort().forEach(value => option(status, value, value.charAt(0).toUpperCase() + value.slice(1).replaceAll('_', ' ')));
        statusLabel.hidden = status.options.length === 1;
        const sortLabel = create('label', '', 'Sort by'); const sort = create('select'); sortLabel.append(sort); option(sort, '', 'Original order');
        headings.forEach((heading, index) => { if (!/actions|save|update|review/i.test(heading.textContent)) { option(sort, `${index}:asc`, `${heading.textContent.trim()} ↑`); option(sort, `${index}:desc`, `${heading.textContent.trim()} ↓`); } });
        const clear = create('button', 'admin-reset', 'Clear filters'); clear.type = 'button';
        const viewOptions=create('details','admin-table-options');
        viewOptions.append(create('summary','','Sort options'),sortLabel);
        toolbar.append(searchLabel, statusLabel, viewOptions, clear); wrapper.before(toolbar);
        const footer = create('div', 'admin-table-footer'); const count = create('p'); count.setAttribute('role', 'status'); count.setAttribute('aria-live', 'polite');
        const pageControls = create('div', 'admin-pagination'); const sizeLabel = create('label', '', 'Rows'); const size = create('select');
        [10, 25, 50, 0].forEach(value => option(size, String(value), value ? String(value) : 'All')); sizeLabel.append(size);
        const previous = create('button', 'admin-page-button', 'Previous'); previous.type = 'button'; const next = create('button', 'admin-page-button', 'Next'); next.type = 'button';
        const pageLabel = create('span'); pageControls.append(sizeLabel, previous, pageLabel, next); footer.append(count, pageControls); wrapper.after(footer);
        pageControls.setAttribute('aria-label','Record pagination');
        let page = 1;
        const params = new URLSearchParams(window.location.search);
        if (!tableIndex) { search.value = params.get('q') || ''; const initialStatus=params.get('status'); if ([...status.options].some(item => item.value === initialStatus)) status.value = initialStatus; }
        const update = (resetPage = true, sync = true) => {
            if (resetPage) page = 1;
            records.forEach(record => { record.cells = [...record.row.cells].map(cellText); record.text = record.cells.join(' '); });
            let matching = filterRecords(records, search.value, status.value);
            if (sort.value) { const [column, direction] = sort.value.split(':'); matching = sortRecords(matching, Number(column), direction === 'desc'); }
            const result = pageRecords(matching, page, Number(size.value)); page = result.page;
            records.forEach(record => record.row.hidden = true);
            result.rows.forEach(record => { record.row.hidden = false; body.append(record.row); });
            if (originalEmpty) originalEmpty.hidden = records.length > 0 || Boolean(search.value || status.value);
            empty.hidden = matching.length > 0 || (!records.length && !search.value && !status.value);
            const start = matching.length ? (Number(size.value) ? (page - 1) * Number(size.value) + 1 : 1) : 0;
            count.textContent = `Showing ${start}–${matching.length ? start + result.rows.length - 1 : 0} of ${matching.length} records${matching.length !== records.length ? ` (${records.length} total)` : ''}`;
            pageLabel.textContent = `Page ${page} of ${result.pages}`; previous.disabled = page <= 1; next.disabled = page >= result.pages;
            if (!tableIndex && sync) {
                const url = new URL(window.location.href);
                search.value ? url.searchParams.set('q', search.value) : url.searchParams.delete('q');
                status.value ? url.searchParams.set('status', status.value) : url.searchParams.delete('status');
                try { history.replaceState(history.state, '', url); } catch { /* Filtering still works when history is unavailable. */ }
            }
        };
        search.addEventListener('input', () => update()); status.addEventListener('change', () => update()); sort.addEventListener('change', () => update()); size.addEventListener('change', () => update());
        clear.addEventListener('click', () => { search.value='';status.value='';sort.value='';update();search.focus(); });
        previous.addEventListener('click', () => { page--;update(false); }); next.addEventListener('click', () => { page++;update(false); });
        table.addEventListener('input', event => {
            const row = event.target.closest('tr'); if (!row || event.target.type === 'hidden') return;
            const controls = [...row.querySelectorAll('input:not([type=hidden]),select')];
            const dirty = controls.some(control => control.tagName === 'SELECT' ? control.value !== ([...control.options].find(item => item.defaultSelected)?.value ?? control.options[0]?.value) : control.value !== control.defaultValue);
            row.classList.toggle('admin-row-dirty', dirty); const note=row.querySelector('.admin-unsaved');if(note)note.textContent=dirty?'Unsaved changes':'';
        });
        update(true, false);
        const searchHints={airlines:'Search airline name or code…',airports:'Search airport, city, or code…',flights:'Search flight, airline, or route…',seats:'Search flight or route…',bookings:'Search PNR, customer, or passenger…',payments:'Search PNR, sender, or reference…'};
        search.placeholder=searchHints[section] || 'Search PNR, customer, or flight…';
    });

    document.querySelectorAll('.admin-create form').forEach(form => {
        const actions = create('div', 'admin-form-actions');
        const submit = form.querySelector('button:not([type=reset])'); if (submit) { submit.type='submit';submit.before(actions);actions.append(submit); }
        if (!form.querySelector('button[type=reset]')) { const reset=create('button','admin-reset','Clear form');reset.type='reset';actions.append(reset); }
        form.querySelectorAll('input[required]').forEach(input => {
            const label = input.closest('label'); if(label) { const mark=create('span','admin-required',' *');input.before(mark); }
        });
        if (form.querySelector('input[name=rows]')) {
            const preview=create('span','admin-form-hint');preview.setAttribute('role','status');actions.append(preview);
            const updatePreview=()=>{ const letters=form.querySelectorAll('input[name="letters[]"]:checked').length;const rows=Number(form.querySelector('input[name=rows]').value);preview.textContent=rows>0&&letters?`${rows * letters} seat positions (${rows} rows × ${letters} letters). Existing seats are preserved.`:'Choose rows and at least one seat letter.'; };
            form.addEventListener('input',updatePreview);form.addEventListener('reset',()=>setTimeout(updatePreview,0));updatePreview();
        }
    });
    document.querySelectorAll('a[href="#admin-create"]').forEach(link => link.addEventListener('click',()=>setTimeout(()=>document.querySelector('#admin-create input:not([type=hidden]),#admin-create select')?.focus({preventScroll:true}),0)));
    if (window.location.hash === '#admin-create') document.querySelector('#admin-create input:not([type=hidden]),#admin-create select')?.focus({preventScroll:true});
    document.addEventListener('submit', event => {
        const form=event.target;if(!form.matches('.admin-content form'))return;
        setTimeout(()=>{if(event.defaultPrevented)return;form.setAttribute('aria-busy','true');const button=event.submitter;if(button){button.dataset.adminOriginalText=button.textContent;button.textContent=button.value==='verified'?'Verifying…':button.value==='rejected'?'Rejecting…':'Saving…';}announce('Submitting your changes. Please wait for confirmation.');},0);
    });
    window.addEventListener('pageshow',()=>{document.querySelectorAll('[data-admin-original-text]').forEach(button=>{button.textContent=button.dataset.adminOriginalText;button.disabled=false;button.removeAttribute('aria-busy');});document.querySelectorAll('.admin-content form[aria-busy]').forEach(form=>form.removeAttribute('aria-busy'));});
    // Keep server feedback available after the shared toast has been displayed.
    document.querySelectorAll('[data-admin-feedback]').forEach(node=>{setTimeout(()=>node.hidden=false,0);});

    const toggle=document.querySelector('.admin-menu-toggle'); const sidebar=document.getElementById('admin-sidebar');
    if (!toggle || !sidebar) return;
    const backdrop=create('button','admin-backdrop');backdrop.type='button';backdrop.setAttribute('aria-label','Close admin navigation');backdrop.tabIndex=-1;sidebar.before(backdrop);
    const close=create('button','admin-sidebar-close','Close');close.type='button';close.setAttribute('aria-label','Close admin navigation');sidebar.querySelector('.admin-brand').after(close);
    const setOpen=open=>{document.body.classList.toggle('admin-nav-open',open);toggle.setAttribute('aria-expanded',String(open));toggle.setAttribute('aria-label',open?'Close admin navigation':'Open admin navigation');document.body.style.overflow=open?'hidden':'';if(open)close.focus();};
    toggle.addEventListener('click',()=>setOpen(!document.body.classList.contains('admin-nav-open')));close.addEventListener('click',()=>{setOpen(false);toggle.focus();});backdrop.addEventListener('click',()=>{setOpen(false);toggle.focus();});
    sidebar.querySelectorAll('nav a').forEach(link=>link.addEventListener('click',()=>setOpen(false)));
    document.addEventListener('keydown',event=>{
        if(!document.body.classList.contains('admin-nav-open'))return;
        if(event.key==='Escape'){setOpen(false);toggle.focus();}
        if(event.key==='Tab'){const links=[...sidebar.querySelectorAll('a,button,input:not([type=hidden])')].filter(node=>!node.disabled);const first=links[0],last=links.at(-1);if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}}
    });
    window.addEventListener('resize',()=>{if(window.innerWidth>820)setOpen(false);});
})();
