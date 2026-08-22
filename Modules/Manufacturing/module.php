<?php

return array (
  'name' => 'Manufacturing',
  'slug' => 'manufacturing',
  'type' => 'industry',
  'description' => 'Track BOMs, planning, work orders, shop floor activity, quality, costing, inventory, maintenance, batches, and production reporting.',
  'features' => 
  array (
    0 => 'Bill of Materials',
    1 => 'Production Planning',
    2 => 'Work Orders',
    3 => 'Shop Floor Management',
    4 => 'Quality Assurance',
    5 => 'Production Costing',
    6 => 'Inventory Control',
    7 => 'Machine Maintenance',
    8 => 'Batch Tracking',
    9 => 'Manufacturing Reports',
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
    'prefix' => '/api/v1/industries/manufacturing',
  ),
  'widgets' => 
  array (
    0 => 'manufacturing-overview',
    1 => 'manufacturing-reports',
    2 => 'manufacturing-bill-of-materials-summary',
    3 => 'manufacturing-production-planning-summary',
    4 => 'manufacturing-work-orders-summary',
    5 => 'manufacturing-shop-floor-management-summary',
    6 => 'manufacturing-quality-assurance-summary',
    7 => 'manufacturing-production-costing-summary',
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
    0 => 'manufacturing.view',
    1 => 'manufacturing.manage',
    2 => 'manufacturing.reports',
    3 => 'manufacturing.bill.of.materials.view',
    4 => 'manufacturing.production.planning.view',
    5 => 'manufacturing.work.orders.view',
    6 => 'manufacturing.shop.floor.management.view',
    7 => 'manufacturing.quality.assurance.view',
    8 => 'manufacturing.production.costing.view',
    9 => 'manufacturing.inventory.control.view',
    10 => 'manufacturing.machine.maintenance.view',
    11 => 'manufacturing.batch.tracking.view',
    12 => 'manufacturing.manufacturing.reports.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Bill of Materials',
      'module' => 'manufacturing-bill-of-materials',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Production Planning',
      'module' => 'manufacturing-production-planning',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Work Orders',
      'module' => 'manufacturing-work-orders',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Shop Floor Management',
      'module' => 'manufacturing-shop-floor-management',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Quality Assurance',
      'module' => 'manufacturing-quality-assurance',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Production Costing',
      'module' => 'manufacturing-production-costing',
      'icon' => 'bi-grid',
    ),
    6 => 
    array (
      'label' => 'Inventory Control',
      'module' => 'manufacturing-inventory-control',
      'icon' => 'bi-grid',
    ),
    7 => 
    array (
      'label' => 'Machine Maintenance',
      'module' => 'manufacturing-machine-maintenance',
      'icon' => 'bi-grid',
    ),
    8 => 
    array (
      'label' => 'Batch Tracking',
      'module' => 'manufacturing-batch-tracking',
      'icon' => 'bi-grid',
    ),
    9 => 
    array (
      'label' => 'Manufacturing Reports',
      'module' => 'manufacturing-manufacturing-reports',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Bill of Materials dashboard',
    1 => 'Production Planning dashboard',
    2 => 'Work Orders dashboard',
    3 => 'Shop Floor Management dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Manufacturing Standard',
      'description' => 'Track BOMs, planning, work orders, shop floor activity, quality, costing, inventory, maintenance, batches, and production reporting.',
      'dashboard_features' => 
      array (
        0 => 'Bill of Materials dashboard',
        1 => 'Production Planning dashboard',
        2 => 'Work Orders dashboard',
        3 => 'Shop Floor Management dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Manufacturing',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Manufacturing.',
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
      'name' => 'Enterprise Manufacturing',
      'description' => 'Scale Manufacturing operations with advanced controls, reports, workflows, and templates.',
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
