<?php

return array (
  'name' => 'Microfinance',
  'slug' => 'microfinance',
  'type' => 'industry',
  'description' => 'Manage borrowers, loans, collections, group lending, savings, guarantors, and credit scoring.',
  'features' => 
  array (
    0 => 'Borrowers',
    1 => 'Loans',
    2 => 'Collections',
    3 => 'Group Lending',
    4 => 'Savings',
    5 => 'Guarantors',
    6 => 'Credit Scoring',
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
    'prefix' => '/api/v1/industries/microfinance',
  ),
  'widgets' => 
  array (
    0 => 'microfinance-overview',
    1 => 'microfinance-reports',
    2 => 'microfinance-borrowers-summary',
    3 => 'microfinance-loans-summary',
    4 => 'microfinance-collections-summary',
    5 => 'microfinance-group-lending-summary',
    6 => 'microfinance-savings-summary',
    7 => 'microfinance-guarantors-summary',
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
    0 => 'microfinance.view',
    1 => 'microfinance.manage',
    2 => 'microfinance.reports',
    3 => 'microfinance.borrowers.view',
    4 => 'microfinance.loans.view',
    5 => 'microfinance.collections.view',
    6 => 'microfinance.group.lending.view',
    7 => 'microfinance.savings.view',
    8 => 'microfinance.guarantors.view',
    9 => 'microfinance.credit.scoring.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Borrowers',
      'module' => 'microfinance-borrowers',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Loans',
      'module' => 'microfinance-loans',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Collections',
      'module' => 'microfinance-collections',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Group Lending',
      'module' => 'microfinance-group-lending',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Savings',
      'module' => 'microfinance-savings',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Guarantors',
      'module' => 'microfinance-guarantors',
      'icon' => 'bi-grid',
    ),
    6 => 
    array (
      'label' => 'Credit Scoring',
      'module' => 'microfinance-credit-scoring',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Borrowers dashboard',
    1 => 'Loans dashboard',
    2 => 'Collections dashboard',
    3 => 'Group Lending dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Microfinance Standard',
      'description' => 'Manage borrowers, loans, collections, group lending, savings, guarantors, and credit scoring.',
      'dashboard_features' => 
      array (
        0 => 'Borrowers dashboard',
        1 => 'Loans dashboard',
        2 => 'Collections dashboard',
        3 => 'Group Lending dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Microfinance',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Microfinance.',
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
      'name' => 'Enterprise Microfinance',
      'description' => 'Scale Microfinance operations with advanced controls, reports, workflows, and templates.',
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
