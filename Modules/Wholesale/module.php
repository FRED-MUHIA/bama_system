<?php

return array (
  'name' => 'Wholesale & Distribution',
  'slug' => 'wholesale',
  'type' => 'industry',
  'description' => 'Operate inventory, warehouses, distribution routes, sales orders, purchase orders, fleet, pricing, transfers, and forecasting.',
  'features' => 
  array (
    0 => 'Inventory',
    1 => 'Warehousing',
    2 => 'Distribution Routes',
    3 => 'Sales Orders',
    4 => 'Purchase Orders',
    5 => 'Fleet Tracking',
    6 => 'Customer Pricing',
    7 => 'Stock Transfers',
    8 => 'Demand Forecasting',
  ),
  'core_modules' => 
  array (
    0 => 'CRM',
    1 => 'Projects',
    2 => 'Finance',
    3 => 'Accounting',
    4 => 'Documents',
    5 => 'Reporting',
    6 => 'HR',
    7 => 'Administration',
    8 => 'Portal',
    9 => 'Notifications',
  ),
  'routes' => 
  array (
  ),
  'api' => 
  array (
    'prefix' => '/api/v1/industries/wholesale',
  ),
  'widgets' => 
  array (
    0 => 'wholesale-overview',
    1 => 'wholesale-reports',
    2 => 'wholesale-inventory-summary',
    3 => 'wholesale-warehousing-summary',
    4 => 'wholesale-distribution-routes-summary',
    5 => 'wholesale-sales-orders-summary',
    6 => 'wholesale-purchase-orders-summary',
    7 => 'wholesale-fleet-tracking-summary',
  ),
  'reports' => 
  array (
    0 => 'Executive summary',
    1 => 'Operational performance',
    2 => 'Compliance report',
    3 => 'Financial performance',
  ),
  'workflows' => 
  array (
    0 => 'Create',
    1 => 'Review',
    2 => 'Approve',
    3 => 'Post',
    4 => 'Report',
  ),
  'templates' => 
  array (
    0 => 'Default dashboard',
    1 => 'Management report',
    2 => 'Approval workflow',
    3 => 'Document template',
  ),
  'permissions' => 
  array (
    0 => 'wholesale.view',
    1 => 'wholesale.manage',
    2 => 'wholesale.reports',
    3 => 'wholesale.inventory.view',
    4 => 'wholesale.warehousing.view',
    5 => 'wholesale.distribution.routes.view',
    6 => 'wholesale.sales.orders.view',
    7 => 'wholesale.purchase.orders.view',
    8 => 'wholesale.fleet.tracking.view',
    9 => 'wholesale.customer.pricing.view',
    10 => 'wholesale.stock.transfers.view',
    11 => 'wholesale.demand.forecasting.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Inventory',
      'module' => 'wholesale-inventory',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Warehousing',
      'module' => 'wholesale-warehousing',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Distribution Routes',
      'module' => 'wholesale-distribution-routes',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Sales Orders',
      'module' => 'wholesale-sales-orders',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Purchase Orders',
      'module' => 'wholesale-purchase-orders',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Fleet Tracking',
      'module' => 'wholesale-fleet-tracking',
      'icon' => 'bi-grid',
    ),
    6 => 
    array (
      'label' => 'Customer Pricing',
      'module' => 'wholesale-customer-pricing',
      'icon' => 'bi-grid',
    ),
    7 => 
    array (
      'label' => 'Stock Transfers',
      'module' => 'wholesale-stock-transfers',
      'icon' => 'bi-grid',
    ),
    8 => 
    array (
      'label' => 'Demand Forecasting',
      'module' => 'wholesale-demand-forecasting',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Inventory dashboard',
    1 => 'Warehousing dashboard',
    2 => 'Distribution Routes dashboard',
    3 => 'Sales Orders dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Wholesale & Distribution Standard',
      'description' => 'Operate inventory, warehouses, distribution routes, sales orders, purchase orders, fleet, pricing, transfers, and forecasting.',
      'dashboard_features' => 
      array (
        0 => 'Inventory dashboard',
        1 => 'Warehousing dashboard',
        2 => 'Distribution Routes dashboard',
        3 => 'Sales Orders dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Wholesale & Distribution',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Wholesale & Distribution.',
      'dashboard_features' => 
      array (
        0 => 'Branch performance',
        1 => 'Team workload',
        2 => 'Consolidated reporting',
        3 => 'Approval queue',
      ),
    ),
    2 => 
    array (
      'slug' => 'enterprise',
      'name' => 'Enterprise Wholesale & Distribution',
      'description' => 'Scale Wholesale & Distribution operations with advanced controls, reports, workflows, and templates.',
      'dashboard_features' => 
      array (
        0 => 'Executive KPIs',
        1 => 'Risk alerts',
        2 => 'Workflow performance',
        3 => 'Compliance status',
      ),
    ),
  ),
  'tenant_isolated' => true,
  'role_permissions' => true,
  'dynamic_menus' => true,
  'dashboard_widgets' => true,
  'api_endpoints' => true,
  'subscription_activated' => true,
);
