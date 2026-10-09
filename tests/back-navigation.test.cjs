const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { test } = require('node:test');
const script = fs.readFileSync(require('node:path').join(__dirname, '../public/assets/js/back-navigation.js'), 'utf8');
const key = 'aerobook.page-history.v1';
function visit(path, storage = new Map(), options = {}) {
    const events = {};
    const attributes = { href: options.fallback || '/account' };
    const button = options.home ? null : {
        getAttribute: name => attributes[name], setAttribute: (name, value) => attributes[name] = value,
        addEventListener: (name, fn) => events[name] = fn
    };
    let assigned = null;
    const history = { state: options.state || null, replaceState: value => history.state = value };
    const window = {
        location: { origin: 'https://aerobook.test', href: 'https://aerobook.test' + path, assign: value => assigned = value },
        history, sessionStorage: { getItem: name => options.blocked ? (() => { throw Error('blocked'); })() : storage.get(name) || null,
            setItem: (name, value) => { if(options.blocked) throw Error('blocked'); storage.set(name, value); } },
        addEventListener: (name, fn) => events[name] = fn
    };
    const document = { body: { dataset: { backRole: options.role || 'customer', backUser: options.user || '1', backBookingActive: options.active ? '1' : '0', backMethod: options.method || 'GET' } }, querySelector: () => button };
    vm.runInNewContext(script, { window, document, URL });
    return { href: () => attributes.href, state: () => history.state, back: () => { events.click({ button: 0, preventDefault() {} }); return assigned; }, pageshow: () => events.pageshow({ persisted: true }), entries: () => JSON.parse(storage.get(key)).entries };
}
test('returns to the actual prior page including query parameters', () => {
    const store = new Map(); visit('/flights?departure_id=1&arrival_id=2&date=2026-10-20', store);
    const page = visit('/flights/details?id=10', store);
    assert.equal(page.back(), '/flights?departure_id=1&arrival_id=2&date=2026-10-20');
});
test('repeated Back consumes history without a two-page loop', () => {
    const store = new Map(); visit('/', store, { home: true }); visit('/flights', store); visit('/flights/details?id=10', store);
    assert.equal(visit('/seat-selection', store).back(), '/flights/details?id=10');
    assert.equal(visit('/flights/details?id=10', store).back(), '/flights');
    assert.equal(visit('/flights', store).back(), '/');
});
test('refresh does not introduce duplicate visits', () => {
    const store = new Map(); visit('/flights', store); const details = visit('/flights/details?id=1', store);
    const refresh = visit('/flights/details?id=1', store, { state: details.state() });
    assert.equal(refresh.entries().length, 2); assert.equal(refresh.back(), '/flights');
});
test('POST review returns to passenger form using GET rather than resubmitting POST', () => {
    const store = new Map(); visit('/flights', store, { active: true }); visit('/booking/passengers', store, { active: true });
    assert.equal(visit('/booking/review', store, { active: true, method: 'POST' }).back(), '/booking/passengers');
});
test('confirmation skips expired passenger and POST review pages', () => {
    const store = new Map(); visit('/flights', store); visit('/booking/passengers', store, { active: true }); visit('/booking/review', store, { active: true, method: 'POST' });
    const page = visit('/booking/confirmation?id=1', store); assert.equal(page.back(), '/flights');
});
test('seat and payment pages preserve the booking identifier', () => {
    const store = new Map(); visit('/seat-selection?booking_id=22', store);
    assert.equal(visit('/payments?booking_id=22', store).back(), '/seat-selection?booking_id=22');
});
test('admin navigation and empty-history fallback work', () => {
    const store = new Map(); visit('/admin/airlines', store, { role: 'admin' });
    assert.equal(visit('/admin/flights', store, { role: 'admin' }).back(), '/admin/airlines');
    assert.equal(visit('/admin/payments', new Map(), { role: 'admin', fallback: '/admin' }).back(), '/admin');
});
test('guest login/signup preserve the preceding public page', () => {
    const store = new Map(); visit('/', store, { home: true, role: 'guest' }); visit('/login', store, { role: 'guest' });
    assert.equal(visit('/register', store, { role: 'guest', fallback: '/' }).back(), '/login');
});
test('sign-in, sign-out and account changes discard inaccessible history', () => {
    const store = new Map(); visit('/', store, { home: true, role: 'guest' }); visit('/login', store, { role: 'guest' });
    assert.equal(visit('/account', store).href(), '/'); visit('/payments?booking_id=1', store);
    assert.equal(visit('/login', store, { role: 'guest', fallback: '/' }).back(), '/');
});
test('unsafe origins, POST endpoints, JSON endpoints and malformed storage are rejected', () => {
    const store = new Map([[key, JSON.stringify({ identity: 'customer:1', entries: ['https://evil.test/flights', '/flights/select', '/logout', '/flights?seat_counts=1&ids=1', '/admin', 'javascript:alert(1)', '//evil.test/'].map((url, i) => ({ url, id: '' + i })) })]]);
    assert.equal(visit('/payments', store).back(), '/account');
    store.set(key, '{broken'); assert.equal(visit('/payments', store).back(), '/account');
});
test('blocked storage still provides a safe fallback', () => { assert.equal(visit('/payments', new Map(), { blocked: true }).back(), '/account'); });
test('redirected Back destination is removed so it cannot repeat indefinitely', () => {
    const store = new Map(); visit('/flights', store); visit('/seat-selection?booking_id=1', store);
    assert.equal(visit('/payments', store).back(), '/seat-selection?booking_id=1');
    assert.equal(visit('/seat-selection', store).back(), '/flights');
});
test('browser Back and cached pages restore their matching history entry', () => {
    const store = new Map(); visit('/flights', store); const details = visit('/flights/details?id=1', store); visit('/payments', store);
    details.pageshow(); assert.equal(details.entries().length, 2); assert.equal(details.href(), '/flights');
});
