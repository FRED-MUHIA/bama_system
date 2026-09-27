const standaloneMedia = window.matchMedia('(display-mode: standalone)');
const isStandalone = () => standaloneMedia.matches || window.navigator.standalone === true;
const isIos = () => /iphone|ipad|ipod/i.test(window.navigator.userAgent);
const isAndroid = () => /android/i.test(window.navigator.userAgent);
const INSTALL_DISMISS_DAYS = 14;
const APP_UPDATE_INTERVAL = 5 * 60 * 1000;

let deferredInstallPrompt = null;

function runWhenIdle(callback, timeout = 3000) {
    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(callback, { timeout });
        return;
    }

    window.setTimeout(callback, Math.min(timeout, 1200));
}

function configureInstallCards() {
    const cards = document.querySelectorAll('[data-bama-install-card]');
    const footers = document.querySelectorAll('[data-bama-install-footer]');
    const iosHelpers = document.querySelectorAll('[data-bama-ios-install]');
    const androidHelpers = document.querySelectorAll('[data-bama-android-install]');
    const installButtons = document.querySelectorAll('[data-bama-install]');
    const dismissButtons = document.querySelectorAll('[data-bama-install-dismiss]');
    const popup = document.querySelector('[data-bama-install-popup]');
    const manualHelp = document.querySelector('[data-bama-install-manual]');
    const phoneMedia = window.matchMedia('(max-width: 767px)');
    let installed = false;
    let dismissedAt = 0;
    try { dismissedAt = Number(localStorage.getItem('bama-install-dismissed-at') || 0); } catch { /* Storage is optional. */ }
    let dismissed = dismissedAt > 0 && Date.now() - dismissedAt < INSTALL_DISMISS_DAYS * 24 * 60 * 60 * 1000;

    const render = () => {
        const canInstall = Boolean(deferredInstallPrompt) && !installed;
        const showIosHelp = isIos() && ! isStandalone() && !installed && ! dismissed;
        const showAndroidHelp = isAndroid() && ! isStandalone() && !installed && ! dismissed && canInstall;
        const showCard = ! isStandalone() && !installed && ! dismissed && (canInstall || showIosHelp);

        const showPopup = !isStandalone() && !installed && !dismissed && phoneMedia.matches && (isIos() || isAndroid() || canInstall);
        if (manualHelp) manualHelp.hidden = !showPopup || isIos() || canInstall;
        if (showPopup && popup && !popup.open) popup.showModal();
        if (!showPopup && popup?.open) popup.close();

        cards.forEach((card) => {
            card.hidden = ! showCard;
        });
        footers.forEach((footer) => {
            footer.hidden = ! showCard;
        });
        iosHelpers.forEach((helper) => {
            helper.hidden = ! showIosHelp;
        });
        androidHelpers.forEach((helper) => {
            helper.hidden = ! showAndroidHelp;
        });
        installButtons.forEach((button) => {
            button.hidden = ! canInstall;
        });
    };

    installButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            if (! deferredInstallPrompt) return;

            const prompt = deferredInstallPrompt;
            deferredInstallPrompt = null;
            button.disabled = true;
            try {
                await prompt.prompt();
                const choice = await prompt.userChoice;
                if (choice.outcome === 'accepted') installed = true;
                dismiss();
            } catch {
                render();
            } finally {
                button.disabled = false;
            }
        });
    });

    const dismiss = () => {
        dismissed = true;
        try { localStorage.setItem('bama-install-dismissed-at', String(Date.now())); } catch { /* Still dismiss for this page. */ }
        render();
    };
    dismissButtons.forEach((button) => button.addEventListener('click', dismiss));
    popup?.addEventListener('cancel', (event) => { event.preventDefault(); dismiss(); });
    popup?.addEventListener('click', (event) => { if (event.target === popup) {
        const bounds = popup.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dismiss();
    } });
    window.addEventListener('appinstalled', () => { installed = true; dismiss(); });
    standaloneMedia.addEventListener('change', render);
    phoneMedia.addEventListener('change', render);

    render();

    return render;
}

function configureConnectivity() {
    const banner = document.querySelector('[data-bama-offline]');
    const retry = document.querySelector('[data-bama-retry]');
    const render = () => {
        if (! banner) return;
        banner.hidden = window.navigator.onLine;
    };

    retry?.addEventListener('click', () => window.location.reload());
    window.addEventListener('online', render);
    window.addEventListener('offline', render);
    render();
}

function configurePageLoader() {
    const loader = document.querySelector('[data-bama-loader]');
    if (! loader) return;

    let hideTimer = null;

    const showLoader = () => {
        window.clearTimeout(hideTimer);
        loader.hidden = false;
        loader.classList.remove('is-hidden');
        document.documentElement.setAttribute('aria-busy', 'true');
    };

    const hideLoader = () => {
        loader.classList.add('is-hidden');
        document.documentElement.removeAttribute('aria-busy');
        hideTimer = window.setTimeout(() => {
            loader.hidden = true;
        }, 180);
    };

    window.BamaLoader = {
        show: showLoader,
        hide: hideLoader,
    };

    window.addEventListener('pageshow', () => window.setTimeout(hideLoader, 180));
    window.setTimeout(hideLoader, 350);
}

async function sha256(value) {
    const bytes = new TextEncoder().encode(value);
    const digest = await window.crypto.subtle.digest('SHA-256', bytes);

    return Array.from(new Uint8Array(digest), (byte) => byte.toString(16).padStart(2, '0')).join('');
}

async function checkForAppUpdate() {
    const currentVersion = document.querySelector('meta[name="bama-build-version"]')?.content;

    if (! currentVersion || currentVersion === 'development' || ! window.crypto?.subtle) return;

    try {
        const response = await fetch(`/build/manifest.json?update=${Date.now()}`, {
            cache: 'no-store',
            credentials: 'same-origin',
        });

        if (! response.ok) return;

        const latestVersion = await sha256(await response.text());
        const reloadKey = 'bama-auto-update-version';

        if (latestVersion === currentVersion) {
            sessionStorage.removeItem(reloadKey);
            return;
        }

        if (sessionStorage.getItem(reloadKey) === latestVersion) return;

        sessionStorage.setItem(reloadKey, latestVersion);
        window.location.reload();
    } catch {
        // Update checks are best-effort and should never interrupt offline use.
    }
}

function configureAutomaticAppUpdates(registration) {
    let controllerRefreshing = false;
    const activate = (worker) => worker?.postMessage({ type: 'SKIP_WAITING' });

    activate(registration.waiting);

    registration.addEventListener('updatefound', () => {
        const worker = registration.installing;
        if (! worker) return;

        worker.addEventListener('statechange', () => {
            if (worker.state === 'installed' && navigator.serviceWorker.controller) {
                activate(worker);
            }
        });
    });

    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (controllerRefreshing) return;
        controllerRefreshing = true;
        window.location.reload();
    });

    const check = () => {
        if (document.visibilityState === 'hidden') return;

        registration.update().catch(() => {});
        checkForAppUpdate();
    };

    runWhenIdle(check, 10000);
    window.setInterval(check, APP_UPDATE_INTERVAL);
    window.addEventListener('online', check);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') check();
    });
}

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    window.dispatchEvent(new CustomEvent('bama-install-ready'));
});

window.addEventListener('appinstalled', () => {
    deferredInstallPrompt = null;
    try { localStorage.setItem('bama-install-dismissed-at', String(Date.now())); } catch { /* Storage is optional. */ }
});

document.addEventListener('DOMContentLoaded', () => {
    const renderInstallCards = configureInstallCards();
    configureConnectivity();
    configurePageLoader();

    window.addEventListener('bama-install-ready', renderInstallCards);

    if ('serviceWorker' in navigator) {
        runWhenIdle(() => {
            const buildVersion = document.querySelector('meta[name="bama-build-version"]')?.content || 'development';

            navigator.serviceWorker.register(`/sw.js?v=${encodeURIComponent(buildVersion)}`)
                .then(configureAutomaticAppUpdates)
                .catch(() => {});
        });
    }
});
