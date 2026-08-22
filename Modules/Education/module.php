<?php

return array (
  'name' => 'Education',
  'slug' => 'education',
  'type' => 'industry',
  'description' => 'Manage students, admissions, classes, timetables, exams, attendance, fees, parents, teachers, resources, transport, and library workflows.',
  'features' => 
  array (
    0 => 'Student Management',
    1 => 'Admissions',
    2 => 'Classes',
    3 => 'Timetables',
    4 => 'Exams',
    5 => 'Grading',
    6 => 'Attendance',
    7 => 'Fee Management',
    8 => 'Parent Portal',
    9 => 'Teacher Management',
    10 => 'Learning Resources',
    11 => 'Academic Reports',
    12 => 'School Transport',
    13 => 'Library',
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
    'prefix' => '/api/v1/industries/education',
  ),
  'widgets' => 
  array (
    0 => 'education-overview',
    1 => 'education-reports',
    2 => 'education-student-management-summary',
    3 => 'education-admissions-summary',
    4 => 'education-classes-summary',
    5 => 'education-timetables-summary',
    6 => 'education-exams-summary',
    7 => 'education-grading-summary',
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
    0 => 'education.view',
    1 => 'education.manage',
    2 => 'education.reports',
    3 => 'education.student.management.view',
    4 => 'education.admissions.view',
    5 => 'education.classes.view',
    6 => 'education.timetables.view',
    7 => 'education.exams.view',
    8 => 'education.grading.view',
    9 => 'education.attendance.view',
    10 => 'education.fee.management.view',
    11 => 'education.parent.portal.view',
    12 => 'education.teacher.management.view',
    13 => 'education.learning.resources.view',
    14 => 'education.academic.reports.view',
    15 => 'education.school.transport.view',
    16 => 'education.library.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Student Management',
      'module' => 'education-student-management',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Admissions',
      'module' => 'education-admissions',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Classes',
      'module' => 'education-classes',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Timetables',
      'module' => 'education-timetables',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Exams',
      'module' => 'education-exams',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Grading',
      'module' => 'education-grading',
      'icon' => 'bi-grid',
    ),
    6 => 
    array (
      'label' => 'Attendance',
      'module' => 'education-attendance',
      'icon' => 'bi-grid',
    ),
    7 => 
    array (
      'label' => 'Fee Management',
      'module' => 'education-fee-management',
      'icon' => 'bi-grid',
    ),
    8 => 
    array (
      'label' => 'Parent Portal',
      'module' => 'education-parent-portal',
      'icon' => 'bi-grid',
    ),
    9 => 
    array (
      'label' => 'Teacher Management',
      'module' => 'education-teacher-management',
      'icon' => 'bi-grid',
    ),
    10 => 
    array (
      'label' => 'Learning Resources',
      'module' => 'education-learning-resources',
      'icon' => 'bi-grid',
    ),
    11 => 
    array (
      'label' => 'Academic Reports',
      'module' => 'education-academic-reports',
      'icon' => 'bi-grid',
    ),
    12 => 
    array (
      'label' => 'School Transport',
      'module' => 'education-school-transport',
      'icon' => 'bi-grid',
    ),
    13 => 
    array (
      'label' => 'Library',
      'module' => 'education-library',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Student Management dashboard',
    1 => 'Admissions dashboard',
    2 => 'Classes dashboard',
    3 => 'Timetables dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Education Standard',
      'description' => 'Manage students, admissions, classes, timetables, exams, attendance, fees, parents, teachers, resources, transport, and library workflows.',
      'dashboard_features' => 
      array (
        0 => 'Student Management dashboard',
        1 => 'Admissions dashboard',
        2 => 'Classes dashboard',
        3 => 'Timetables dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Education',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Education.',
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
      'name' => 'Enterprise Education',
      'description' => 'Scale Education operations with advanced controls, reports, workflows, and templates.',
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
