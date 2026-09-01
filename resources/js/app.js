import './bootstrap';
import './pwa';

document.addEventListener('DOMContentLoaded', () => {
    const mobileMedia = window.matchMedia('(max-width: 991px)');
    const desktopMedia = window.matchMedia('(min-width: 992px)');
    const eagerImageCount = mobileMedia.matches ? 1 : 2;

    document.querySelectorAll('img').forEach((image, index) => {
        image.decoding = image.decoding || 'async';

        if (index < eagerImageCount) {
            if (!image.hasAttribute('fetchpriority')) {
                image.fetchPriority = 'high';
            }

            return;
        }

        if (!image.hasAttribute('loading')) {
            image.loading = 'lazy';
        }

        if (!image.hasAttribute('fetchpriority')) {
            image.fetchPriority = 'low';
        }
    });

    const hydrateDeferredTemplates = () => {
        if (!desktopMedia.matches) return;

        document.querySelectorAll('[data-defer-template]').forEach((target) => {
            if (target.dataset.deferredHydrated === 'true') return;

            const template = document.getElementById(target.dataset.deferTemplate);
            if (!(template instanceof HTMLTemplateElement)) return;

            target.appendChild(template.content.cloneNode(true));
            target.dataset.deferredHydrated = 'true';
        });
    };

    hydrateDeferredTemplates();

    if (desktopMedia.addEventListener) {
        desktopMedia.addEventListener('change', hydrateDeferredTemplates);
    } else if (desktopMedia.addListener) {
        desktopMedia.addListener(hydrateDeferredTemplates);
    }

    document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        const targetId = toggle.getAttribute('data-password-toggle');
        const password = targetId ? document.getElementById(targetId) : toggle.closest('.password-wrap')?.querySelector('input');

        if (!(password instanceof HTMLInputElement)) return;

        toggle.addEventListener('click', () => {
            const show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            const icon = toggle.querySelector('i');

            if (icon) {
                icon.className = `bi bi-eye${show ? '-slash' : ''}`;
            }
        });
    });
});
