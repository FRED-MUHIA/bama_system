<?php

return array (
  'name' => 'Restaurant',
  'slug' => 'restaurant',
  'type' => 'industry',
  'description' => 'Control restaurant POS, kitchen display, tables, reservations, online orders, delivery, menu, recipe costing, and loyalty.',
  'features' => 
  array (
    0 => 'Restaurant POS',
    1 => 'Kitchen Display System',
    2 => 'Table Management',
    3 => 'Reservations',
    4 => 'Online Ordering',
    5 => 'Delivery Management',
    6 => 'Menu Management',
    7 => 'Recipe Costing',
    8 => 'Loyalty Programs',
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
    'prefix' => '/api/v1/industries/restaurant',
  ),
  'widgets' => 
  array (
    0 => 'restaurant-overview',
    1 => 'restaurant-reports',
    2 => 'restaurant-restaurant-pos-summary',
    3 => 'restaurant-kitchen-display-system-summary',
    4 => 'restaurant-table-management-summary',
    5 => 'restaurant-reservations-summary',
    6 => 'restaurant-online-ordering-summary',
    7 => 'restaurant-delivery-management-summary',
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
    0 => 'restaurant.view',
    1 => 'restaurant.manage',
    2 => 'restaurant.reports',
    3 => 'restaurant.restaurant.pos.view',
    4 => 'restaurant.kitchen.display.system.view',
    5 => 'restaurant.table.management.view',
    6 => 'restaurant.reservations.view',
    7 => 'restaurant.online.ordering.view',
    8 => 'restaurant.delivery.management.view',
    9 => 'restaurant.menu.management.view',
    10 => 'restaurant.recipe.costing.view',
    11 => 'restaurant.loyalty.programs.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Restaurant POS',
      'module' => 'restaurant-restaurant-pos',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Kitchen Display System',
      'module' => 'restaurant-kitchen-display-system',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Table Management',
      'module' => 'restaurant-table-management',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Reservations',
      'module' => 'restaurant-reservations',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Online Ordering',
      'module' => 'restaurant-online-ordering',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Delivery Management',
      'module' => 'restaurant-delivery-management',
      'icon' => 'bi-grid',
    ),
    6 => 
    array (
      'label' => 'Menu Management',
      'module' => 'restaurant-menu-management',
      'icon' => 'bi-grid',
    ),
    7 => 
    array (
      'label' => 'Recipe Costing',
      'module' => 'restaurant-recipe-costing',
      'icon' => 'bi-grid',
    ),
    8 => 
    array (
      'label' => 'Loyalty Programs',
      'module' => 'restaurant-loyalty-programs',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Restaurant POS dashboard',
    1 => 'Kitchen Display System dashboard',
    2 => 'Table Management dashboard',
    3 => 'Reservations dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Restaurant Standard',
      'description' => 'Control restaurant POS, kitchen display, tables, reservations, online orders, delivery, menu, recipe costing, and loyalty.',
      'dashboard_features' => 
      array (
        0 => 'Restaurant POS dashboard',
        1 => 'Kitchen Display System dashboard',
        2 => 'Table Management dashboard',
        3 => 'Reservations dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Restaurant',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Restaurant.',
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
      'name' => 'Enterprise Restaurant',
      'description' => 'Scale Restaurant operations with advanced controls, reports, workflows, and templates.',
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
