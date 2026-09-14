<div class="bama-loading-screen" data-bama-loader role="status" aria-live="polite" aria-label="Loading" hidden>
    <div class="bama-splash-content">
        <x-bama-logo variant="splash" mark :src="asset('images/bama-splash-icon.png')" alt="" aria-hidden="true" />
        <strong class="bama-splash-name">BAMA</strong>
        <span class="bama-splash-tagline">Business Management Anywhere</span>
        <span class="bama-radial-loader" aria-hidden="true">
            @for ($spoke = 0; $spoke < 8; $spoke++)
                <span style="--spoke: {{ $spoke }}"></span>
            @endfor
        </span>
    </div>
</div>
<script>
    (() => {
        const loader = document.currentScript.previousElementSibling;
        const authPath = /^\/?(app\/login|login|owner\/login|portal\/login|public\/login|public\/owner\/login|public\/portal\/login|register|forgot-password|reset-password)(\/|$)/.test(window.location.pathname);

        const hideLoader = () => {
            loader.classList.add('is-hidden');
            window.setTimeout(() => {
                loader.hidden = true;
            }, 180);
        };

        const showInitialLoader = () => {
            loader.hidden = false;

            const hideAfterPaint = () => window.setTimeout(hideLoader, 120);

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', hideAfterPaint, { once: true });
            } else {
                hideAfterPaint();
            }

            window.addEventListener('pageshow', hideLoader, { once: true });
            window.setTimeout(hideLoader, 700);
        };

        if (authPath) {
            loader.hidden = true;
            return;
        }

        try {
            const sessionKey = 'bama-initial-loader-shown';

            if (!sessionStorage.getItem(sessionKey)) {
                sessionStorage.setItem(sessionKey, 'true');
                showInitialLoader();
            }
        } catch (error) {
            showInitialLoader();
        }
    })();
</script>
<noscript><style>.bama-loading-screen { display: none !important; }</style></noscript>

<div class="bama-offline-banner" data-bama-offline hidden>
    <strong>You're offline</strong>
    <span>Some actions need an internet connection.</span>
    <button type="button" data-bama-retry>Retry</button>
</div>
