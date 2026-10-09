const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { test } = require('node:test');

const script = fs.readFileSync(require('node:path').join(__dirname, '../public/assets/js/alerts.js'), 'utf8');

async function submitPayment(decision, { confirmed = true, sweetAlert = true, named = true } = {}) {
    let onSubmit;
    let confirmation;
    const requests = [];
    class Button {
        constructor() {
            this.name = named ? 'decision' : '';
            this.value = decision;
            this.textContent = decision;
            this.dataset = {};
            this.disabled = false;
        }
        setAttribute() {}
    }
    class Form {
        constructor() { this.dataset = {}; this.inputs = []; }
        matches(selector) { return selector === '.review-actions'; }
        appendChild(input) { this.inputs.push(input); }
        async requestSubmit(button) {
            const event = { target: this, submitter: button, prevented: false, preventDefault() { this.prevented = true; } };
            await onSubmit(event);
            if (!event.prevented) {
                // Browser form data excludes disabled controls and unclicked buttons.
                const data = this.inputs.map(input => [input.name, input.value]);
                if (button.name && !button.disabled) data.push([button.name, button.value]);
                requests.push(data);
            }
        }
    }
    const window = {
        matchMedia: () => ({ matches: false }),
        confirm: () => confirmed,
        ...(sweetAlert ? { Swal: {
            mixin: () => ({ fire() {} }),
            fire: async options => { confirmation = options; return { isConfirmed: confirmed }; }
        } } : {})
    };
    vm.runInNewContext(script, {
        window, HTMLFormElement: Form, HTMLButtonElement: Button,
        document: {
            querySelectorAll: () => [],
            createElement: () => ({}),
            addEventListener: (type, callback) => { if (type === 'submit') onSubmit = callback; }
        }
    });
    const form = new Form();
    const button = new Button();
    await form.requestSubmit(button);
    return { form, button, requests, confirmation };
}

for (const decision of ['verified', 'rejected']) {
    for (const sweetAlert of [true, false]) {
        test(`${decision} preserves exactly one decision with ${sweetAlert ? 'SweetAlert' : 'native confirmation'}`, async () => {
            const result = await submitPayment(decision, { sweetAlert });
            assert.deepEqual(result.requests, [[['decision', decision]]]);
            assert.equal(result.button.disabled, true);
            assert.equal(result.form.dataset.submitting, 'true');
            if (sweetAlert) assert.equal(result.confirmation.confirmButtonText, decision === 'verified' ? 'Verify payment' : 'Reject payment');
        });
    }
    test(`cancelling ${decision} sends no request and keeps the button available`, async () => {
        const result = await submitPayment(decision, { confirmed: false });
        assert.deepEqual(result.requests, []);
        assert.equal(result.button.disabled, false);
        assert.equal(result.form.inputs.length, 0);
    });
}

test('unnamed submit buttons do not add an empty field', async () => {
    const result = await submitPayment('verified', { named: false });
    assert.deepEqual(result.requests, [[]]);
    assert.equal(result.form.inputs.length, 0);
});

test('shared submission handler ignores forms rejected by page validation', async () => {
    let onSubmit;
    class Form { constructor() { this.dataset = {}; } }
    class Button { constructor() { this.disabled = false; } }
    vm.runInNewContext(script, {
        window: { matchMedia: () => ({ matches: false }) },
        HTMLFormElement: Form, HTMLButtonElement: Button,
        document: { querySelectorAll: () => [], addEventListener: (type, callback) => { onSubmit = callback; } }
    });
    const form = new Form();
    const button = new Button();
    await onSubmit({ target: form, submitter: button, defaultPrevented: true });
    assert.equal(form.dataset.submitting, undefined);
    assert.equal(button.disabled, false);
});

for (const sweetAlert of [true, false]) {
    test(`seat availability warning ${sweetAlert ? 'opens one modal without a toast' : 'stays visible without SweetAlert'}`, () => {
        const modals = [];
        const toasts = [];
        const warning = {
            hidden: false,
            dataset: { popupTitle: 'No seats available' },
            textContent: 'No seats available. Choose another flight.',
            hasAttribute: name => name === 'data-popup-title',
            querySelector: selector => selector === 'p' ? { textContent: 'All seats are reserved. Choose another flight.' } : null
        };
        vm.runInNewContext(script, {
            window: {
                matchMedia: () => ({ matches: false }),
                ...(sweetAlert ? { Swal: {
                    mixin: () => ({ fire: options => toasts.push(options) }),
                    fire: options => modals.push(options)
                } } : {})
            },
            document: {
                querySelectorAll: () => [warning],
                addEventListener() {}
            }
        });
        assert.equal(warning.hidden, sweetAlert);
        assert.equal(toasts.length, 0);
        assert.equal(modals.length, sweetAlert ? 1 : 0);
        if (sweetAlert) {
            assert.equal(modals[0].titleText, 'No seats available');
            assert.equal(modals[0].text, 'All seats are reserved. Choose another flight.');
            assert.equal(modals[0].icon, 'warning');
            assert.equal(modals[0].confirmButtonText, 'OK');
        }
    });
}
