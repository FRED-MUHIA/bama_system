<?php

return array (
  'name' => 'Legal',
  'slug' => 'legal',
  'type' => 'industry',
  'description' => 'Coordinate cases, clients, legal documents, court schedules, billing, contracts, matters, and compliance.',
  'features' => 
  array (
    0 => 'Case Management',
    1 => 'Clients',
    2 => 'Legal Documents',
    3 => 'Court Scheduling',
    4 => 'Legal Billing',
    5 => 'Contract Management',
    6 => 'Matter Tracking',
    7 => 'Compliance',
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
    'prefix' => '/api/v1/industries/legal',
  ),
  'widgets' => 
  array (
    0 => 'legal-overview',
    1 => 'legal-reports',
    2 => 'legal-case-management-summary',
    3 => 'legal-clients-summary',
    4 => 'legal-legal-documents-summary',
    5 => 'legal-court-scheduling-summary',
    6 => 'legal-legal-billing-summary',
    7 => 'legal-contract-management-summary',
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
    0 => 'legal.view',
    1 => 'legal.manage',
    2 => 'legal.reports',
    3 => 'legal.case.management.view',
    4 => 'legal.clients.view',
    5 => 'legal.legal.documents.view',
    6 => 'legal.court.scheduling.view',
    7 => 'legal.legal.billing.view',
    8 => 'legal.contract.management.view',
    9 => 'legal.matter.tracking.view',
    10 => 'legal.compliance.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Case Management',
      'module' => 'legal-case-management',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Clients',
      'module' => 'legal-clients',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Legal Documents',
      'module' => 'legal-legal-documents',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Court Scheduling',
      'module' => 'legal-court-scheduling',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Legal Billing',
      'module' => 'legal-legal-billing',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Contract Management',
      'module' => 'legal-contract-management',
      'icon' => 'bi-grid',
    ),
    6 => 
    array (
      'label' => 'Matter Tracking',
      'module' => 'legal-matter-tracking',
      'icon' => 'bi-grid',
    ),
    7 => 
    array (
      'label' => 'Compliance',
      'module' => 'legal-compliance',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Case Management dashboard',
    1 => 'Clients dashboard',
    2 => 'Legal Documents dashboard',
    3 => 'Court Scheduling dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Legal Standard',
      'description' => 'Coordinate cases, clients, legal documents, court schedules, billing, contracts, matters, and compliance.',
      'dashboard_features' => 
      array (
        0 => 'Case Management dashboard',
        1 => 'Clients dashboard',
        2 => 'Legal Documents dashboard',
        3 => 'Court Scheduling dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Legal',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Legal.',
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
      'name' => 'Enterprise Legal',
      'description' => 'Scale Legal operations with advanced controls, reports, workflows, and templates.',
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
