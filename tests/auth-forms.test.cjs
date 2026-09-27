const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');

function setup() {
    const events = {};
    let timeout;
    const button = { disabled: false, innerHTML: 'Sign in', setAttribute() {}, removeAttribute() {} };
    const form = {
        closest: () => true,
        querySelector: () => null,
        append(notice) { this.notice = notice; },
    };
    const context = {
        window: { addEventListener: (name, fn) => { events[name] = fn; } },
        document: {
            addEventListener: (name, fn) => { events[name] = fn; },
            createElement: () => ({ dataset: {}, setAttribute() {} }),
        },
        navigator: { onLine: true },
        setTimeout: (fn) => { timeout = fn; return 1; },
        clearTimeout() {},
    };
    vm.runInNewContext(fs.readFileSync('public/auth-forms.js', 'utf8'), context);
    const submit = () => {
        const event = { target: form, submitter: button, preventDefault() { this.defaultPrevented = true; } };
        events.submit(event);
        return event;
    };
    return { context, button, form, events, submit, expire: () => timeout() };
}

test('blocks double submission and restores the button on back navigation', () => {
    const page = setup();
    assert.ok(!page.submit().defaultPrevented);
    assert.equal(page.button.disabled, true);
    assert.equal(page.submit().defaultPrevented, true);
    page.events.pageshow();
    assert.equal(page.button.disabled, false);
    assert.equal(page.button.innerHTML, 'Sign in');
    assert.ok(!page.submit().defaultPrevented);
});

test('slow submission recovers without automatically submitting again', () => {
    const page = setup();
    page.submit();
    page.expire();
    assert.equal(page.button.disabled, false);
    assert.match(page.form.notice.textContent, /longer than expected/);
});

test('offline submission stays editable and explains how to recover', () => {
    const page = setup();
    page.context.navigator.onLine = false;
    assert.equal(page.submit().defaultPrevented, true);
    assert.equal(page.button.disabled, false);
    assert.match(page.form.notice.textContent, /offline/);
});
