<?php

return array (
  'name' => 'Pharmacy',
  'slug' => 'pharmacy',
  'type' => 'industry',
  'description' => 'Run drug inventory, prescriptions, suppliers, expiry tracking, pharmacy POS, and insurance claims.',
  'features' => 
  array (
    0 => 'Drug Inventory',
    1 => 'Prescriptions',
    2 => 'Suppliers',
    3 => 'Expiry Tracking',
    4 => 'Pharmacy POS',
    5 => 'Insurance Claims',
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
    'prefix' => '/api/v1/industries/pharmacy',
  ),
  'widgets' => 
  array (
    0 => 'pharmacy-overview',
    1 => 'pharmacy-reports',
    2 => 'pharmacy-drug-inventory-summary',
    3 => 'pharmacy-prescriptions-summary',
    4 => 'pharmacy-suppliers-summary',
    5 => 'pharmacy-expiry-tracking-summary',
    6 => 'pharmacy-pharmacy-pos-summary',
    7 => 'pharmacy-insurance-claims-summary',
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
    0 => 'pharmacy.view',
    1 => 'pharmacy.manage',
    2 => 'pharmacy.reports',
    3 => 'pharmacy.drug.inventory.view',
    4 => 'pharmacy.prescriptions.view',
    5 => 'pharmacy.suppliers.view',
    6 => 'pharmacy.expiry.tracking.view',
    7 => 'pharmacy.pharmacy.pos.view',
    8 => 'pharmacy.insurance.claims.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Drug Inventory',
      'module' => 'pharmacy-drug-inventory',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Prescriptions',
      'module' => 'pharmacy-prescriptions',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Suppliers',
      'module' => 'pharmacy-suppliers',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Expiry Tracking',
      'module' => 'pharmacy-expiry-tracking',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Pharmacy POS',
      'module' => 'pharmacy-pharmacy-pos',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Insurance Claims',
      'module' => 'pharmacy-insurance-claims',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Drug Inventory dashboard',
    1 => 'Prescriptions dashboard',
    2 => 'Suppliers dashboard',
    3 => 'Expiry Tracking dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Pharmacy Standard',
      'description' => 'Run drug inventory, prescriptions, suppliers, expiry tracking, pharmacy POS, and insurance claims.',
      'dashboard_features' => 
      array (
        0 => 'Drug Inventory dashboard',
        1 => 'Prescriptions dashboard',
        2 => 'Suppliers dashboard',
        3 => 'Expiry Tracking dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Pharmacy',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Pharmacy.',
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
      'name' => 'Enterprise Pharmacy',
      'description' => 'Scale Pharmacy operations with advanced controls, reports, workflows, and templates.',
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
