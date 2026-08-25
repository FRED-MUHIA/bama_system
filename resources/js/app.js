import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const mobileMedia = window.matchMedia('(max-width: 991px)');
    const desktopMedia = window.matchMedia('(min-width: 992px)');
    const isMobile = mobileMedia.matches;

    document.querySelectorAll('img').forEach((image, index) => {
        image.decoding = image.decoding || 'async';

        if (!image.hasAttribute('loading') && (!isMobile || index > 1)) {
            image.loading = 'lazy';
        }

        if (isMobile && index > 1 && !image.hasAttribute('fetchpriority')) {
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
});
