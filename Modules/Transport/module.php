<?php

return array (
  'name' => 'Transport',
  'slug' => 'transport',
  'type' => 'industry',
  'description' => 'Operate vehicles, drivers, ticketing, routes, maintenance, fuel, and passenger workflows.',
  'features' => 
  array (
    0 => 'Vehicle Management',
    1 => 'Driver Management',
    2 => 'Ticketing',
    3 => 'Route Scheduling',
    4 => 'Fleet Maintenance',
    5 => 'Fuel Tracking',
    6 => 'Passenger Management',
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
    'prefix' => '/api/v1/industries/transport',
  ),
  'widgets' => 
  array (
    0 => 'transport-overview',
    1 => 'transport-reports',
    2 => 'transport-vehicle-management-summary',
    3 => 'transport-driver-management-summary',
    4 => 'transport-ticketing-summary',
    5 => 'transport-route-scheduling-summary',
    6 => 'transport-fleet-maintenance-summary',
    7 => 'transport-fuel-tracking-summary',
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
    0 => 'transport.view',
    1 => 'transport.manage',
    2 => 'transport.reports',
    3 => 'transport.vehicle.management.view',
    4 => 'transport.driver.management.view',
    5 => 'transport.ticketing.view',
    6 => 'transport.route.scheduling.view',
    7 => 'transport.fleet.maintenance.view',
    8 => 'transport.fuel.tracking.view',
    9 => 'transport.passenger.management.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Vehicle Management',
      'module' => 'transport-vehicle-management',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Driver Management',
      'module' => 'transport-driver-management',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Ticketing',
      'module' => 'transport-ticketing',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Route Scheduling',
      'module' => 'transport-route-scheduling',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Fleet Maintenance',
      'module' => 'transport-fleet-maintenance',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Fuel Tracking',
      'module' => 'transport-fuel-tracking',
      'icon' => 'bi-grid',
    ),
    6 => 
    array (
      'label' => 'Passenger Management',
      'module' => 'transport-passenger-management',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Vehicle Management dashboard',
    1 => 'Driver Management dashboard',
    2 => 'Ticketing dashboard',
    3 => 'Route Scheduling dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Transport Standard',
      'description' => 'Operate vehicles, drivers, ticketing, routes, maintenance, fuel, and passenger workflows.',
      'dashboard_features' => 
      array (
        0 => 'Vehicle Management dashboard',
        1 => 'Driver Management dashboard',
        2 => 'Ticketing dashboard',
        3 => 'Route Scheduling dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Transport',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Transport.',
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
      'name' => 'Enterprise Transport',
      'description' => 'Scale Transport operations with advanced controls, reports, workflows, and templates.',
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
