<?php

return array (
  'name' => 'Government',
  'slug' => 'government',
  'type' => 'industry',
  'description' => 'Manage citizen records, licensing, permits, revenue collection, assets, service requests, and public projects.',
  'features' => 
  array (
    0 => 'Citizen Records',
    1 => 'Licensing',
    2 => 'Permits',
    3 => 'Revenue Collection',
    4 => 'Asset Management',
    5 => 'Service Requests',
    6 => 'Public Projects',
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
    'prefix' => '/api/v1/industries/government',
  ),
  'widgets' => 
  array (
    0 => 'government-overview',
    1 => 'government-reports',
    2 => 'government-citizen-records-summary',
    3 => 'government-licensing-summary',
    4 => 'government-permits-summary',
    5 => 'government-revenue-collection-summary',
    6 => 'government-asset-management-summary',
    7 => 'government-service-requests-summary',
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
    0 => 'government.view',
    1 => 'government.manage',
    2 => 'government.reports',
    3 => 'government.citizen.records.view',
    4 => 'government.licensing.view',
    5 => 'government.permits.view',
    6 => 'government.revenue.collection.view',
    7 => 'government.asset.management.view',
    8 => 'government.service.requests.view',
    9 => 'government.public.projects.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Citizen Records',
      'module' => 'government-citizen-records',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Licensing',
      'module' => 'government-licensing',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Permits',
      'module' => 'government-permits',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Revenue Collection',
      'module' => 'government-revenue-collection',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Asset Management',
      'module' => 'government-asset-management',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Service Requests',
      'module' => 'government-service-requests',
      'icon' => 'bi-grid',
    ),
    6 => 
    array (
      'label' => 'Public Projects',
      'module' => 'government-public-projects',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Citizen Records dashboard',
    1 => 'Licensing dashboard',
    2 => 'Permits dashboard',
    3 => 'Revenue Collection dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Government Standard',
      'description' => 'Manage citizen records, licensing, permits, revenue collection, assets, service requests, and public projects.',
      'dashboard_features' => 
      array (
        0 => 'Citizen Records dashboard',
        1 => 'Licensing dashboard',
        2 => 'Permits dashboard',
        3 => 'Revenue Collection dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Government',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Government.',
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
      'name' => 'Enterprise Government',
      'description' => 'Scale Government operations with advanced controls, reports, workflows, and templates.',
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
