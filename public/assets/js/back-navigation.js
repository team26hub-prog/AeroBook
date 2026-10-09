(() => {
    'use strict';
    const key = 'aerobook.page-history.v1';
    const body = document.body;
    const button = document.querySelector('[data-page-back]');
    const role = body.dataset.backRole || 'guest';
    const identity = role + ':' + (body.dataset.backUser || '0');
    const bookingActive = body.dataset.backBookingActive === '1';
    const customer = new Set(['/account', '/flights', '/flights/details', '/booking/passengers', '/booking/confirmation', '/seat-selection', '/payments']);
    const admin = new Set(['/admin', '/admin/airlines', '/admin/airports', '/admin/flights', '/admin/seats', '/admin/bookings', '/admin/payments']);
    function safe(raw) {
        if (typeof raw !== 'string' || raw.length > 2048) return null;
        try {
            const url = new URL(raw, window.location.origin);
            if (url.origin !== window.location.origin || url.username || url.password) return null;
            const path = url.pathname.replace(/\/+$/, '') || '/';
            if (path !== '/' && !(role === 'guest' && ['/login', '/register'].includes(path)) &&
                !(role === 'customer' && customer.has(path)) && !(role === 'admin' && admin.has(path))) return null;
            if (path === '/booking/passengers' && !bookingActive) return null;
            if (url.searchParams.has('seat_counts') || url.searchParams.has('dashboard_data')) return null;
            return path + url.search;
        } catch (_) { return null; }
    }
    // POST responses never become Back destinations: retain the preceding GET form instead.
    const current = body.dataset.backMethod === 'POST' ? null : safe(window.location.href);
    const fallback = safe(button?.getAttribute('href')) || '/';
    let memory = { identity, entries: [], pending: null };
    function read() {
        try {
            const data = JSON.parse(window.sessionStorage.getItem(key));
            if (data && Array.isArray(data.entries)) memory = data;
        } catch (_) { /* Private browsing or malformed storage: use this page's fallback. */ }
        const changed = memory.identity !== identity;
        memory.entries = memory.entries.filter(entry => entry && typeof entry.id === 'string' && safe(entry.url) && (!changed || safe(entry.url) === '/')).map(entry => ({ id: entry.id, url: safe(entry.url) })).slice(-50);
        memory.identity = identity;
        if (changed || !safe(memory.pending)) memory.pending = null;
        return memory;
    }
    function write(data) {
        memory = data;
        try { window.sessionStorage.setItem(key, JSON.stringify(data)); } catch (_) {}
    }
    function activate() {
        const data = read();
        // A target can redirect after its booking/session expires. Do not offer it again.
        if (data.pending && data.pending !== current) data.entries = data.entries.filter(entry => entry.url !== data.pending);
        const stateId = window.history.state?.aerobookBackEntry;
        const index = data.entries.findIndex(entry => entry.id === stateId && entry.url === current);
        if (index >= 0) data.entries = data.entries.slice(0, index + 1);
        if (current) {
            const last = data.entries.at(-1);
            if (!last || last.url !== current) data.entries.push({ url: current, id: Date.now() + '-' + Math.random().toString(36).slice(2) });
            data.entries = data.entries.slice(-50);
            try { window.history.replaceState({ ...window.history.state, aerobookBackEntry: data.entries.at(-1).id }, ''); } catch (_) {}
        }
        data.pending = null;
        write(data);
        update();
    }
    function target(data) {
        return [...data.entries].reverse().find(entry => safe(entry.url) && entry.url !== current)?.url || fallback;
    }
    function update() { if (button) button.setAttribute('href', target(read())); }
    if (button) button.addEventListener('click', event => {
        if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const data = read();
        const destination = target(data);
        if (!destination || destination === current) { event.preventDefault(); return; }
        // Consume the current entry rather than adding A -> B -> A loops on each Back click.
        while (data.entries.length && data.entries.at(-1).url !== destination) data.entries.pop();
        data.pending = destination;
        write(data);
        event.preventDefault();
        window.location.assign(destination);
    });
    activate();
    window.addEventListener('pageshow', event => { if (event.persisted) activate(); });
})();
