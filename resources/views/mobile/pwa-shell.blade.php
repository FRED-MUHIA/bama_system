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

<dialog class="bama-install-popup" data-bama-install-popup aria-labelledby="bama-install-title" aria-describedby="bama-install-description">
    <img src="{{ asset('pwa-icons/icon-192.png') }}" width="64" height="64" alt="">
    <h2 id="bama-install-title">Install the Bama app</h2>
    <p id="bama-install-description">Open your business straight from your phone’s home screen.</p>
    <p data-bama-ios-install hidden>On iPhone, open this website in Safari, tap Share, then Add to Home Screen and Add.</p>
    <p data-bama-install-manual hidden>Open your browser menu and choose Install app or Add to Home screen, if available.</p>
    <div class="bama-install-actions">
        <button type="button" data-bama-install hidden>Install Bama</button>
        <button type="button" data-bama-install-dismiss autofocus>Not now</button>
    </div>
</dialog>

<div class="bama-offline-banner" data-bama-offline hidden>
    <strong>You're offline</strong>
    <span>Some actions need an internet connection.</span>
    <button type="button" data-bama-retry>Retry</button>
</div>
