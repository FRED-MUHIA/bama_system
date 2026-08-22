@php
    $retailNav = [
        ['Dashboard', 'retail.dashboard', 'retail.dashboard', 'bi-speedometer2'],
        ['Point of Sale', 'retail.pos.index', 'retail.pos.*', 'bi-upc-scan'],
        ['Products', 'retail.products.index', 'retail.products.*', 'bi-box-seam'],
        ['Inventory', 'retail.inventory.index', 'retail.inventory.*', 'bi-stack'],
        ['Warehousing', 'retail.warehousing.index', 'retail.warehousing.*', 'bi-buildings'],
        ['Orders', 'retail.orders.index', 'retail.orders.*', 'bi-bag-check'],
        ['Customers', 'retail.customers.index', 'retail.customers.*', 'bi-people'],
        ['Loyalty Programs', 'retail.loyalty.index', 'retail.loyalty.*', 'bi-gem'],
        ['Promotions', 'retail.promotions.index', 'retail.promotions.*', 'bi-percent'],
        ['Gift Cards', 'retail.gift-cards.index', 'retail.gift-cards.*', 'bi-credit-card-2-front'],
        ['Returns', 'retail.returns.index', 'retail.returns.*', 'bi-arrow-counterclockwise'],
        ['Procurement', 'retail.procurement.index', 'retail.procurement.*', 'bi-cart-check'],
        ['Suppliers', 'retail.suppliers.index', 'retail.suppliers.*', 'bi-truck'],
        ['Branches', 'retail.branches.index', 'retail.branches.*', 'bi-diagram-3'],
        ['Ecommerce', 'retail.ecommerce.index', 'retail.ecommerce.*', 'bi-cloud-arrow-up'],
        ['Smart Scanning', 'retail.scanning.index', 'retail.scanning.*', 'bi-qr-code-scan'],
        ['Analytics', 'retail.analytics.index', 'retail.analytics.*', 'bi-graph-up'],
        ['Reports', 'retail.reports.index', 'retail.reports.*', 'bi-bar-chart'],
        ['Settings', 'retail.settings.index', 'retail.settings.*', 'bi-gear'],
    ];
@endphp

<nav class="nav nav-pills gap-2 mb-3 flex-wrap">
    @foreach($retailNav as [$label, $route, $match, $icon])
        <a class="nav-link {{ request()->routeIs($match) ? 'active' : '' }}" href="{{ route($route) }}">
            <i class="bi {{ $icon }} me-1"></i>{{ $label }}
        </a>
    @endforeach
</nav>
