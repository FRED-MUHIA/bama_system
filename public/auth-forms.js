(() => {
    if (window.bamaAuthFormsReady) return;
    window.bamaAuthFormsReady = true;
    const pending = new Map();
    const reset = (form) => {
        const state = pending.get(form);
        if (!state) return;
        clearTimeout(state.timer);
        state.button.disabled = false;
        state.button.innerHTML = state.label;
        state.button.removeAttribute('aria-busy');
        pending.delete(form);
    };
    const message = (form, text) => {
        let notice = form.querySelector('[data-auth-feedback]');
        if (!notice) {
            notice = document.createElement('p');
            notice.dataset.authFeedback = '';
            notice.setAttribute('role', 'status');
            form.append(notice);
        }
        notice.textContent = text;
    };
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!form.closest('.auth-layout, .registration-page, .website-auth') || event.defaultPrevented) return;
        if (pending.has(form)) {
            event.preventDefault();
            return;
        }
        if (!navigator.onLine) {
            event.preventDefault();
            message(form, 'You are offline. Reconnect to the internet, then try again.');
            return;
        }
        const button = event.submitter || form.querySelector('button[type="submit"]');
        if (!button || button.disabled) return;
        const label = button.innerHTML;
        message(form, '');
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.textContent = 'Please wait…';
        const timer = setTimeout(() => {
            reset(form);
            message(form, 'This is taking longer than expected. Check your connection. If you submitted account setup, try signing in before submitting again.');
        }, 20000);
        pending.set(form, { button, label, timer });
    });
    window.addEventListener('pageshow', () => {
        for (const form of pending.keys()) reset(form);
    });
})();
