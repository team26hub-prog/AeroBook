const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { test } = require('node:test');
const script = fs.readFileSync(require('node:path').join(__dirname, '../public/assets/js/flight-date-picker.js'), 'utf8');

class Element {
    constructor(tag) {
        this.tag = tag; this.children = []; this.attrs = {}; this.events = {}; this.dataset = {};
        this.classList = { add() {}, remove() {} }; this.dispatched = [];
    }
    append(...items) { this.children.push(...items); }
    before(item) { this.wrapper = item; }
    setAttribute(key, value) { this.attrs[key] = value; }
    addEventListener(key, action) { this.events[key] = action; }
    replaceChildren() { this.children = []; }
    focus() { this.focused = true; }
    contains(item) { return item === this || this.children.some(child => child.contains(item)); }
    all() { return this.children.flatMap(child => [child, ...child.all()]); }
    querySelector(selector) {
        return this.all().find(item => {
            if (selector === '.flight-date-days button:not(:disabled)') return item.attrs['aria-pressed'] !== undefined && !item.disabled;
            if (selector === '[data-previous]') return 'previous' in item.dataset;
            if (selector === '[data-next]') return 'next' in item.dataset;
            const attr = selector.match(/^\[([^=]+)="([^"]+)"\]$/);
            return attr ? item.attrs[attr[1]] === attr[2] : item.tag === selector;
        });
    }
    dispatchEvent(event) { this.dispatched.push(event.type); }
}

function setup(width = 1280, { now = [2026, 9, 14, 12], travelMin = '2026-10-14', travelMax = '' } = {}) {
    // Freeze only the application's VM clock; explicit dates and local-time
    // arithmetic keep normal Date behavior. The host clock remains untouched.
    const timestamp = new Date(...now).getTime();
    class CalendarDate extends Date {
        constructor(...args) { super(...(args.length ? args : [timestamp])); }
        static now() { return timestamp; }
    }
    const inputs = [
        Object.assign(new Element('input'), { name: 'date', value: '', min: travelMin, max: travelMax }),
        Object.assign(new Element('input'), { name: 'passengers[0][date_of_birth]', value: '2000-02-29', min: '1000-01-01', max: '2026-10-14' })
    ];
    vm.runInNewContext(script, {
        document: { querySelectorAll: () => inputs, createElement: tag => new Element(tag), addEventListener() {} },
        window: { innerWidth: width, addEventListener() {} }, Date: CalendarDate,
        Event: class { constructor(type) { this.type = type; } }
    });
    const panel = input => input.wrapper.children.find(child => child.className === 'flight-date-calendar');
    return { inputs, panel };
}

test('opens both calendars on desktop, tablet and mobile without a width gate', () => {
    for (const width of [320, 768, 1440]) {
        const { inputs, panel } = setup(width);
        for (const input of inputs) {
            let prevented = false;
            input.events.pointerdown({ preventDefault() { prevented = true; } });
            assert.equal(prevented, true);
            assert.equal(panel(input).hidden, false);
        }
        assert.notEqual(panel(inputs[0]).id, panel(inputs[1]).id);
    }
});

test('enforces travel-date limits and dispatches validation events after selection', () => {
    const { inputs: [input], panel } = setup();
    input.events.click({ preventDefault() {} });
    const days = panel(input).all().filter(item => item.attrs['aria-pressed'] !== undefined);
    assert.equal(days.length, 31);
    assert.equal(days[12].disabled, true);
    assert.equal(days[13].disabled, false);
    days[16].events.click();
    assert.equal(input.value, '2026-10-17');
    assert.deepEqual(input.dispatched, ['input', 'change']);
    assert.equal(panel(input).hidden, true);
});

test('supports leap-day birth dates and direct year selection', () => {
    const { inputs: [, input], panel } = setup();
    input.events.click({ preventDefault() {} });
    assert.equal(panel(input).querySelector('[aria-pressed="true"]').textContent, '29');
    const year = panel(input).querySelector('[aria-label="Calendar year"]');
    year.value = '2001'; year.events.change();
    const days = panel(input).all().filter(item => item.attrs['aria-pressed'] !== undefined);
    assert.equal(days.length, 28);
});

test('does not open a disabled passenger field', () => {
    const { inputs: [, input], panel } = setup();
    input.disabled = true;
    input.events.click({ preventDefault() {} });
    assert.equal(panel(input).hidden, true);
});

for (const year of [1900, 2000, 2023, 2024, 2100, 2400]) {
    test(`renders and validates every month in ${year}`, () => {
        // Independent expected lengths include Gregorian century exceptions.
        const leap = year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0);
        const lengths = [31, leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        for (let month = 0; month < 12; month++) {
            const prefix = `${year}-${String(month + 1).padStart(2, '0')}`;
            const { inputs: [input], panel } = setup(1280, {
                now: [year, month, 15, 12], travelMin: `${prefix}-14`, travelMax: `${prefix}-20`
            });
            input.events.click({ preventDefault() {} });
            const calendar = panel(input);
            assert.equal(calendar.querySelector('[aria-label="Calendar month"]').value, String(month));
            assert.equal(calendar.querySelector('[aria-label="Calendar year"]').value, String(year));
            const days = calendar.all().filter(item => item.attrs['aria-pressed'] !== undefined);
            assert.equal(days.length, lengths[month], prefix);
            days.forEach((day, index) => {
                assert.equal(day.textContent, String(index + 1), prefix);
                assert.equal(day.disabled, index + 1 < 14 || index + 1 > 20, `${prefix}-${index + 1}`);
            });
            assert.equal(days[14].attrs['aria-current'], 'date');
            days[16].events.click();
            assert.equal(input.value, `${prefix}-17`);
            assert.deepEqual(input.dispatched, ['input', 'change']);
            assert.equal(calendar.hidden, true);
        }
    });
}

test('handles midnight, year rollover, leap day and month navigation with a fixed local clock', () => {
    for (const now of [
        [2026, 11, 31, 23, 59, 59, 999], [2027, 0, 1, 0, 0, 0, 0],
        [2024, 1, 29, 23, 59, 59, 999], [2024, 2, 1, 0, 0, 0, 0]
    ]) {
        const [year, month, day] = now;
        const { inputs: [input], panel } = setup(320, { now, travelMin: '' });
        input.events.click({ preventDefault() {} });
        const calendar = panel(input);
        assert.equal(calendar.querySelector('[aria-current="date"]').textContent, String(day));
        calendar.querySelector('[data-next]').events.click();
        assert.equal(calendar.querySelector('[aria-label="Calendar month"]').value, String((month + 1) % 12));
        assert.equal(calendar.querySelector('[aria-label="Calendar year"]').value, String(year + (month === 11 ? 1 : 0)));
        calendar.querySelector('[data-previous]').events.click();
        assert.equal(calendar.querySelector('[aria-label="Calendar month"]').value, String(month));
        assert.equal(calendar.querySelector('[aria-label="Calendar year"]').value, String(year));
        calendar.all().find(item => item.tag === 'button' && item.textContent === 'Today').events.click();
        assert.equal(input.value, `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`);
        assert.equal(calendar.hidden, true);
    }
});
