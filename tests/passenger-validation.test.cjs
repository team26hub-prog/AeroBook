const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { test } = require('node:test');
const script = fs.readFileSync(require('node:path').join(__dirname, '../public/assets/js/passenger-validation.js'), 'utf8');

function setup() {
    const field = (name, value) => ({
        name, value, disabled: false, validity: {}, attrs: {}, events: {},
        min: '1000-01-01', max: '2026-10-14',
        setCustomValidity(value) { this.message = value; },
        setAttribute(key, value) { this.attrs[key] = value; },
        getAttribute(key) { return this.attrs[key]; },
        after(error) { this.error = error; },
        addEventListener(event, fn) { (this.events[event] ||= []).push(fn); },
        fire(event) { this.events[event]?.forEach(fn => fn()); },
        focus() { this.focused = true; }
    });
    const count = field('', '1');
    const groups = Array.from({ length: 9 }, (_, index) => {
        const defaults = { first_name: 'Daniel', last_name: 'Harris', date_of_birth: '2000-01-01', gender: 'unspecified', passport_number: '', passport_country: '' };
        const fields = Object.entries(defaults).map(([key, value]) => {
            const input = field(`passengers[${index}][${key}]`, value);
            input.disabled = index > 0;
            return input;
        });
        return { hidden: index > 0, fields, querySelectorAll: () => fields };
    });
    const form = { querySelectorAll: () => groups, addEventListener(event, fn) { this[event] = fn; } };
    vm.runInNewContext(script, {
        document: { getElementById: id => id === 'passenger-form' ? form : count, createElement: () => ({ setAttribute() {} }) }, Date
    });
    return { form, count, groups, get: (key, index = 0) => groups[index].fields.find(field => field.name.endsWith(`[${key}]`)) };
}

test('names reject digits and punctuation with live errors, accepting international alphabets', () => {
    const ui = setup();
    for (const name of ['first_name', 'last_name']) {
        const field = ui.get(name);
        for (const value of ['', '123', 'John1', "O'Connor", 'Anne-Marie', '<script>', 'A'.repeat(101)]) {
            field.value = value; field.fire('input');
            assert.ok(field.message, value);
            assert.equal(field.attrs['aria-invalid'], 'true');
        }
        for (const value of ['Daniel', 'Mary Jane', 'محمد', 'José', 'Jose\u0301', '李']) {
            field.value = value; field.fire('input');
            assert.equal(field.error.textContent, '', value);
        }
    }
});

test('validates date, gender, and both passport fields together', () => {
    const ui = setup();
    const check = (name, value, valid) => {
        const field = ui.get(name); field.value = value; field.fire('change');
        assert.equal(!field.message, valid, `${name}: ${value}`);
    };
    for (const value of ['', '2026-02-30', '2026-10-15', '0999-12-31']) check('date_of_birth', value, false);
    for (const value of ['2000-02-29', '2026-10-14']) check('date_of_birth', value, true);
    check('gender', 'invalid', false); check('gender', 'unspecified', true);
    check('passport_number', 'AB-123', false);
    check('passport_number', 'AB123', true);
    assert.ok(ui.get('passport_country').message);
    check('passport_country', 'PAK', false); check('passport_country', 'pk', true);
    check('passport_number', '', false);
    check('passport_country', '', true);
    assert.equal(ui.get('passport_number').message, '');
});

test('validates added passengers, excludes removed passengers, and checks count', () => {
    const ui = setup();
    const submit = () => {
        let blocked = false;
        ui.form.submit({ preventDefault() { blocked = true; }, stopImmediatePropagation() {} });
        return blocked;
    };
    assert.equal(submit(), false);
    ui.count.value = '2'; ui.count.fire('change');
    assert.equal(ui.groups[1].hidden, false);
    const second = ui.get('first_name', 1); second.value = '123'; second.fire('input');
    assert.equal(submit(), true); assert.ok(second.focused);
    ui.count.value = '1'; ui.count.fire('change');
    assert.equal(ui.groups[1].hidden, true); assert.equal(second.message, '');
    assert.equal(submit(), false);
    ui.count.value = '10'; ui.count.fire('change');
    assert.equal(submit(), true);
});
