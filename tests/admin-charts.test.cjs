const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { test } = require('node:test');
const script = fs.readFileSync(require('node:path').join(__dirname, '../public/assets/js/admin-charts.js'), 'utf8');
const series = (values = [1, 2]) => ({ labels: values.map((_, index) => `Label ${index}`), values });
const payload = () => ({ trends: { daily: series(), weekly: series([3]), monthly: series([7]) }, revenue: series([100.25, 200]), bookingStatuses: series([2, 3]), paymentStatuses: series([1, 4]), updatedAt: '2026-10-09T12:00:00+05:00', timezone: 'Asia/Karachi' });

function setup(data, { library = true, response = null } = {}) {
    const charts = [];
    const events = {};
    const keys = ['trends', 'revenue', 'bookingStatuses', 'paymentStatuses'];
    const element = () => ({ children: [], textContent: '', appendChild(child) { this.children.push(child); }, replaceChildren() { this.children = []; } });
    const cards = Object.fromEntries(keys.map(key => {
        const body = element();
        const empty = { hidden: false, dataset: { emptyMessage: `No ${key} data` } };
        return [key, { body, empty, querySelector: selector => selector === '[data-chart-table]' ? body : empty }];
    }));
    const filter = { value: 'daily', addEventListener: (name, fn) => { events.filter = fn; } };
    const button = { disabled: false, addEventListener: (name, fn) => { events.refresh = fn; } };
    const status = element();
    const description = element();
    const nodes = { 'dashboard-chart-data': { textContent: JSON.stringify(data) }, 'booking-trend-period': filter, 'refresh-dashboard-charts': button, 'dashboard-refresh-status': status };
    keys.forEach(key => { nodes[`chart-${key}`] = { hidden: true, key }; });
    const document = {
        visibilityState: 'visible', getElementById: id => nodes[id], createElement: element,
        querySelector: selector => selector.includes('data-chart-description') ? description : cards[selector.match(/"(\w+)"/)[1]],
        addEventListener: (name, fn) => { events[name] = fn; }
    };
    class Chart {
        constructor(canvas, config) { this.key = canvas.key; this.config = config; this.data = config.data; this.updates = 0; charts.push(this); }
        update() { this.updates++; }
        destroy() { this.destroyed = true; }
    }
    vm.runInNewContext(script, {
        document, AbortController,
        fetch: async () => response || { ok: false },
        window: {
            ...(library ? { Chart } : {}), matchMedia: () => ({ matches: true }),
            setTimeout: () => 1, clearTimeout() {}, setInterval: fn => { events.timer = fn; return 1; }, clearInterval() {},
            addEventListener: (name, fn) => { events[name] = fn; }
        }
    });
    return { charts, cards, filter, nodes, events, status, button, document };
}

test('creates four colorful responsive charts', () => {
    const ui = setup(payload());
    assert.equal(ui.charts.length, 4);
    assert.deepEqual(ui.charts.map(chart => chart.config.type), ['line', 'bar', 'doughnut', 'bar']);
    assert.ok(ui.charts.slice(1).every(chart => new Set(chart.data.datasets[0].backgroundColor).size > 1));
    assert.equal(ui.charts[0].data.datasets[0].borderColor, '#7068ad');
    assert.equal(ui.charts[1].config.options.plugins.tooltip.callbacks.label({ label: 'October', raw: 100.25 }), 'October: PKR 100.25');
    assert.ok(ui.charts.every(chart => chart.config.options.responsive && !chart.config.options.maintainAspectRatio));
});

test('weekly and monthly filters update the existing line chart', () => {
    const ui = setup(payload());
    ui.filter.value = 'weekly';
    ui.events.filter();
    assert.equal(ui.charts[0].data.datasets[0].data[0], 3);
    ui.filter.value = 'monthly';
    ui.events.filter();
    assert.equal(ui.charts[0].data.datasets[0].data[0], 7);
    assert.equal(ui.charts.length, 4);
});

test('empty series show empty states without creating misleading charts', () => {
    const data = payload();
    for (const key of ['daily', 'weekly', 'monthly']) data.trends[key] = series([0, 0]);
    for (const key of ['revenue', 'bookingStatuses', 'paymentStatuses']) data[key] = series([0, 0]);
    const ui = setup(data);
    assert.equal(ui.charts.length, 0);
    assert.ok(Object.values(ui.cards).every(card => !card.empty.hidden));
});

test('data tables remain usable when Chart.js cannot load', () => {
    const ui = setup(payload(), { library: false });
    assert.equal(ui.charts.length, 0);
    assert.equal(ui.cards.revenue.body.children.length, 2);
    assert.match(ui.cards.revenue.empty.textContent, /View the data table/);
});

test('successful refresh updates data while preserving the selected period', async () => {
    const fresh = payload(); fresh.trends.weekly = series([99]);
    const ui = setup(payload(), { response: { ok: true, redirected: false, json: async () => fresh } });
    ui.filter.value = 'weekly';
    await ui.events.refresh();
    assert.equal(ui.charts[0].data.datasets[0].data[0], 99);
    assert.equal(ui.button.disabled, false);
});

test('failed refresh preserves the last successful charts', async () => {
    const ui = setup(payload());
    await ui.events.refresh();
    assert.equal(ui.charts[0].data.datasets[0].data[0], 1);
    assert.match(ui.status.textContent, /last successful update/);
    assert.equal(ui.button.disabled, false);
});

test('redirected login responses never replace chart data', async () => {
    const ui = setup(payload(), { response: { ok: true, redirected: true } });
    await ui.events.refresh();
    assert.equal(ui.charts[0].data.datasets[0].data[0], 1);
    assert.match(ui.status.textContent, /sign in again/);
});

test('automatic refresh pauses in hidden tabs', async () => {
    const ui = setup(payload());
    ui.document.visibilityState = 'hidden';
    const text = ui.status.textContent;
    await ui.events.timer();
    assert.equal(ui.status.textContent, text);
});
