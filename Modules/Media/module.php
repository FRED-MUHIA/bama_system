<?php

return array (
  'name' => 'Media & Marketing',
  'slug' => 'media',
  'type' => 'industry',
  'description' => 'Manage campaigns, clients, content calendars, social media, leads, creative projects, and analytics.',
  'features' => 
  array (
    0 => 'Campaign Management',
    1 => 'Clients',
    2 => 'Content Calendar',
    3 => 'Social Media',
    4 => 'Leads',
    5 => 'Creative Projects',
    6 => 'Analytics',
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
    'prefix' => '/api/v1/industries/media',
  ),
  'widgets' => 
  array (
    0 => 'media-overview',
    1 => 'media-reports',
    2 => 'media-campaign-management-summary',
    3 => 'media-clients-summary',
    4 => 'media-content-calendar-summary',
    5 => 'media-social-media-summary',
    6 => 'media-leads-summary',
    7 => 'media-creative-projects-summary',
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
    0 => 'media.view',
    1 => 'media.manage',
    2 => 'media.reports',
    3 => 'media.campaign.management.view',
    4 => 'media.clients.view',
    5 => 'media.content.calendar.view',
    6 => 'media.social.media.view',
    7 => 'media.leads.view',
    8 => 'media.creative.projects.view',
    9 => 'media.analytics.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Campaign Management',
      'module' => 'media-campaign-management',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Clients',
      'module' => 'media-clients',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Content Calendar',
      'module' => 'media-content-calendar',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Social Media',
      'module' => 'media-social-media',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Leads',
      'module' => 'media-leads',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Creative Projects',
      'module' => 'media-creative-projects',
      'icon' => 'bi-grid',
    ),
    6 => 
    array (
      'label' => 'Analytics',
      'module' => 'media-analytics',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Campaign Management dashboard',
    1 => 'Clients dashboard',
    2 => 'Content Calendar dashboard',
    3 => 'Social Media dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Media & Marketing Standard',
      'description' => 'Manage campaigns, clients, content calendars, social media, leads, creative projects, and analytics.',
      'dashboard_features' => 
      array (
        0 => 'Campaign Management dashboard',
        1 => 'Clients dashboard',
        2 => 'Content Calendar dashboard',
        3 => 'Social Media dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Media & Marketing',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Media & Marketing.',
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
      'name' => 'Enterprise Media & Marketing',
      'description' => 'Scale Media & Marketing operations with advanced controls, reports, workflows, and templates.',
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
