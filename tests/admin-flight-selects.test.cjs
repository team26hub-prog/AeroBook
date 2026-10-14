const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { test } = require('node:test');
const script = fs.readFileSync(require('node:path').join(__dirname, '../public/assets/js/admin-flight-selects.js'), 'utf8');
class Element {
    constructor() {
        this.children = []; this.attrs = {}; this.events = {}; this.classes = new Set();
        this.classList = { add: name => this.classes.add(name), remove: name => this.classes.delete(name), toggle: (name, enabled) => enabled ? this.classes.add(name) : this.classes.delete(name) };
    }
    append(...items) { this.children.push(...items); }
    before(item) { this.wrapper = item; }
    setAttribute(key, value) { this.attrs[key] = value; }
    getAttribute(key) { return this.attrs[key] ?? null; }
    removeAttribute(key) { delete this.attrs[key]; }
    addEventListener(event, fn) { (this.events[event] ||= []).push(fn); }
    dispatchEvent(event) { this.events[event.type]?.forEach(fn => fn(event)); }
    contains(node) { return node === this || this.children.some(item => item.contains(node)); }
    focus() { this.focused = true; }
    scrollIntoView() {}
}
function setup() {
    const label = new Element(); label.childNodes = [{ nodeType: 3, textContent: 'Departure airport' }];
    const select = new Element(); select.closest = () => label;
    select.required = true;
    select.options = [{ value: '', textContent: 'Choose an airport', disabled: true }, { value: '1', textContent: 'Dubai — DXB · Dubai International Airport' }, { value: '2', textContent: 'Islamabad — ISB · Islamabad International Airport' }];
    let value = '';
    Object.defineProperty(select, 'value', { get: () => value, set: next => { value = next; select.options.forEach(option => { option.selected = option.value === value; }); } });
    select.value = '';
    select.form = new Element();
    const document = new Element(); document.querySelectorAll = () => [select]; document.createElement = () => new Element();
    let observe;
    vm.runInNewContext(script, { document, Event: class { constructor(type) { this.type = type; } }, MutationObserver: class { constructor(fn) { observe = fn; } observe() {} }, performance: { now: () => 1000 }, setTimeout: fn => fn() });
    const [, toggle, list] = select.wrapper.children;
    const fire = (node, type, extra = {}) => node.dispatchEvent({ type, preventDefault() {}, ...extra });
    return { select, toggle, list, document, fire, observe, label };
}
test('opens an accessible menu and selects an option without changing posted field names', () => {
    const { select, toggle, list, fire, label } = setup();
    const changes = [];
    select.addEventListener('input', () => changes.push('input'));
    select.addEventListener('change', () => changes.push('change'));
    fire(toggle, 'click');
    assert.equal(list.hidden, false);
    assert.equal(toggle.attrs['aria-expanded'], 'true');
    assert.equal(list.attrs.role, 'listbox');
    assert.equal(label.attrs.for, toggle.id);
    fire(list.children[1], 'click');
    assert.equal(select.value, '1');
    assert.deepEqual(changes, ['input', 'change']);
    assert.equal(toggle.children[0].textContent, select.options[1].textContent);
    assert.equal(list.hidden, true);
});
test('supports keyboard navigation, typing, Escape, and disabled placeholders', () => {
    const { select, toggle, list, fire } = setup();
    fire(toggle, 'keydown', { key: 'i' });
    assert.equal(toggle.attrs['aria-activedescendant'], list.children[2].id);
    fire(toggle, 'keydown', { key: 'Enter' });
    assert.equal(select.value, '2');
    fire(toggle, 'keydown', { key: 'ArrowDown' });
    fire(toggle, 'keydown', { key: 'Home' });
    assert.equal(toggle.attrs['aria-activedescendant'], list.children[1].id);
    fire(toggle, 'keydown', { key: 'Escape' });
    assert.equal(list.hidden, true);
    assert.equal(toggle.attrs['aria-activedescendant'], undefined);
});
test('mirrors validation, forwards focus, closes outside, and follows form reset', () => {
    const { select, toggle, list, document, fire, observe } = setup();
    select.setAttribute('aria-invalid', 'true'); select.setAttribute('aria-describedby', 'departure-error'); observe();
    assert.equal(toggle.attrs['aria-invalid'], 'true');
    assert.equal(toggle.attrs['aria-describedby'], 'departure-error');
    fire(select, 'focus'); assert.equal(toggle.focused, true);
    fire(toggle, 'click'); fire(document, 'pointerdown', { target: new Element() });
    assert.equal(list.hidden, true);
    select.value = '1'; fire(select.form, 'reset');
    assert.equal(toggle.children[0].textContent, select.options[1].textContent);
    assert.equal(list.hidden, true);
});
