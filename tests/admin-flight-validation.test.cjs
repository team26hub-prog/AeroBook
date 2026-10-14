const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { test } = require('node:test');
const script = fs.readFileSync(require('node:path').join(__dirname, '../public/assets/js/admin-flight-validation.js'), 'utf8');

function setup() {
    const values = { airline_id: '1', flight_number: 'AB101', departure_airport_id: '1', arrival_airport_id: '2', departure_at: '2028-02-28T23:00', arrival_at: '2028-02-29T01:00', base_fare: '18500.00', currency: 'PKR', status: 'scheduled' };
    const fields = Object.entries(values).map(([name, value]) => ({
        name, value, validity: {}, events: {}, attrs: { 'aria-describedby': 'existing-help' },
        options: [{ value: '', disabled: true }, { value: '1' }, { value: '2' }],
        setCustomValidity(message) { this.message = message; },
        setAttribute(key, value) { this.attrs[key] = value; },
        getAttribute(key) { return this.attrs[key]; },
        removeAttribute(key) { delete this.attrs[key]; },
        closest() { return { append: error => { this.error = error; } }; },
        addEventListener(event, handler) { this.events[event] = handler; },
        focus() { this.focused = true; }
    }));
    const get = name => fields.find(field => field.name === name);
    const form = { dataset: {}, elements: { namedItem: get }, querySelectorAll: () => fields, addEventListener(event, handler) { this[event] = handler; } };
    vm.runInNewContext(script, { document: { querySelector: () => form, createElement: () => ({ setAttribute() {} }) }, Date, setTimeout: handler => handler() });
    const check = (name, value, valid) => {
        const field = get(name); field.value = value; field.events.input();
        assert.equal(!field.message, valid, `${name}: ${value}`);
        assert.equal(field.attrs['aria-invalid'], String(!valid));
        assert.equal(field.error.textContent, field.message);
    };
    return { fields, form, get, check };
}

test('validates flight number, fare, currency and status while typing', () => {
    const { check } = setup();
    for (const value of ['', 'A', 'AB 101', 'AB@101', 'A'.repeat(11)]) check('flight_number', value, false);
    for (const value of ['AB101', 'ab-123', 'AB', 'A'.repeat(10)]) check('flight_number', value, true);
    for (const value of ['', '0', '-1', '1.001', '1e3', '100000000']) check('base_fare', value, false);
    for (const value of ['0.01', '18500', '99999999.99']) check('base_fare', value, true);
    for (const value of ['', 'PK', '123', 'PKRR']) check('currency', value, false);
    for (const value of ['PKR', 'usd']) check('currency', value, true);
    check('status', 'unknown', false);
    for (const value of ['scheduled', 'boarding', 'departed', 'completed', 'cancelled']) check('status', value, true);
});

test('validates listed options and updates arrival errors when departure changes', () => {
    const { check, get } = setup();
    for (const name of ['airline_id', 'departure_airport_id', 'arrival_airport_id']) {
        for (const value of ['', '0', '999', '1e3']) check(name, value, false);
    }
    check('airline_id', '1', true);
    check('departure_airport_id', '1', true);
    check('arrival_airport_id', '1', false);
    check('departure_airport_id', '2', true);
    assert.equal(get('arrival_airport_id').message, '');
    check('arrival_airport_id', '2', false);
    check('arrival_airport_id', '1', true);
    for (const value of ['', '2027-02-29T10:00', '2028-02-30T10:00', 'invalid']) check('arrival_at', value, false);
    check('arrival_at', '2028-02-28T23:00', false);
    check('arrival_at', '2028-02-29T01:00', true);
    check('departure_at', '2028-02-29T02:00', true);
    assert.match(get('arrival_at').message, /later than departure/);
    check('departure_at', '2028-02-28T23:00', true);
    assert.equal(get('arrival_at').message, '');
});

test('blocks invalid submissions before confirmation and resets validation state', () => {
    const { form, get, fields } = setup();
    assert.ok(fields.every(field => field.attrs['aria-describedby'].startsWith('existing-help ')));
    form.submit({ preventDefault() { assert.fail('Valid submission blocked'); } });
    get('base_fare').value = '0';
    let blocked = false, stopped = false;
    form.dataset.confirmed = 'true';
    form.submit({ preventDefault() { blocked = true; }, stopImmediatePropagation() { stopped = true; } });
    assert.ok(blocked && stopped && get('base_fare').focused);
    assert.equal(form.dataset.confirmed, undefined);
    form.reset();
    assert.ok(fields.every(field => field.message === '' && field.error.textContent === '' && field.attrs['aria-invalid'] === undefined));
});
