import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const isMobile = window.matchMedia('(max-width: 991px)').matches;

    document.querySelectorAll('img').forEach((image, index) => {
        image.decoding = image.decoding || 'async';

        if (!image.hasAttribute('loading') && (!isMobile || index > 1)) {
            image.loading = 'lazy';
        }

        if (isMobile && index > 1 && !image.hasAttribute('fetchpriority')) {
            image.fetchPriority = 'low';
        }
    });
});
