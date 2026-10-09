(() => {
    const cards = [...document.querySelectorAll('[data-flight-id]')];
    if (!cards.length) return;
    let busy = false;
    let request = null;
    const refresh = async () => {
        if (busy || document.visibilityState === 'hidden') return;
        busy = true;
        request = new AbortController();
        const timeout = window.setTimeout(() => request?.abort(), 10000);
        try {
            // Batch large result sets to keep the existing authenticated endpoint bounded.
            const counts = new Map();
            for (let offset = 0; offset < cards.length; offset += 500) {
                const ids = cards.slice(offset, offset + 500).map(card => card.dataset.flightId).join(',');
                const response = await fetch(`/flights?seat_counts=1&ids=${encodeURIComponent(ids)}`, {
                    cache: 'no-store', credentials: 'same-origin', headers: { Accept: 'application/json' }, signal: request.signal
                });
                if (!response.ok || response.redirected) throw new Error('Availability unavailable');
                const data = await response.json();
                if (!Array.isArray(data.flights) || !data.flights.every(flight => Number.isSafeInteger(flight.id)
                    && flight.id > 0 && Number.isSafeInteger(flight.available_seats) && flight.available_seats >= 0)) {
                    throw new Error('Invalid availability');
                }
                data.flights.forEach(flight => counts.set(String(flight.id), flight.available_seats));
            }
            cards.forEach(card => {
                const count = counts.get(card.dataset.flightId) ?? 0;
                const text = `${count} ${count === 1 ? 'seat' : 'seats'} available`;
                const badge = card.querySelector('[data-seat-count]');
                if (badge.textContent !== text) badge.textContent = text;
                card.classList.toggle('flight-sold-out', count === 0);
            });
        } catch {
            // Preserve the last known counts on connection errors or session expiry.
        } finally {
            window.clearTimeout(timeout);
            request = null;
            busy = false;
        }
    };
    let timer = window.setInterval(refresh, 10000);
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') refresh(); });
    window.addEventListener('pageshow', () => {
        window.clearInterval(timer);
        timer = window.setInterval(refresh, 10000);
        refresh();
    });
    window.addEventListener('pagehide', () => { window.clearInterval(timer); request?.abort(); });
    refresh();
})();
