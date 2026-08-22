<?php

return array (
  'name' => 'Professional Services',
  'slug' => 'professional-services',
  'type' => 'industry',
  'description' => 'Manage clients, projects, timesheets, resources, retainers, billing, contracts, service requests, and utilization reports.',
  'features' => 
  array (
    0 => 'Clients',
    1 => 'Projects',
    2 => 'Timesheets',
    3 => 'Resource Planning',
    4 => 'Retainers',
    5 => 'Billing',
    6 => 'Contracts',
    7 => 'Service Requests',
    8 => 'Utilization Reports',
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
    'prefix' => '/api/v1/industries/professional-services',
  ),
  'widgets' => 
  array (
    0 => 'professional-services-overview',
    1 => 'professional-services-reports',
    2 => 'professional-services-clients-summary',
    3 => 'professional-services-projects-summary',
    4 => 'professional-services-timesheets-summary',
    5 => 'professional-services-resource-planning-summary',
    6 => 'professional-services-retainers-summary',
    7 => 'professional-services-billing-summary',
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
    0 => 'professional-services.view',
    1 => 'professional-services.manage',
    2 => 'professional-services.reports',
    3 => 'professional.services.clients.view',
    4 => 'professional.services.projects.view',
    5 => 'professional.services.timesheets.view',
    6 => 'professional.services.resource.planning.view',
    7 => 'professional.services.retainers.view',
    8 => 'professional.services.billing.view',
    9 => 'professional.services.contracts.view',
    10 => 'professional.services.service.requests.view',
    11 => 'professional.services.utilization.reports.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Clients',
      'module' => 'professional-services-clients',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Projects',
      'module' => 'professional-services-projects',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Timesheets',
      'module' => 'professional-services-timesheets',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Resource Planning',
      'module' => 'professional-services-resource-planning',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Retainers',
      'module' => 'professional-services-retainers',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Billing',
      'module' => 'professional-services-billing',
      'icon' => 'bi-grid',
    ),
    6 => 
    array (
      'label' => 'Contracts',
      'module' => 'professional-services-contracts',
      'icon' => 'bi-grid',
    ),
    7 => 
    array (
      'label' => 'Service Requests',
      'module' => 'professional-services-service-requests',
      'icon' => 'bi-grid',
    ),
    8 => 
    array (
      'label' => 'Utilization Reports',
      'module' => 'professional-services-utilization-reports',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Clients dashboard',
    1 => 'Projects dashboard',
    2 => 'Timesheets dashboard',
    3 => 'Resource Planning dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Professional Services Standard',
      'description' => 'Manage clients, projects, timesheets, resources, retainers, billing, contracts, service requests, and utilization reports.',
      'dashboard_features' => 
      array (
        0 => 'Clients dashboard',
        1 => 'Projects dashboard',
        2 => 'Timesheets dashboard',
        3 => 'Resource Planning dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Professional Services',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Professional Services.',
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
      'name' => 'Enterprise Professional Services',
      'description' => 'Scale Professional Services operations with advanced controls, reports, workflows, and templates.',
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
