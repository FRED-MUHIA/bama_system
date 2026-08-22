<?php

return array (
  'name' => 'Telecommunications',
  'slug' => 'telecom',
  'type' => 'industry',
  'description' => 'Operate subscribers, billing, SIM management, packages, usage tracking, and customer support.',
  'features' => 
  array (
    0 => 'Subscribers',
    1 => 'Billing',
    2 => 'SIM Management',
    3 => 'Packages',
    4 => 'Usage Tracking',
    5 => 'Customer Support',
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
    'prefix' => '/api/v1/industries/telecom',
  ),
  'widgets' => 
  array (
    0 => 'telecom-overview',
    1 => 'telecom-reports',
    2 => 'telecom-subscribers-summary',
    3 => 'telecom-billing-summary',
    4 => 'telecom-sim-management-summary',
    5 => 'telecom-packages-summary',
    6 => 'telecom-usage-tracking-summary',
    7 => 'telecom-customer-support-summary',
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
    0 => 'telecom.view',
    1 => 'telecom.manage',
    2 => 'telecom.reports',
    3 => 'telecom.subscribers.view',
    4 => 'telecom.billing.view',
    5 => 'telecom.sim.management.view',
    6 => 'telecom.packages.view',
    7 => 'telecom.usage.tracking.view',
    8 => 'telecom.customer.support.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Subscribers',
      'module' => 'telecom-subscribers',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Billing',
      'module' => 'telecom-billing',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'SIM Management',
      'module' => 'telecom-sim-management',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Packages',
      'module' => 'telecom-packages',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Usage Tracking',
      'module' => 'telecom-usage-tracking',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Customer Support',
      'module' => 'telecom-customer-support',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Subscribers dashboard',
    1 => 'Billing dashboard',
    2 => 'SIM Management dashboard',
    3 => 'Packages dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Telecommunications Standard',
      'description' => 'Operate subscribers, billing, SIM management, packages, usage tracking, and customer support.',
      'dashboard_features' => 
      array (
        0 => 'Subscribers dashboard',
        1 => 'Billing dashboard',
        2 => 'SIM Management dashboard',
        3 => 'Packages dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Telecommunications',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Telecommunications.',
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
      'name' => 'Enterprise Telecommunications',
      'description' => 'Scale Telecommunications operations with advanced controls, reports, workflows, and templates.',
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
