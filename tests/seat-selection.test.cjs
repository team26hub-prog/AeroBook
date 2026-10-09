const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { test } = require('node:test');
const script = fs.readFileSync(require('node:path').join(__dirname, '../public/assets/js/seat-selection.js'), 'utf8');

function setup(initial = ['', '']) {
    const progress = { textContent: '' };
    const fill = { style: {} };
    const save = { disabled: false };
    const help = { textContent: '' };
    const active = { value: '1' };
    const selects = initial.map((value, index) => {
        const label = { textContent: '' };
        const row = { querySelector: selector => selector === 'strong' ? { textContent: `Passenger ${index + 1}` } : label };
        return {
            value, dataset: { passengerId: String(index + 1) }, events: {},
            options: ['', '10', '11'].map(value => ({ value, disabled: false, dataset: { seatNumber: value === '10' ? '10A' : '10B' } })),
            get selectedOptions() { return this.options.filter(option => option.value === this.value); },
            closest: () => row,
            addEventListener(type, callback) { this.events[type] = callback; }
        };
    });
    const buttons = ['10', '11', '12'].map((id, index) => ({
        disabled: index === 2,
        dataset: { seatId: id, seatNumber: ['10A', '10B', '10C'][index], seatState: index === 2 ? 'occupied' : 'available', cabin: 'Economy' },
        classes: new Set(), attributes: {}, events: {},
        get classList() { return { toggle: (name, enabled) => enabled ? this.classes.add(name) : this.classes.delete(name) }; },
        setAttribute(name, value) { this.attributes[name] = value; },
        addEventListener(type, callback) { this.events[type] = callback; }
    }));
    const form = {
        events: {},
        querySelectorAll: selector => selector === '.map-seat' ? buttons : selects,
        addEventListener(type, callback) { this.events[type] = callback; }
    };
    const elements = { 'seat-selection-form': form, 'active-passenger': active, 'seat-map-help': help, 'seat-selection-progress': progress, 'seat-progress-fill': fill, 'save-seat-selection': save };
    vm.runInNewContext(script, { document: { getElementById: id => elements[id] } });
    return { form, active, selects, buttons, progress, fill, save, help };
}

test('saving requires a unique seat for every passenger', () => {
    const ui = setup();
    assert.equal(ui.save.disabled, true);
    ui.buttons[0].events.click();
    assert.equal(ui.selects[0].value, '10');
    assert.equal(ui.active.value, '2');
    assert.equal(ui.progress.textContent, '1 of 2 passengers have seats');
    assert.equal(ui.save.disabled, true);
    assert.equal(ui.selects[1].options[1].disabled, true);
    ui.buttons[1].events.click();
    assert.equal(ui.selects[1].value, '11');
    assert.equal(ui.save.disabled, false);
    assert.equal(ui.fill.style.width, '100%');
    assert.equal(ui.buttons[0].attributes['aria-pressed'], 'true');
    assert.match(ui.buttons[0].attributes['aria-label'], /Passenger 1/);
});

test('reserved seats and seats selected by another passenger cannot be assigned', () => {
    const ui = setup();
    ui.buttons[2].events.click();
    assert.equal(ui.selects[0].value, '');
    ui.buttons[0].events.click();
    ui.buttons[0].events.click();
    assert.equal(ui.selects[1].value, '');
    assert.match(ui.help.textContent, /another passenger/);
});

test('clearing an assignment releases its option and disables saving', () => {
    const ui = setup(['10', '11']);
    assert.equal(ui.save.disabled, false);
    ui.selects[0].value = '';
    ui.selects[0].events.change();
    assert.equal(ui.save.disabled, true);
    assert.equal(ui.selects[1].options[1].disabled, false);
    assert.equal(ui.buttons[0].attributes['aria-pressed'], 'false');
    assert.match(ui.buttons[0].attributes['aria-label'], /available$/);
});

test('duplicate dropdown selections and incomplete submissions are rejected', () => {
    const ui = setup(['10', '']);
    ui.selects[1].value = '10';
    ui.selects[1].events.change();
    assert.equal(ui.selects[1].value, '');
    let prevented = false;
    ui.form.events.submit({ preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    assert.equal(ui.save.disabled, true);
});

test('saved assignments remain selected on page load', () => {
    const ui = setup(['10', '11']);
    assert.equal(ui.progress.textContent, '2 of 2 passengers have seats');
    assert.equal(ui.save.disabled, false);
    assert.equal(ui.buttons[1].attributes['aria-pressed'], 'true');
});
