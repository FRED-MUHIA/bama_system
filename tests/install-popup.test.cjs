const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');

function setup({ standalone = false, dismissed = false, blockedStorage = false, phone = true } = {}) {
    const events = {};
    const popup = { open: false, showModal() { this.open = true; }, close() { this.open = false; }, addEventListener(name, fn) { events[name] = fn; } };
    const cancel = { addEventListener(name, fn) { this.click = fn; } };
    const storage = {
        getItem() { if (blockedStorage) throw Error('blocked'); return dismissed ? Date.now() : null; },
        setItem() { if (blockedStorage) throw Error('blocked'); },
    };
    const context = {
        window: { matchMedia: (query) => ({ matches: query.includes('standalone') ? standalone : phone, addEventListener() {} }), navigator: { userAgent: 'Android' }, addEventListener(name, fn) { events[name] = fn; } },
        document: { querySelector: (query) => query.includes('popup') ? popup : null, querySelectorAll: (query) => query.includes('dismiss') ? [cancel] : [] },
        localStorage: storage,
    };
    const source = fs.readFileSync('resources/js/pwa.js', 'utf8').split('function configureConnectivity')[0];
    vm.runInNewContext(source + '\nconfigureInstallCards();', context);
    return { popup, cancel, events };
}

test('phone popup closes immediately when cancelled, even if storage is blocked', () => {
    const page = setup({ blockedStorage: true });
    assert.equal(page.popup.open, true);
    page.cancel.click();
    assert.equal(page.popup.open, false);
});
test('dismissed, installed, and desktop visitors do not see the popup', () => {
    for (const options of [{ dismissed: true }, { standalone: true }, { phone: false }]) {
        assert.equal(setup(options).popup.open, false);
    }
});
test('native cancellation and completed installation close the popup', () => {
    const page = setup();
    page.events.cancel({ preventDefault() {} });
    assert.equal(page.popup.open, false);
    const installed = setup();
    installed.events.appinstalled();
    assert.equal(installed.popup.open, false);
});
