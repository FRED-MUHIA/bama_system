<?php

return array (
  'name' => 'Fitness & Gym',
  'slug' => 'fitness',
  'type' => 'industry',
  'description' => 'Run memberships, trainers, attendance, class schedules, payments, and fitness programs.',
  'features' => 
  array (
    0 => 'Memberships',
    1 => 'Trainers',
    2 => 'Attendance',
    3 => 'Class Scheduling',
    4 => 'Payments',
    5 => 'Fitness Programs',
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
    'web' => 
    array (
      'dashboard' => 'fitness.dashboard',
      'memberships' => 'fitness.memberships.index',
      'members' => 'fitness.members.index',
      'trainers' => 'fitness.trainers.index',
      'attendance' => 'fitness.attendance.index',
      'check_in' => 'fitness.check-in.index',
      'class_scheduling' => 'fitness.classes.index',
      'fitness_programs' => 'fitness.programs.index',
      'exercise_library' => 'fitness.exercises.index',
      'health_profiles' => 'fitness.health-profiles.index',
      'assessments' => 'fitness.assessments.index',
      'personal_training' => 'fitness.personal-training.index',
      'nutrition' => 'fitness.nutrition.index',
      'challenges' => 'fitness.challenges.index',
      'equipment' => 'fitness.equipment.index',
      'inventory' => 'products.index',
      'payments' => 'finance.index',
      'reports' => 'fitness.reports.index',
    ),
  ),
  'api' => 
  array (
    'prefix' => '/api/v1/industries/fitness',
  ),
  'widgets' => 
  array (
    0 => 'fitness-overview',
    1 => 'fitness-reports',
    2 => 'fitness-memberships-summary',
    3 => 'fitness-trainers-summary',
    4 => 'fitness-attendance-summary',
    5 => 'fitness-class-scheduling-summary',
    6 => 'fitness-payments-summary',
    7 => 'fitness-fitness-programs-summary',
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
    0 => 'fitness.view',
    1 => 'fitness.manage',
    2 => 'fitness.reports',
    3 => 'fitness.memberships.view',
    4 => 'fitness.trainers.view',
    5 => 'fitness.attendance.view',
    6 => 'fitness.classes.view',
    7 => 'fitness.payments.view',
    8 => 'fitness.programs.view',
    9 => 'fitness.exercises.view',
    10 => 'fitness.health.view',
    11 => 'fitness.assessments.view',
    12 => 'fitness.personal-training.view',
    13 => 'fitness.nutrition.view',
    14 => 'fitness.challenges.view',
    15 => 'fitness.equipment.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Dashboard',
      'route' => 'fitness.dashboard',
      'icon' => 'bi-speedometer2',
      'permission' => 'fitness.view',
      'tables' => 
      array (
        0 => 'fitness_members',
      ),
    ),
    1 => 
    array (
      'label' => 'Memberships',
      'route' => 'fitness.memberships.index',
      'icon' => 'bi-card-checklist',
      'permission' => 'fitness.memberships.view',
      'tables' => 
      array (
        0 => 'fitness_membership_plans',
        1 => 'fitness_member_memberships',
      ),
    ),
    2 => 
    array (
      'label' => 'Members',
      'route' => 'fitness.members.index',
      'icon' => 'bi-people',
      'permission' => 'fitness.members.view',
      'tables' => 
      array (
        0 => 'fitness_members',
      ),
    ),
    3 => 
    array (
      'label' => 'Trainers',
      'route' => 'fitness.trainers.index',
      'icon' => 'bi-person-workspace',
      'permission' => 'fitness.trainers.view',
    ),
    4 => 
    array (
      'label' => 'Attendance',
      'route' => 'fitness.attendance.index',
      'icon' => 'bi-qr-code-scan',
      'permission' => 'fitness.attendance.view',
    ),
    5 => 
    array (
      'label' => 'Check-In',
      'route' => 'fitness.check-in.index',
      'icon' => 'bi-box-arrow-in-right',
      'permission' => 'fitness.attendance.view',
    ),
    6 => 
    array (
      'label' => 'Class Scheduling',
      'route' => 'fitness.classes.index',
      'icon' => 'bi-calendar-week',
      'permission' => 'fitness.classes.view',
    ),
    7 => 
    array (
      'label' => 'Fitness Programs',
      'route' => 'fitness.programs.index',
      'icon' => 'bi-clipboard2-pulse',
      'permission' => 'fitness.programs.view',
    ),
    8 => 
    array (
      'label' => 'Exercise Library',
      'route' => 'fitness.exercises.index',
      'icon' => 'bi-list-check',
      'permission' => 'fitness.exercises.view',
    ),
    9 => 
    array (
      'label' => 'Health Profiles',
      'route' => 'fitness.health-profiles.index',
      'icon' => 'bi-heart-pulse',
      'permission' => 'fitness.health.view',
    ),
    10 => 
    array (
      'label' => 'Assessments',
      'route' => 'fitness.assessments.index',
      'icon' => 'bi-graph-up-arrow',
      'permission' => 'fitness.assessments.view',
    ),
    11 => 
    array (
      'label' => 'Personal Training',
      'route' => 'fitness.personal-training.index',
      'icon' => 'bi-person-arms-up',
      'permission' => 'fitness.personal-training.view',
    ),
    12 => 
    array (
      'label' => 'Nutrition',
      'route' => 'fitness.nutrition.index',
      'icon' => 'bi-egg-fried',
      'permission' => 'fitness.nutrition.view',
    ),
    13 => 
    array (
      'label' => 'Challenges',
      'route' => 'fitness.challenges.index',
      'icon' => 'bi-trophy',
      'permission' => 'fitness.challenges.view',
    ),
    14 => 
    array (
      'label' => 'Equipment',
      'route' => 'fitness.equipment.index',
      'icon' => 'bi-tools',
      'permission' => 'fitness.equipment.view',
    ),
    15 => 
    array (
      'label' => 'Inventory',
      'route' => 'products.index',
      'icon' => 'bi-box-seam',
      'permission' => 'inventory.view',
    ),
    16 => 
    array (
      'label' => 'Payments',
      'route' => 'finance.index',
      'icon' => 'bi-cash-coin',
      'permission' => 'fitness.payments.view',
    ),
    17 => 
    array (
      'label' => 'Reports',
      'route' => 'fitness.reports.index',
      'icon' => 'bi-bar-chart',
      'permission' => 'fitness.reports',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Memberships dashboard',
    1 => 'Trainers dashboard',
    2 => 'Attendance dashboard',
    3 => 'Class Scheduling dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Fitness & Gym Standard',
      'description' => 'Run memberships, trainers, attendance, class schedules, payments, and fitness programs.',
      'dashboard_features' => 
      array (
        0 => 'Memberships dashboard',
        1 => 'Trainers dashboard',
        2 => 'Attendance dashboard',
        3 => 'Class Scheduling dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Fitness & Gym',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Fitness & Gym.',
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
      'name' => 'Enterprise Fitness & Gym',
      'description' => 'Scale Fitness & Gym operations with advanced controls, reports, workflows, and templates.',
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
