<?php

return array (
  'name' => 'Insurance',
  'slug' => 'insurance',
  'type' => 'industry',
  'description' => 'Manage policies, claims, underwriting, premiums, renewals, agents, and risk assessment.',
  'features' => 
  array (
    0 => 'Policy Management',
    1 => 'Claims Management',
    2 => 'Underwriting',
    3 => 'Premium Collection',
    4 => 'Renewals',
    5 => 'Agents Management',
    6 => 'Risk Assessment',
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
    'prefix' => '/api/v1/industries/insurance',
  ),
  'widgets' => 
  array (
    0 => 'insurance-overview',
    1 => 'insurance-reports',
    2 => 'insurance-policy-management-summary',
    3 => 'insurance-claims-management-summary',
    4 => 'insurance-underwriting-summary',
    5 => 'insurance-premium-collection-summary',
    6 => 'insurance-renewals-summary',
    7 => 'insurance-agents-management-summary',
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
    0 => 'insurance.view',
    1 => 'insurance.manage',
    2 => 'insurance.reports',
    3 => 'insurance.policy.management.view',
    4 => 'insurance.claims.management.view',
    5 => 'insurance.underwriting.view',
    6 => 'insurance.premium.collection.view',
    7 => 'insurance.renewals.view',
    8 => 'insurance.agents.management.view',
    9 => 'insurance.risk.assessment.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Policy Management',
      'module' => 'insurance-policy-management',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Claims Management',
      'module' => 'insurance-claims-management',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Underwriting',
      'module' => 'insurance-underwriting',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Premium Collection',
      'module' => 'insurance-premium-collection',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Renewals',
      'module' => 'insurance-renewals',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Agents Management',
      'module' => 'insurance-agents-management',
      'icon' => 'bi-grid',
    ),
    6 => 
    array (
      'label' => 'Risk Assessment',
      'module' => 'insurance-risk-assessment',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Policy Management dashboard',
    1 => 'Claims Management dashboard',
    2 => 'Underwriting dashboard',
    3 => 'Premium Collection dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Insurance Standard',
      'description' => 'Manage policies, claims, underwriting, premiums, renewals, agents, and risk assessment.',
      'dashboard_features' => 
      array (
        0 => 'Policy Management dashboard',
        1 => 'Claims Management dashboard',
        2 => 'Underwriting dashboard',
        3 => 'Premium Collection dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Insurance',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Insurance.',
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
      'name' => 'Enterprise Insurance',
      'description' => 'Scale Insurance operations with advanced controls, reports, workflows, and templates.',
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
