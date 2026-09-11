<div class="bama-loading-screen" data-bama-loader role="status" aria-live="polite" aria-label="Loading" hidden>
    <div class="bama-splash-content">
        <x-bama-logo variant="splash" mark alt="" aria-hidden="true" />
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

        try {
            const sessionKey = 'bama-initial-loader-shown';

            if (!sessionStorage.getItem(sessionKey)) {
                sessionStorage.setItem(sessionKey, 'true');
                loader.hidden = false;
            }
        } catch (error) {
            loader.hidden = false;
        }
    })();
</script>
<noscript><style>.bama-loading-screen { display: none !important; }</style></noscript>

<div class="bama-offline-banner" data-bama-offline hidden>
    <strong>You're offline</strong>
    <span>Some actions need an internet connection.</span>
    <button type="button" data-bama-retry>Retry</button>
</div>
