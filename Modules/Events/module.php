<?php

return array (
  'name' => 'Event Management',
  'slug' => 'events',
  'type' => 'industry',
  'description' => 'Coordinate events, venues, ticketing, sponsors, vendors, registrations, and event finance.',
  'features' => 
  array (
    0 => 'Events',
    1 => 'Venues',
    2 => 'Ticketing',
    3 => 'Sponsors',
    4 => 'Vendors',
    5 => 'Registrations',
    6 => 'Event Finance',
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
    'prefix' => '/api/v1/industries/events',
  ),
  'widgets' => 
  array (
    0 => 'events-overview',
    1 => 'events-reports',
    2 => 'events-events-summary',
    3 => 'events-venues-summary',
    4 => 'events-ticketing-summary',
    5 => 'events-sponsors-summary',
    6 => 'events-vendors-summary',
    7 => 'events-registrations-summary',
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
    0 => 'events.view',
    1 => 'events.manage',
    2 => 'events.reports',
    3 => 'events.events.view',
    4 => 'events.venues.view',
    5 => 'events.ticketing.view',
    6 => 'events.sponsors.view',
    7 => 'events.vendors.view',
    8 => 'events.registrations.view',
    9 => 'events.event.finance.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Events',
      'module' => 'events-events',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Venues',
      'module' => 'events-venues',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Ticketing',
      'module' => 'events-ticketing',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Sponsors',
      'module' => 'events-sponsors',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Vendors',
      'module' => 'events-vendors',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Registrations',
      'module' => 'events-registrations',
      'icon' => 'bi-grid',
    ),
    6 => 
    array (
      'label' => 'Event Finance',
      'module' => 'events-event-finance',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Events dashboard',
    1 => 'Venues dashboard',
    2 => 'Ticketing dashboard',
    3 => 'Sponsors dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Event Management Standard',
      'description' => 'Coordinate events, venues, ticketing, sponsors, vendors, registrations, and event finance.',
      'dashboard_features' => 
      array (
        0 => 'Events dashboard',
        1 => 'Venues dashboard',
        2 => 'Ticketing dashboard',
        3 => 'Sponsors dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Event Management',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Event Management.',
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
      'name' => 'Enterprise Event Management',
      'description' => 'Scale Event Management operations with advanced controls, reports, workflows, and templates.',
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
