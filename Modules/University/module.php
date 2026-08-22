<?php

return array (
  'name' => 'University',
  'slug' => 'university',
  'type' => 'industry',
  'description' => 'Coordinate faculties, departments, courses, semesters, registration, exams, graduation, research, hostels, student finance, and alumni.',
  'features' => 
  array (
    0 => 'Student Information System',
    1 => 'Faculties',
    2 => 'Departments',
    3 => 'Courses',
    4 => 'Semesters',
    5 => 'Registration',
    6 => 'Exams',
    7 => 'Graduation Tracking',
    8 => 'Research Management',
    9 => 'Hostel Management',
    10 => 'Student Finance',
    11 => 'Alumni Management',
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
    'prefix' => '/api/v1/industries/university',
  ),
  'widgets' => 
  array (
    0 => 'university-overview',
    1 => 'university-reports',
    2 => 'university-student-information-system-summary',
    3 => 'university-faculties-summary',
    4 => 'university-departments-summary',
    5 => 'university-courses-summary',
    6 => 'university-semesters-summary',
    7 => 'university-registration-summary',
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
    0 => 'university.view',
    1 => 'university.manage',
    2 => 'university.reports',
    3 => 'university.student.information.system.view',
    4 => 'university.faculties.view',
    5 => 'university.departments.view',
    6 => 'university.courses.view',
    7 => 'university.semesters.view',
    8 => 'university.registration.view',
    9 => 'university.exams.view',
    10 => 'university.graduation.tracking.view',
    11 => 'university.research.management.view',
    12 => 'university.hostel.management.view',
    13 => 'university.student.finance.view',
    14 => 'university.alumni.management.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Student Information System',
      'module' => 'university-student-information-system',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Faculties',
      'module' => 'university-faculties',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Departments',
      'module' => 'university-departments',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Courses',
      'module' => 'university-courses',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Semesters',
      'module' => 'university-semesters',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Registration',
      'module' => 'university-registration',
      'icon' => 'bi-grid',
    ),
    6 => 
    array (
      'label' => 'Exams',
      'module' => 'university-exams',
      'icon' => 'bi-grid',
    ),
    7 => 
    array (
      'label' => 'Graduation Tracking',
      'module' => 'university-graduation-tracking',
      'icon' => 'bi-grid',
    ),
    8 => 
    array (
      'label' => 'Research Management',
      'module' => 'university-research-management',
      'icon' => 'bi-grid',
    ),
    9 => 
    array (
      'label' => 'Hostel Management',
      'module' => 'university-hostel-management',
      'icon' => 'bi-grid',
    ),
    10 => 
    array (
      'label' => 'Student Finance',
      'module' => 'university-student-finance',
      'icon' => 'bi-grid',
    ),
    11 => 
    array (
      'label' => 'Alumni Management',
      'module' => 'university-alumni-management',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Student Information System dashboard',
    1 => 'Faculties dashboard',
    2 => 'Departments dashboard',
    3 => 'Courses dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'University Standard',
      'description' => 'Coordinate faculties, departments, courses, semesters, registration, exams, graduation, research, hostels, student finance, and alumni.',
      'dashboard_features' => 
      array (
        0 => 'Student Information System dashboard',
        1 => 'Faculties dashboard',
        2 => 'Departments dashboard',
        3 => 'Courses dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch University',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for University.',
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
      'name' => 'Enterprise University',
      'description' => 'Scale University operations with advanced controls, reports, workflows, and templates.',
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
