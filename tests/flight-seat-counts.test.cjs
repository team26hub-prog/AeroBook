const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { test } = require('node:test');
const script = fs.readFileSync(require('node:path').join(__dirname, '../public/assets/js/flight-availability.js'), 'utf8');
async function setup(response) {
    const events = {};
    const badge = { textContent: '60 seats available' };
    const card = { dataset: { flightId: '1' }, querySelector: () => badge, classList: { toggle: (_, value) => { card.soldOut = value; } } };
    const document = { visibilityState: 'visible', querySelectorAll: () => [card], addEventListener: (name, fn) => { events[name] = fn; } };
    let calls = 0;
    vm.runInNewContext(script, { document, AbortController, encodeURIComponent, fetch: async () => { calls++; return response; }, window: {
        setInterval: fn => { events.timer = fn; return 1; }, clearInterval() {}, setTimeout: () => 1, clearTimeout() {},
        addEventListener: (name, fn) => { events[name] = fn; }
    } });
    await new Promise(resolve => setImmediate(resolve));
    return { badge, card, document, events, calls: () => calls };
}
const response = flights => ({ ok: true, redirected: false, json: async () => ({ flights }) });
test('refreshes a reserved-seat count without reloading the card', async () => {
    const ui = await setup(response([{ id: 1, available_seats: 59 }]));
    assert.equal(ui.badge.textContent, '59 seats available');
    assert.equal(ui.card.soldOut, false);
});
test('marks a sold-out flight and handles a no-longer-eligible flight', async () => {
    for (const flights of [[{ id: 1, available_seats: 0 }], []]) {
        const ui = await setup(response(flights));
        assert.equal(ui.badge.textContent, '0 seats available');
        assert.equal(ui.card.soldOut, true);
    }
});
test('uses the singular label for the last seat', async () => {
    const ui = await setup(response([{ id: 1, available_seats: 1 }]));
    assert.equal(ui.badge.textContent, '1 seat available');
});
test('preserves counts on failed, redirected and invalid responses', async () => {
    for (const result of [{ ok: false }, { ok: true, redirected: true }, response([{ id: 1, available_seats: -1 }])]) {
        const ui = await setup(result);
        assert.equal(ui.badge.textContent, '60 seats available');
    }
});
test('pauses in hidden tabs and refreshes when visible', async () => {
    const ui = await setup(response([{ id: 1, available_seats: 58 }]));
    ui.document.visibilityState = 'hidden';
    await ui.events.timer();
    assert.equal(ui.calls(), 1);
    ui.document.visibilityState = 'visible';
    await ui.events.visibilitychange();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(ui.calls(), 2);
});
