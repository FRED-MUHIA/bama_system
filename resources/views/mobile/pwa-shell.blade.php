<div class="bama-loading-screen" data-bama-loader role="status" aria-live="polite" aria-label="Loading">
    <span class="bama-radial-loader" aria-hidden="true">
        @for ($spoke = 0; $spoke < 16; $spoke++)
            <span style="--spoke: {{ $spoke }}"></span>
        @endfor
    </span>
</div>
<noscript><style>.bama-loading-screen { display: none !important; }</style></noscript>

<div class="bama-offline-banner" data-bama-offline hidden>
    <strong>You're offline</strong>
    <span>Some actions need an internet connection.</span>
    <button type="button" data-bama-retry>Retry</button>
</div>

<div class="bama-update-toast" data-bama-update hidden>
    <span>A new Bama version is available.</span>
    <button type="button" data-bama-update-now>Update Now</button>
</div>
