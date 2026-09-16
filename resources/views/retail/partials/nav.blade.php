@php
    $retailNav = [
        ['Dashboard', 'retail.dashboard', 'retail.dashboard', 'bi-speedometer2'],
        ['Make a Sale', 'retail.pos.index', 'retail.pos.*', 'bi-upc-scan'],
        ['Products', 'retail.products.index', 'retail.products.*', 'bi-box-seam'],
        ['Inventory', 'retail.inventory.index', 'retail.inventory.*', 'bi-stack'],
        ['Transactions', 'retail.transactions.index', 'retail.transactions.*', 'bi-receipt'],
        ['Orders', 'retail.orders.index', 'retail.orders.*', 'bi-bag-check'],
        ['Customers', 'retail.customers.index', 'retail.customers.*', 'bi-people'],
        ['Reports', 'retail.reports.index', 'retail.reports.*', 'bi-bar-chart'],
    ];

    $retailMoreNav = [
        ['Returns', 'retail.returns.index', 'retail.returns.*', 'bi-arrow-counterclockwise'],
        ['Warehousing', 'retail.warehousing.index', 'retail.warehousing.*', 'bi-buildings'],
        ['Suppliers', 'retail.suppliers.index', 'retail.suppliers.*', 'bi-truck'],
        ['Procurement', 'retail.procurement.index', 'retail.procurement.*', 'bi-cart-check'],
        ['Branches', 'retail.branches.index', 'retail.branches.*', 'bi-diagram-3'],
        ['Website', 'retail.ecommerce.index', 'retail.ecommerce.*', 'bi-globe2'],
        ['Loyalty', 'retail.loyalty.index', 'retail.loyalty.*', 'bi-gem'],
        ['Promotions', 'retail.promotions.index', 'retail.promotions.*', 'bi-percent'],
        ['Gift Cards', 'retail.gift-cards.index', 'retail.gift-cards.*', 'bi-credit-card-2-front'],
        ['Scanning', 'retail.scanning.index', 'retail.scanning.*', 'bi-qr-code-scan'],
        ['Analytics', 'retail.analytics.index', 'retail.analytics.*', 'bi-graph-up'],
        ['Settings', 'retail.settings.index', 'retail.settings.*', 'bi-gear'],
    ];

    $retailMoreActive = collect($retailMoreNav)->contains(fn ($item) => request()->routeIs($item[2]));
@endphp

@once
    <style>
        .retail-nav .nav-link.active,
        .retail-nav .nav-link.active i {
            color:#fff !important;
            -webkit-text-fill-color:#fff;
        }

        .retail-nav .nav-link.active {
            background:var(--bama-ink,#111827) !important;
            border-color:var(--bama-ink,#111827) !important;
        }
        .retail-page, .retail-page .card, .retail-page .pos-band {min-width:0;overflow-wrap:anywhere}
        .retail-page .form-control, .retail-page .form-select {min-width:0;max-width:100%}
        @media(max-width:767px){
            .retail-page .retail-nav{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}
            .retail-page .retail-nav .nav-link{white-space:normal;width:100%;height:100%}
            .retail-page .retail-nav .dropdown-menu{max-width:calc(100vw - 2rem);min-width:0}
            .retail-page .d-flex:not(.retail-nav){flex-wrap:wrap}
            .retail-page .pos-list-row{flex-direction:column}
            .retail-page .pos-list-row .text-end{text-align:left!important}
            .retail-page .table-responsive{overflow:visible}
            .retail-page table.table, .retail-page table.table tbody{display:block;width:100%;min-width:0!important}
            .retail-page table.table thead{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)}
            .retail-page table.table tr{display:block;border-bottom:1px solid #d9dee8;padding:.5rem}
            .retail-page table.table tr[hidden]{display:none!important}
            .retail-page table.table td{display:block;width:100%!important;min-width:0!important;white-space:normal!important;text-align:left!important;border:0;overflow-wrap:anywhere}
            .retail-page table.table td[data-label]::before{content:attr(data-label);display:block;font-size:.75rem;font-weight:700;color:#667085;margin-bottom:.25rem}
            .retail-page .pagination{flex-wrap:wrap}
        }
    </style>
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const page = document.querySelector('.retail-nav')?.closest('section');
            if (!page) return;
            page.querySelectorAll('table.table').forEach(table => {
                const headings = Array.from(table.querySelectorAll('thead th'), th => th.textContent.trim());
                table.querySelectorAll('tbody tr').forEach(row => {
                    Array.from(row.cells).forEach((cell, index) => {
                        if (cell.colSpan === 1 && headings[index]) cell.dataset.label = headings[index];
                    });
                });
            });
        });
    </script>
    @endpush
@endonce

<nav class="nav nav-pills gap-2 mb-3 flex-wrap retail-nav">
    @foreach($retailNav as [$label, $route, $match, $icon])
        <a class="nav-link {{ request()->routeIs($match) ? 'active' : '' }}" href="{{ route($route) }}">
            <i class="bi {{ $icon }} me-1"></i>{{ $label }}
        </a>
    @endforeach
    <div class="dropdown">
        <button class="nav-link dropdown-toggle {{ $retailMoreActive ? 'active' : '' }}" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-three-dots me-1"></i>More
        </button>
        <ul class="dropdown-menu">
            @foreach($retailMoreNav as [$label, $route, $match, $icon])
                <li>
                    <a class="dropdown-item {{ request()->routeIs($match) ? 'active' : '' }}" href="{{ route($route) }}">
                        <i class="bi {{ $icon }} me-2"></i>{{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</nav>
