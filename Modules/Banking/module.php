<?php

return array (
  'name' => 'Banking & SACCO',
  'slug' => 'banking',
  'type' => 'industry',
  'description' => 'Operate members, savings, loans, repayments, interest, share capital, statements, and mobile banking.',
  'features' => 
  array (
    0 => 'Member Management',
    1 => 'Savings Accounts',
    2 => 'Loans',
    3 => 'Repayments',
    4 => 'Interest Calculations',
    5 => 'Share Capital',
    6 => 'Statements',
    7 => 'Mobile Banking',
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
    'prefix' => '/api/v1/industries/banking',
  ),
  'widgets' => 
  array (
    0 => 'banking-overview',
    1 => 'banking-reports',
    2 => 'banking-member-management-summary',
    3 => 'banking-savings-accounts-summary',
    4 => 'banking-loans-summary',
    5 => 'banking-repayments-summary',
    6 => 'banking-interest-calculations-summary',
    7 => 'banking-share-capital-summary',
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
    0 => 'banking.view',
    1 => 'banking.manage',
    2 => 'banking.reports',
    3 => 'banking.member.management.view',
    4 => 'banking.savings.accounts.view',
    5 => 'banking.loans.view',
    6 => 'banking.repayments.view',
    7 => 'banking.interest.calculations.view',
    8 => 'banking.share.capital.view',
    9 => 'banking.statements.view',
    10 => 'banking.mobile.banking.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Member Management',
      'module' => 'banking-member-management',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Savings Accounts',
      'module' => 'banking-savings-accounts',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Loans',
      'module' => 'banking-loans',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Repayments',
      'module' => 'banking-repayments',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Interest Calculations',
      'module' => 'banking-interest-calculations',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Share Capital',
      'module' => 'banking-share-capital',
      'icon' => 'bi-grid',
    ),
    6 => 
    array (
      'label' => 'Statements',
      'module' => 'banking-statements',
      'icon' => 'bi-grid',
    ),
    7 => 
    array (
      'label' => 'Mobile Banking',
      'module' => 'banking-mobile-banking',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Member Management dashboard',
    1 => 'Savings Accounts dashboard',
    2 => 'Loans dashboard',
    3 => 'Repayments dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Banking & SACCO Standard',
      'description' => 'Operate members, savings, loans, repayments, interest, share capital, statements, and mobile banking.',
      'dashboard_features' => 
      array (
        0 => 'Member Management dashboard',
        1 => 'Savings Accounts dashboard',
        2 => 'Loans dashboard',
        3 => 'Repayments dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Banking & SACCO',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Banking & SACCO.',
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
      'name' => 'Enterprise Banking & SACCO',
      'description' => 'Scale Banking & SACCO operations with advanced controls, reports, workflows, and templates.',
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
