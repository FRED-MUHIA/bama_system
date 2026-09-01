const standaloneMedia = window.matchMedia('(display-mode: standalone)');
const isStandalone = () => standaloneMedia.matches || window.navigator.standalone === true;
const isIos = () => /iphone|ipad|ipod/i.test(window.navigator.userAgent);

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
    const iosHelpers = document.querySelectorAll('[data-bama-ios-install]');
    const installButtons = document.querySelectorAll('[data-bama-install]');
    const dismissButtons = document.querySelectorAll('[data-bama-install-dismiss]');
    const dismissed = localStorage.getItem('bama-install-dismissed') === '1';

    const render = () => {
        const canInstall = Boolean(deferredInstallPrompt);
        const showIosHelp = isIos() && ! isStandalone() && ! dismissed;
        const showCard = ! isStandalone() && ! dismissed && (canInstall || showIosHelp);

        cards.forEach((card) => {
            card.hidden = ! showCard;
        });
        iosHelpers.forEach((helper) => {
            helper.hidden = ! showIosHelp;
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
            localStorage.setItem('bama-install-dismissed', '1');
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

function configureSplash() {
    const splash = document.querySelector('[data-bama-splash]');
    if (! splash) return;

    const hideSplash = () => {
        splash.classList.add('is-hidden');
        window.setTimeout(() => splash.remove(), 260);
    };

    if (! isStandalone()) {
        splash.remove();
        return;
    }

    window.setTimeout(hideSplash, 450);
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
    localStorage.setItem('bama-install-dismissed', '1');
});

document.addEventListener('DOMContentLoaded', () => {
    const renderInstallCards = configureInstallCards();
    configureConnectivity();
    configureSplash();

    window.addEventListener('bama-install-ready', renderInstallCards);

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(configureServiceWorkerUpdate)
            .catch(() => {});
    }
});
