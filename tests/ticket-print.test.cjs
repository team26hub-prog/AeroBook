const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { test } = require('node:test');
const script = fs.readFileSync(require('node:path').join(__dirname, '../public/assets/js/ticket-print.js'), 'utf8');

function setup({ fail = false } = {}) {
    const classList = () => {
        const names = new Set();
        return { add: name => names.add(name), remove: name => names.delete(name), contains: name => names.has(name) };
    };
    const tickets = ['FIRST123', 'SECOND456'].map(number => ({ dataset: { ticketNumber: number }, classList: classList() }));
    const buttons = tickets.map(ticket => ({
        closest: () => ticket,
        addEventListener(type, handler) { this[type] = handler; }
    }));
    const document = { title: 'E-Tickets | AeroBook', body: { classList: classList() }, querySelectorAll: () => buttons };
    const events = {};
    const printed = [];
    const window = {
        addEventListener: (type, handler) => { events[type] = handler; },
        print: () => {
            printed.push({ title: document.title, active: document.body.classList.contains('ticket-print-active'), targets: tickets.map(ticket => ticket.classList.contains('ticket-print-target')) });
            if (fail) throw new Error('Print unavailable');
        }
    };
    vm.runInNewContext(script, { window, document });
    return { document, events, printed, tickets, buttons };
}

test('each print button targets only its own ticket', () => {
    const ui = setup();
    ui.buttons[0].click();
    assert.deepEqual(ui.printed[0], { title: 'AeroBook-ticket-FIRST123', active: true, targets: [true, false] });
    ui.events.afterprint();
    ui.buttons[1].click();
    assert.deepEqual(ui.printed[1], { title: 'AeroBook-ticket-SECOND456', active: true, targets: [false, true] });
});

test('closing or cancelling print restores the page and document title', () => {
    const ui = setup();
    ui.buttons[1].click();
    ui.events.afterprint();
    assert.equal(ui.document.title, 'E-Tickets | AeroBook');
    assert.equal(ui.document.body.classList.contains('ticket-print-active'), false);
    assert.equal(ui.tickets.some(ticket => ticket.classList.contains('ticket-print-target')), false);
    ui.events.afterprint();
    assert.equal(ui.document.title, 'E-Tickets | AeroBook');
});

test('a second click cannot change the target while a print dialog is active', () => {
    const ui = setup();
    ui.buttons[0].click();
    ui.buttons[1].click();
    assert.equal(ui.printed.length, 1);
    assert.equal(ui.tickets[0].classList.contains('ticket-print-target'), true);
});

test('a print failure cleans up the selection', () => {
    const ui = setup({ fail: true });
    assert.throws(() => ui.buttons[0].click(), /Print unavailable/);
    assert.equal(ui.document.title, 'E-Tickets | AeroBook');
    assert.equal(ui.document.body.classList.contains('ticket-print-active'), false);
});
