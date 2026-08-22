<?php

return array (
  'name' => 'NGO & Non-Profit',
  'slug' => 'ngo',
  'type' => 'industry',
  'description' => 'Track donors, grants, programs, beneficiaries, fundraising, monitoring and evaluation, and impact reporting.',
  'features' => 
  array (
    0 => 'Donor Management',
    1 => 'Grants Management',
    2 => 'Programs',
    3 => 'Beneficiaries',
    4 => 'Fundraising',
    5 => 'Monitoring & Evaluation',
    6 => 'Impact Reporting',
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
    'prefix' => '/api/v1/industries/ngo',
  ),
  'widgets' => 
  array (
    0 => 'ngo-overview',
    1 => 'ngo-reports',
    2 => 'ngo-donor-management-summary',
    3 => 'ngo-grants-management-summary',
    4 => 'ngo-programs-summary',
    5 => 'ngo-beneficiaries-summary',
    6 => 'ngo-fundraising-summary',
    7 => 'ngo-monitoring-evaluation-summary',
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
    0 => 'ngo.view',
    1 => 'ngo.manage',
    2 => 'ngo.reports',
    3 => 'ngo.donor.management.view',
    4 => 'ngo.grants.management.view',
    5 => 'ngo.programs.view',
    6 => 'ngo.beneficiaries.view',
    7 => 'ngo.fundraising.view',
    8 => 'ngo.monitoring.evaluation.view',
    9 => 'ngo.impact.reporting.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Donor Management',
      'module' => 'ngo-donor-management',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Grants Management',
      'module' => 'ngo-grants-management',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Programs',
      'module' => 'ngo-programs',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Beneficiaries',
      'module' => 'ngo-beneficiaries',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Fundraising',
      'module' => 'ngo-fundraising',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Monitoring & Evaluation',
      'module' => 'ngo-monitoring-evaluation',
      'icon' => 'bi-grid',
    ),
    6 => 
    array (
      'label' => 'Impact Reporting',
      'module' => 'ngo-impact-reporting',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Donor Management dashboard',
    1 => 'Grants Management dashboard',
    2 => 'Programs dashboard',
    3 => 'Beneficiaries dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'NGO & Non-Profit Standard',
      'description' => 'Track donors, grants, programs, beneficiaries, fundraising, monitoring and evaluation, and impact reporting.',
      'dashboard_features' => 
      array (
        0 => 'Donor Management dashboard',
        1 => 'Grants Management dashboard',
        2 => 'Programs dashboard',
        3 => 'Beneficiaries dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch NGO & Non-Profit',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for NGO & Non-Profit.',
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
      'name' => 'Enterprise NGO & Non-Profit',
      'description' => 'Scale NGO & Non-Profit operations with advanced controls, reports, workflows, and templates.',
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
