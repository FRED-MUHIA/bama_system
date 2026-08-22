<?php

return array (
  'name' => 'Core Platform',
  'slug' => 'core',
  'type' => 'core',
  'features' => 
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
    'prefix' => '/api/v1',
  ),
  'widgets' => 
  array (
    0 => 'crm-summary',
    1 => 'projects-summary',
    2 => 'finance-summary',
    3 => 'accounting-summary',
    4 => 'documents-summary',
    5 => 'reporting-summary',
    6 => 'hr-summary',
    7 => 'administration-summary',
    8 => 'portal-summary',
    9 => 'notifications-summary',
  ),
  'reports' => 
  array (
    0 => 'Operational reports',
    1 => 'Financial reports',
    2 => 'Activity reports',
  ),
  'workflows' => 
  array (
    0 => 'Tenant onboarding',
    1 => 'User provisioning',
    2 => 'Role assignment',
    3 => 'Notification delivery',
  ),
  'templates' => 
  array (
    0 => 'Default dashboard',
    1 => 'Default permissions',
    2 => 'Default menus',
  ),
  'permissions' => 
  array (
    0 => 'crm.view',
    1 => 'crm.manage',
    2 => 'projects.view',
    3 => 'projects.manage',
    4 => 'finance.view',
    5 => 'finance.manage',
    6 => 'accounting.view',
    7 => 'accounting.manage',
    8 => 'documents.view',
    9 => 'documents.manage',
    10 => 'reporting.view',
    11 => 'reporting.manage',
    12 => 'hr.view',
    13 => 'hr.manage',
    14 => 'administration.view',
    15 => 'administration.manage',
    16 => 'portal.view',
    17 => 'portal.manage',
    18 => 'notifications.view',
    19 => 'notifications.manage',
  ),
  'tenant_isolated' => true,
  'subscription_activated' => true,
  'dynamic_menus' => true,
);
