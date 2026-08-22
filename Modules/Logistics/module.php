<?php

return array (
  'name' => 'Logistics',
  'slug' => 'logistics',
  'type' => 'industry',
  'description' => 'Manage fleets, shipments, routes, delivery schedules, drivers, fuel, vehicle maintenance, billing, and warehouse operations.',
  'features' => 
  array (
    0 => 'Fleet Management',
    1 => 'Shipment Tracking',
    2 => 'Route Planning',
    3 => 'Delivery Scheduling',
    4 => 'Driver Management',
    5 => 'Fuel Management',
    6 => 'Vehicle Maintenance',
    7 => 'Logistics Billing',
    8 => 'Warehouse Operations',
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
    'prefix' => '/api/v1/industries/logistics',
  ),
  'widgets' => 
  array (
    0 => 'logistics-overview',
    1 => 'logistics-reports',
    2 => 'logistics-fleet-management-summary',
    3 => 'logistics-shipment-tracking-summary',
    4 => 'logistics-route-planning-summary',
    5 => 'logistics-delivery-scheduling-summary',
    6 => 'logistics-driver-management-summary',
    7 => 'logistics-fuel-management-summary',
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
    0 => 'logistics.view',
    1 => 'logistics.manage',
    2 => 'logistics.reports',
    3 => 'logistics.fleet.management.view',
    4 => 'logistics.shipment.tracking.view',
    5 => 'logistics.route.planning.view',
    6 => 'logistics.delivery.scheduling.view',
    7 => 'logistics.driver.management.view',
    8 => 'logistics.fuel.management.view',
    9 => 'logistics.vehicle.maintenance.view',
    10 => 'logistics.logistics.billing.view',
    11 => 'logistics.warehouse.operations.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Fleet Management',
      'module' => 'logistics-fleet-management',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Shipment Tracking',
      'module' => 'logistics-shipment-tracking',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Route Planning',
      'module' => 'logistics-route-planning',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Delivery Scheduling',
      'module' => 'logistics-delivery-scheduling',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Driver Management',
      'module' => 'logistics-driver-management',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Fuel Management',
      'module' => 'logistics-fuel-management',
      'icon' => 'bi-grid',
    ),
    6 => 
    array (
      'label' => 'Vehicle Maintenance',
      'module' => 'logistics-vehicle-maintenance',
      'icon' => 'bi-grid',
    ),
    7 => 
    array (
      'label' => 'Logistics Billing',
      'module' => 'logistics-logistics-billing',
      'icon' => 'bi-grid',
    ),
    8 => 
    array (
      'label' => 'Warehouse Operations',
      'module' => 'logistics-warehouse-operations',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Fleet Management dashboard',
    1 => 'Shipment Tracking dashboard',
    2 => 'Route Planning dashboard',
    3 => 'Delivery Scheduling dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Logistics Standard',
      'description' => 'Manage fleets, shipments, routes, delivery schedules, drivers, fuel, vehicle maintenance, billing, and warehouse operations.',
      'dashboard_features' => 
      array (
        0 => 'Fleet Management dashboard',
        1 => 'Shipment Tracking dashboard',
        2 => 'Route Planning dashboard',
        3 => 'Delivery Scheduling dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Logistics',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Logistics.',
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
      'name' => 'Enterprise Logistics',
      'description' => 'Scale Logistics operations with advanced controls, reports, workflows, and templates.',
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
