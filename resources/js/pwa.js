const standaloneMedia = window.matchMedia('(display-mode: standalone)');
const isStandalone = () => standaloneMedia.matches || window.navigator.standalone === true;
const isIos = () => /iphone|ipad|ipod/i.test(window.navigator.userAgent);
const isAndroid = () => /android/i.test(window.navigator.userAgent);
const INSTALL_DISMISS_DAYS = 14;

let deferredInstallPrompt = null;
let waitingWorker = null;

function showElement(element) {
    if (element) element.hidden = false;
}

function hideElement(element) {
    if (element) element.hidden = true;
}

function configureInstallCards() {
    const cards = document.querySelectorAll('[data-bama-install-card]');
    const footers = document.querySelectorAll('[data-bama-install-footer]');
    const iosHelpers = document.querySelectorAll('[data-bama-ios-install]');
    const androidHelpers = document.querySelectorAll('[data-bama-android-install]');
    const installButtons = document.querySelectorAll('[data-bama-install]');
    const dismissButtons = document.querySelectorAll('[data-bama-install-dismiss]');
    const dismissedAt = Number(localStorage.getItem('bama-install-dismissed-at') || 0);
    const dismissed = dismissedAt > 0 && Date.now() - dismissedAt < INSTALL_DISMISS_DAYS * 24 * 60 * 60 * 1000;

    const render = () => {
        const canInstall = Boolean(deferredInstallPrompt);
        const showIosHelp = isIos() && ! isStandalone() && ! dismissed;
        const showAndroidHelp = isAndroid() && ! isStandalone() && ! dismissed && canInstall;
        const showCard = ! isStandalone() && ! dismissed && (canInstall || showIosHelp);

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

            deferredInstallPrompt.prompt();
            await deferredInstallPrompt.userChoice;
            deferredInstallPrompt = null;
            render();
        });
    });

    dismissButtons.forEach((button) => {
        button.addEventListener('click', () => {
            localStorage.setItem('bama-install-dismissed-at', String(Date.now()));
            render();
        });
    });

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

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (! link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        if (link.target && link.target.toLowerCase() !== '_self') return;
        if (link.hasAttribute('download') || link.hasAttribute('data-no-page-loader')) return;

        const destination = new URL(link.href, window.location.href);
        const currentWithoutHash = `${window.location.origin}${window.location.pathname}${window.location.search}`;
        const destinationWithoutHash = `${destination.origin}${destination.pathname}${destination.search}`;
        if (destination.origin !== window.location.origin || destinationWithoutHash === currentWithoutHash) return;

        window.setTimeout(() => {
            if (! event.defaultPrevented) showLoader();
        }, 0);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (form.hasAttribute('data-no-page-loader') || (form.target && form.target.toLowerCase() !== '_self')) return;

        window.setTimeout(() => {
            if (! event.defaultPrevented) showLoader();
        }, 0);
    });

    window.addEventListener('beforeunload', showLoader);
    window.addEventListener('pageshow', () => window.setTimeout(hideLoader, 180));
    window.setTimeout(hideLoader, 350);
}

function configureServiceWorkerUpdate(registration) {
    const toast = document.querySelector('[data-bama-update]');
    const updateButton = document.querySelector('[data-bama-update-now]');

    const showUpdate = (worker) => {
        waitingWorker = worker;
        showElement(toast);
    };

    updateButton?.addEventListener('click', () => {
        waitingWorker?.postMessage({ type: 'SKIP_WAITING' });
    });

    if (registration.waiting) {
        showUpdate(registration.waiting);
    }

    registration.addEventListener('updatefound', () => {
        const worker = registration.installing;
        if (! worker) return;

        worker.addEventListener('statechange', () => {
            if (worker.state === 'installed' && navigator.serviceWorker.controller) {
                showUpdate(worker);
            }
        });
    });

    navigator.serviceWorker.addEventListener('controllerchange', () => {
        hideElement(toast);
        window.location.reload();
    });
}

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    window.dispatchEvent(new CustomEvent('bama-install-ready'));
});

window.addEventListener('appinstalled', () => {
    deferredInstallPrompt = null;
    localStorage.setItem('bama-install-dismissed-at', String(Date.now()));
});

document.addEventListener('DOMContentLoaded', () => {
    const renderInstallCards = configureInstallCards();
    configureConnectivity();
    configurePageLoader();

    window.addEventListener('bama-install-ready', renderInstallCards);

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(configureServiceWorkerUpdate)
            .catch(() => {});
    }
});
