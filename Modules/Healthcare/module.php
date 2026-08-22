<?php

return array (
  'name' => 'Healthcare',
  'slug' => 'healthcare',
  'type' => 'industry',
  'description' => 'Run patient operations, doctors, appointments, clinical records, pharmacy, laboratory, billing, insurance, wards, and reports.',
  'features' => 
  array (
    0 => 'Patient Registration',
    1 => 'Patient Records',
    2 => 'Doctors Management',
    3 => 'Appointments',
    4 => 'Consultation Notes',
    5 => 'Prescriptions',
    6 => 'Pharmacy',
    7 => 'Laboratory',
    8 => 'Radiology',
    9 => 'Medical Billing',
    10 => 'Insurance Claims',
    11 => 'Inpatient Management',
    12 => 'Ward Management',
    13 => 'Emergency Services',
    14 => 'Medical Reports',
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
    'prefix' => '/api/v1/industries/healthcare',
  ),
  'widgets' => 
  array (
    0 => 'healthcare-overview',
    1 => 'healthcare-reports',
    2 => 'healthcare-patient-registration-summary',
    3 => 'healthcare-patient-records-summary',
    4 => 'healthcare-doctors-management-summary',
    5 => 'healthcare-appointments-summary',
    6 => 'healthcare-consultation-notes-summary',
    7 => 'healthcare-prescriptions-summary',
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
    0 => 'healthcare.view',
    1 => 'healthcare.manage',
    2 => 'healthcare.reports',
    3 => 'healthcare.patient.registration.view',
    4 => 'healthcare.patient.records.view',
    5 => 'healthcare.doctors.management.view',
    6 => 'healthcare.appointments.view',
    7 => 'healthcare.consultation.notes.view',
    8 => 'healthcare.prescriptions.view',
    9 => 'healthcare.pharmacy.view',
    10 => 'healthcare.laboratory.view',
    11 => 'healthcare.radiology.view',
    12 => 'healthcare.medical.billing.view',
    13 => 'healthcare.insurance.claims.view',
    14 => 'healthcare.inpatient.management.view',
    15 => 'healthcare.ward.management.view',
    16 => 'healthcare.emergency.services.view',
    17 => 'healthcare.medical.reports.view',
  ),
  'menus' => 
  array (
    0 => 
    array (
      'label' => 'Patient Registration',
      'module' => 'healthcare-patient-registration',
      'icon' => 'bi-grid',
    ),
    1 => 
    array (
      'label' => 'Patient Records',
      'module' => 'healthcare-patient-records',
      'icon' => 'bi-grid',
    ),
    2 => 
    array (
      'label' => 'Doctors Management',
      'module' => 'healthcare-doctors-management',
      'icon' => 'bi-grid',
    ),
    3 => 
    array (
      'label' => 'Appointments',
      'module' => 'healthcare-appointments',
      'icon' => 'bi-grid',
    ),
    4 => 
    array (
      'label' => 'Consultation Notes',
      'module' => 'healthcare-consultation-notes',
      'icon' => 'bi-grid',
    ),
    5 => 
    array (
      'label' => 'Prescriptions',
      'module' => 'healthcare-prescriptions',
      'icon' => 'bi-grid',
    ),
    6 => 
    array (
      'label' => 'Pharmacy',
      'module' => 'healthcare-pharmacy',
      'icon' => 'bi-grid',
    ),
    7 => 
    array (
      'label' => 'Laboratory',
      'module' => 'healthcare-laboratory',
      'icon' => 'bi-grid',
    ),
    8 => 
    array (
      'label' => 'Radiology',
      'module' => 'healthcare-radiology',
      'icon' => 'bi-grid',
    ),
    9 => 
    array (
      'label' => 'Medical Billing',
      'module' => 'healthcare-medical-billing',
      'icon' => 'bi-grid',
    ),
    10 => 
    array (
      'label' => 'Insurance Claims',
      'module' => 'healthcare-insurance-claims',
      'icon' => 'bi-grid',
    ),
    11 => 
    array (
      'label' => 'Inpatient Management',
      'module' => 'healthcare-inpatient-management',
      'icon' => 'bi-grid',
    ),
    12 => 
    array (
      'label' => 'Ward Management',
      'module' => 'healthcare-ward-management',
      'icon' => 'bi-grid',
    ),
    13 => 
    array (
      'label' => 'Emergency Services',
      'module' => 'healthcare-emergency-services',
      'icon' => 'bi-grid',
    ),
    14 => 
    array (
      'label' => 'Medical Reports',
      'module' => 'healthcare-medical-reports',
      'icon' => 'bi-grid',
    ),
  ),
  'dashboard_features' => 
  array (
    0 => 'Patient Registration dashboard',
    1 => 'Patient Records dashboard',
    2 => 'Doctors Management dashboard',
    3 => 'Appointments dashboard',
  ),
  'sub_industries' => 
  array (
    0 => 
    array (
      'slug' => 'standard',
      'name' => 'Healthcare Standard',
      'description' => 'Run patient operations, doctors, appointments, clinical records, pharmacy, laboratory, billing, insurance, wards, and reports.',
      'dashboard_features' => 
      array (
        0 => 'Patient Registration dashboard',
        1 => 'Patient Records dashboard',
        2 => 'Doctors Management dashboard',
        3 => 'Appointments dashboard',
      ),
    ),
    1 => 
    array (
      'slug' => 'multi-branch',
      'name' => 'Multi-Branch Healthcare',
      'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for Healthcare.',
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
      'name' => 'Enterprise Healthcare',
      'description' => 'Scale Healthcare operations with advanced controls, reports, workflows, and templates.',
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
