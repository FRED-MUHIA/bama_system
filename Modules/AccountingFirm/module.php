<?php

return array (
  'name' => 'Accounting Firm',
  'slug' => 'accounting-firm',
  'type' => 'industry',
  'description' => 'Run client accounting, tax, audit, payroll, financial statements, and compliance tracking.',
  'features' => 
  array (
    0 => 'Client Accounting',
    1 => 'Tax Management',
    2 => 'Audit Management',
    3 => 'Payroll Processing',
    4 => 'Financial Statements',
    5 => 'Compliance Tracking',
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
    'prefix' => '/api/v1/industries/accounting-firm',
  ),
  'widgets' => 
  array (
    0 => 'accounting-firm-overview',
    1 => 'accounting-firm-reports',
    2 => 'accounting-firm-client-accounting-summary',
    3 => 'accounting-firm-tax-management-summary',
    4 => 'accounting-firm-audit-management-summary',
    5 => 'accounting-firm-payroll-processing-summary',
    6 => 'accounting-firm-financial-statements-summary',
    7 => 'accounting-firm-compliance-tracking-summary',
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
    0 => 'accounting-firm.view',
    1 => 'accounting-firm.manage',
    2 => 'accounting-firm.reports',
    3 => 'accounting.firm.client.accounting.view',
    4 => 'accounting.firm.tax.management.view',
    5 => 'accounting.firm.audit.management.view',
    6 => 'accounting.firm.payroll.processing.view',
    7 => 'accounting.firm.financial.statements.view',
    8 => 'accounting.firm.compliance.tracking.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Client Accounting',
      'module' => 'accounting-firm-client-accounting',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Tax Management',
      'module' => 'accounting-firm-tax-management',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Audit Management',
      'module' => 'accounting-firm-audit-management',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Payroll Processing',
      'module' => 'accounting-firm-payroll-processing',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Financial Statements',
      'module' => 'accounting-firm-financial-statements',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Compliance Tracking',
      'module' => 'accounting-firm-compliance-tracking',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Client Accounting dashboard',
    1 => 'Tax Management dashboard',
    2 => 'Audit Management dashboard',
    3 => 'Payroll Processing dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Accounting Firm Standard',
      'description' => 'Run client accounting, tax, audit, payroll, financial statements, and compliance tracking.',
      'dashboard_features' => 
      array (
        0 => 'Client Accounting dashboard',
        1 => 'Tax Management dashboard',
        2 => 'Audit Management dashboard',
        3 => 'Payroll Processing dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Accounting Firm',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Accounting Firm.',
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
      'name' => 'Enterprise Accounting Firm',
      'description' => 'Scale Accounting Firm operations with advanced controls, reports, workflows, and templates.',
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
