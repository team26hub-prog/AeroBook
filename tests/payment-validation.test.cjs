const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { test } = require('node:test');
const script = fs.readFileSync(require('node:path').join(__dirname, '../public/assets/js/payment-validation.js'), 'utf8');

function setup() {
    const fields = ['sender_name', 'transaction_reference'].map(name => ({
        name, value: '', required: true, validity: {}, events: {}, attrs: {},
        setCustomValidity(message) { this.message = message; },
        setAttribute(key, value) { this.attrs[key] = value; },
        getAttribute(key) { return this.attrs[key]; },
        after(error) { this.error = error; },
        addEventListener(event, handler) { this.events[event] = handler; },
        focus() { this.focused = true; }
    }));
    const form = {
        dataset: {}, elements: { namedItem: () => null },
        querySelectorAll: () => fields,
        addEventListener(event, handler) { this[event] = handler; }
    };
    vm.runInNewContext(script, {
        document: { querySelectorAll: () => [form], createElement: () => ({ setAttribute() {} }) },
        performance: { now: () => 0 }, Date
    });
    return { fields, form };
}

test('shows live account-name errors and clears them for international names', () => {
    const { fields: [field] } = setup();
    for (const value of ['1', '12345', '-', 'A', 'John123', '<script>']) {
        field.value = value;
        field.events.input();
        assert.match(field.error.textContent, /Enter a valid sender/);
        assert.equal(field.attrs['aria-invalid'], 'true');
    }
    for (const value of ['Daniel Harris', "O’Connor", 'محمد علی', 'José Álvarez', 'A & B Ltd.']) {
        field.value = value;
        field.events.input();
        assert.equal(field.error.textContent, '');
        assert.equal(field.attrs['aria-invalid'], 'false');
    }
});

test('rejects punctuation-only references and accepts receipt formats', () => {
    const { fields: [, field] } = setup();
    for (const value of ['-', '---', ' /._ ', '<script>', 'TX@123']) {
        field.value = value;
        field.events.input();
        assert.match(field.error.textContent, /Enter a valid transaction/);
    }
    for (const value of ['TXN-123456', '123456789', 'AB/123.45_67', 'Receipt 123']) {
        field.value = value;
        field.events.change();
        assert.equal(field.error.textContent, '');
    }
});

test('blocks confirmation on invalid submission and allows corrected values', () => {
    const { fields, form } = setup();
    fields[0].value = '1'; fields[1].value = '-';
    let blocked = false, stopped = false;
    form.submit({ preventDefault() { blocked = true; }, stopImmediatePropagation() { stopped = true; } });
    assert.ok(blocked && stopped && fields[0].focused);
    fields[0].value = 'Daniel Harris'; fields[1].value = 'TXN-123456';
    form.submit({ preventDefault() { assert.fail('Valid submission blocked'); } });
    assert.ok(fields.every(field => field.message === ''));
});
