@php
    $retailNav = [
        ['Dashboard', 'retail.dashboard', 'retail.dashboard', 'bi-speedometer2'],
        ['Point of Sale', 'retail.pos.index', 'retail.pos.*', 'bi-upc-scan'],
        ['Products', 'retail.products.index', 'retail.products.*', 'bi-box-seam'],
        ['Inventory', 'retail.inventory.index', 'retail.inventory.*', 'bi-stack'],
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

<nav class="nav nav-pills gap-2 mb-3 flex-wrap">
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
