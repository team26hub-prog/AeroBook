(() => {
    const source = document.getElementById('dashboard-chart-data');
    if (!source) return;
    const filter = document.getElementById('booking-trend-period');
    const refreshButton = document.getElementById('refresh-dashboard-charts');
    const status = document.getElementById('dashboard-refresh-status');
    const instances = new Map();
    const palettes = {
        revenue: ['#238b83', '#597f97', '#7068ad', '#c18a32', '#3685b5', '#4b9369'],
        bookingStatuses: ['#c18a32', '#238b83', '#c65c66', '#597f97', '#8693a4'],
        paymentStatuses: ['#c18a32', '#3685b5', '#238b83', '#c65c66', '#7068ad', '#8693a4']
    };
    const money = value => `PKR ${Number(value).toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let data = null;
    let busy = false;
    let request = null;
    const validSeries = series => series && Array.isArray(series.labels) && Array.isArray(series.values)
        && series.labels.length === series.values.length && series.values.every(value => Number.isFinite(value) && value >= 0);
    const validData = value => value && ['daily', 'weekly', 'monthly'].every(period => validSeries(value.trends?.[period]))
        && ['revenue', 'bookingStatuses', 'paymentStatuses'].every(key => validSeries(value[key]));

    const table = (card, series, currency) => {
        const body = card.querySelector('[data-chart-table]');
        body.replaceChildren();
        series.labels.forEach((label, index) => {
            const row = document.createElement('tr');
            [String(label), currency ? money(series.values[index]) : String(series.values[index])].forEach(text => {
                const cell = document.createElement('td');
                cell.textContent = text;
                row.appendChild(cell);
            });
            body.appendChild(row);
        });
        if (!series.labels.length) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 2;
            cell.textContent = 'No data available.';
            row.appendChild(cell);
            body.appendChild(row);
        }
    };

    const draw = (key, series) => {
        const card = document.querySelector(`[data-dashboard-chart="${key}"]`);
        const canvas = document.getElementById(`chart-${key}`);
        const empty = card.querySelector('[data-chart-empty]');
        const hasData = series.values.some(value => value > 0);
        table(card, series, key === 'revenue');
        canvas.hidden = !hasData || !window.Chart;
        empty.hidden = hasData && !!window.Chart;
        empty.textContent = hasData ? 'Charts could not load. View the data table below.' : empty.dataset.emptyMessage;
        if (canvas.hidden) {
            instances.get(key)?.destroy();
            instances.delete(key);
            return;
        }
        const type = key === 'trends' ? 'line' : key === 'bookingStatuses' ? 'doughnut' : 'bar';
        const horizontal = false;
        const currency = key === 'revenue';
        const metric = currency ? 'Verified revenue' : key === 'paymentStatuses' ? 'Payments' : 'Bookings';
        const dataset = {
            label: metric, data: series.values,
            backgroundColor: type === 'line' ? 'rgba(112,104,173,.14)' : series.values.map((_, index) => palettes[key][index % palettes[key].length]),
            borderColor: type === 'line' ? '#7068ad' : '#fff',
            borderWidth: type === 'line' ? 2 : type === 'doughnut' ? 2 : 0,
            borderRadius: type === 'bar' ? 5 : 0,
            maxBarThickness: 42,
            fill: type === 'line', tension: .25, pointRadius: 3,
            pointBackgroundColor: '#238b83', pointBorderColor: '#fff', pointBorderWidth: 1,
            pointHoverRadius: 5
        };
        const chartData = { labels: series.labels, datasets: [dataset] };
        if (instances.has(key)) {
            const chart = instances.get(key);
            chart.data = chartData;
            chart.update('none');
            return;
        }
        const options = {
            responsive: true, maintainAspectRatio: false,
            animation: reducedMotion ? false : { duration: 300 },
            indexAxis: horizontal ? 'y' : 'x',
            plugins: {
                legend: { display: type === 'doughnut', position: 'bottom', labels: { color: '#183251', usePointStyle: true, padding: 16, font: { size: 11 } } },
                tooltip: { backgroundColor: '#183251', callbacks: { label: context => `${context.label}: ${currency ? money(context.raw) : context.raw}` } }
            }
        };
        if (type === 'doughnut') options.cutout = '65%';
        else {
            const valueAxis = horizontal ? 'x' : 'y';
            const categoryAxis = horizontal ? 'y' : 'x';
            options.scales = {
                [valueAxis]: { beginAtZero: true, grid: { color: '#e8eef2' }, ticks: { color: '#687b8b', maxTicksLimit: 6, ...(currency ? { callback: value => `PKR ${Number(value).toLocaleString('en-PK', { notation: 'compact' })}` } : { precision: 0 }) } },
                [categoryAxis]: { grid: { display: false }, ticks: { color: '#687b8b', maxRotation: 0, autoSkip: !horizontal, maxTicksLimit: 10, font: { size: 11 } } }
            };
        }
        instances.set(key, new window.Chart(canvas, { type, data: chartData, options }));
    };

    const render = () => {
        if (!data) return;
        const period = Object.hasOwn(data.trends, filter.value) ? filter.value : 'daily';
        const description = document.querySelector('[data-dashboard-chart="trends"] [data-chart-description]');
        description.textContent = { daily: 'Bookings created from 8 October 2026 onward.', weekly: 'Bookings by week · Last 12 weeks · Weeks start Monday.', monthly: 'Bookings by month · Last 12 months.' }[period];
        draw('trends', data.trends[period]);
        ['revenue', 'bookingStatuses', 'paymentStatuses'].forEach(key => draw(key, data[key]));
    };
    const showUpdated = () => {
        const updated = new Date(data.updatedAt).toLocaleTimeString('en-PK', { timeZone: data.timezone || 'Asia/Karachi', hour: '2-digit', minute: '2-digit', second: '2-digit' });
        status.textContent = `Updated ${updated} · Refreshes every 30 seconds.`;
    };
    const refresh = async () => {
        if (busy || document.visibilityState === 'hidden') return;
        busy = true;
        refreshButton.disabled = true;
        status.textContent = 'Refreshing chart data…';
        request = new AbortController();
        const timeout = window.setTimeout(() => request?.abort(), 10000);
        try {
            const response = await fetch('/admin?dashboard_data=1', { cache: 'no-store', credentials: 'same-origin', headers: { Accept: 'application/json' }, signal: request.signal });
            if (!response.ok || response.redirected) throw new Error('Refresh unavailable');
            const fresh = await response.json();
            if (!validData(fresh)) throw new Error('Invalid chart data');
            data = fresh;
            render();
            showUpdated();
        } catch {
            status.textContent = data ? 'Could not refresh. Showing the last successful update; retry or sign in again.' : 'Chart data is temporarily unavailable. Try refreshing.';
        } finally {
            window.clearTimeout(timeout);
            request = null;
            busy = false;
            refreshButton.disabled = false;
        }
    };
    try {
        const initial = JSON.parse(source.textContent);
        if (validData(initial)) { data = initial; render(); showUpdated(); }
    } catch {
        status.textContent = 'Chart data is temporarily unavailable. Try refreshing.';
    }
    filter.addEventListener('change', render);
    refreshButton.addEventListener('click', refresh);
    const timer = window.setInterval(refresh, 30000);
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') refresh(); });
    window.addEventListener('pagehide', () => { window.clearInterval(timer); request?.abort(); });
})();
