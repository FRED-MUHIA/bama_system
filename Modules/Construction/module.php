<?php

$modules = [
    'BOQ Management', 'Estimation', 'Tender Management', 'Site Management', 'Project Planning',
    'Procurement', 'Contractor Management', 'Material Tracking', 'Progress Tracking', 'Certificates',
    'Variations', 'Subcontractors', 'Equipment', 'Site Labour', 'Safety', 'Quality Control',
    'Defects', 'Handover', 'Retention', 'Construction Finance', 'Reports',
];

$subIndustries = [
    ['slug' => 'standard', 'name' => 'Construction Standard', 'modules' => ['Projects', 'BOQ', 'Estimating', 'Site Management', 'Materials', 'Procurement', 'Contractors', 'Progress', 'Certificates', 'Finance', 'Reports']],
    ['slug' => 'multi-branch', 'name' => 'Multi-Branch Construction', 'modules' => ['Projects', 'BOQ', 'Estimating', 'Site Management', 'Materials', 'Procurement', 'Contractors', 'Progress', 'Certificates', 'Finance', 'Reports', 'Multiple Branches', 'Branch Warehouses', 'Cross-Branch Procurement', 'Branch Cost Reporting']],
    ['slug' => 'enterprise', 'name' => 'Enterprise Construction', 'modules' => array_merge($modules, ['Executive Dashboard', 'Advanced Approval Workflows', 'Portfolio Management', 'Cross-Project Analytics', 'Advanced Cost Control', 'Cash Flow Forecasting', 'Resource Planning', 'Document Control', 'Contract Administration'])],
    ['slug' => 'general-contractor', 'name' => 'General Contractor', 'modules' => ['Projects', 'Subcontractors', 'Procurement', 'Cost Control', 'Site Management', 'Certificates']],
    ['slug' => 'building-contractor', 'name' => 'Building Contractor', 'modules' => ['Projects', 'BOQ', 'Sites', 'Materials', 'Labour', 'Quality', 'Safety']],
    ['slug' => 'civil-engineering', 'name' => 'Civil Engineering', 'modules' => ['Structures', 'Measurement', 'Equipment', 'Quality', 'Safety']],
    ['slug' => 'roads-infrastructure', 'name' => 'Roads & Infrastructure', 'modules' => ['Chainage', 'Sections', 'Structures', 'Earthworks', 'Plant Usage', 'Materials']],
    ['slug' => 'electrical-contractor', 'name' => 'Electrical Contractor', 'modules' => ['BOQ', 'Materials', 'Site Instructions', 'Quality', 'Certificates']],
    ['slug' => 'mechanical-contractor', 'name' => 'Mechanical Contractor', 'modules' => ['BOQ', 'Equipment', 'Procurement', 'Quality', 'Certificates']],
    ['slug' => 'plumbing-contractor', 'name' => 'Plumbing Contractor', 'modules' => ['BOQ', 'Materials', 'Site Management', 'Quality', 'Handover']],
    ['slug' => 'interior-fit-out', 'name' => 'Interior Fit-Out', 'modules' => ['BOQ', 'Finishing Materials', 'Subcontractors', 'Room/Area Progress', 'Snagging', 'Handover']],
    ['slug' => 'renovation-remodeling', 'name' => 'Renovation & Remodeling', 'modules' => ['Projects', 'BOQ', 'Variations', 'Defects', 'Handover']],
    ['slug' => 'landscaping', 'name' => 'Landscaping', 'modules' => ['Projects', 'Materials', 'Labour', 'Equipment', 'Progress']],
    ['slug' => 'roofing-contractor', 'name' => 'Roofing Contractor', 'modules' => ['BOQ', 'Safety', 'Materials', 'Progress', 'Quality']],
    ['slug' => 'steel-fabrication', 'name' => 'Steel Fabrication', 'modules' => ['BOQ', 'Materials', 'Equipment', 'Quality', 'Delivery']],
    ['slug' => 'concrete-works', 'name' => 'Concrete Works', 'modules' => ['BOQ', 'Materials', 'Equipment', 'Quality', 'Safety']],
    ['slug' => 'quantity-surveying', 'name' => 'Quantity Surveying', 'modules' => ['BOQ', 'Rate Analysis', 'Measurements', 'Certificates', 'Variations', 'Cost Reports']],
    ['slug' => 'property-development', 'name' => 'Property Development', 'modules' => ['Portfolio Management', 'Projects', 'Cost Control', 'Cash Flow', 'Handover']],
    ['slug' => 'epc-contractor', 'name' => 'EPC Contractor', 'modules' => ['Engineering', 'Procurement', 'Construction', 'Document Control', 'Contracts', 'Cost Control', 'Project Planning']],
    ['slug' => 'specialist-subcontractor', 'name' => 'Specialist Subcontractor', 'modules' => ['Subcontracts', 'Progress', 'Certificates', 'Quality', 'Safety']],
];

foreach ($subIndustries as &$sub) {
    $sub['description'] = 'Construction operating profile for '.$sub['name'].'.';
    $sub['dashboard_features'] = array_slice(array_map(fn ($module) => $module.' dashboard', $sub['modules']), 0, 6);
}
unset($sub);

return [
    'name' => 'Construction',
    'slug' => 'construction',
    'type' => 'industry',
    'description' => 'Control BOQs, tenders, sites, contractors, materials, certificates, defects, and handover workflows.',
    'modules' => $modules,
    'features' => $modules,
    'core_modules' => ['CRM', 'Projects', 'Finance', 'Accounting', 'Cost Accounting', 'Procurement', 'Inventory', 'Documents', 'Reporting', 'Portal', 'Notifications', 'Communication'],
    'routes' => ['web' => 'construction.dashboard', 'api' => '/api/v1/industries/construction'],
    'api' => ['prefix' => '/api/v1/industries/construction'],
    'widgets' => ['construction-overview', 'construction-project-progress', 'construction-budget-actual', 'construction-cash-flow', 'construction-safety-quality'],
    'reports' => [
        'Project Status', 'Project Progress', 'Delayed Projects', 'Milestone Performance',
        'BOQ Summary', 'BOQ vs Actual', 'Quantity Variance', 'Budget vs Actual', 'Committed Cost',
        'Project Profitability', 'Cost To Complete', 'Cost by Work Package', 'Material Usage',
        'Material Variance', 'Material Waste', 'Stock by Project', 'Purchase Orders', 'Supplier Spend',
        'Procurement Lead Time', 'Contractor Performance', 'Subcontract Value', 'Certified vs Paid',
        'Retention', 'Certificate Register', 'Variation Register', 'Retention Register',
        'Contract Value Movement', 'Daily Progress', 'Manpower', 'Equipment Usage', 'Delay Register',
        'Incident Report', 'Safety Performance', 'Inspection Results', 'NCR Register', 'Defect Register',
    ],
    'workflows' => ['Lead', 'Estimate', 'Tender', 'Award', 'Project', 'BOQ', 'Budget', 'Planning', 'Site Mobilization', 'Procurement', 'Construction', 'Measurement', 'Certification', 'Invoice', 'Payment', 'Variations', 'Quality & Safety', 'Defects', 'Handover', 'Final Completion'],
    'templates' => ['BOQ Template', 'Tender Checklist', 'Daily Site Report', 'Interim Certificate', 'Handover Checklist'],
    'permissions' => [
        'construction.view', 'construction.dashboard', 'boq.view', 'boq.create', 'boq.update', 'boq.approve',
        'estimates.manage', 'tenders.manage', 'projects.manage', 'sites.manage', 'site_reports.create',
        'materials.manage', 'material_requests.create', 'material_requests.approve', 'construction.procurement',
        'contractors.manage', 'subcontracts.manage', 'measurements.create', 'measurements.approve',
        'certificates.create', 'certificates.approve', 'variations.create', 'variations.approve',
        'rfi.manage', 'site_instructions.manage', 'quality.manage', 'safety.manage', 'defects.manage',
        'handover.manage', 'construction.finance', 'construction.reports', 'construction.settings',
    ],
    'menus' => [
        ['label' => 'Dashboard', 'route' => 'construction.dashboard', 'icon' => 'bi-speedometer2', 'permission' => 'construction.dashboard', 'tables' => ['construction_project_profiles']],
        ['label' => 'Projects', 'route' => 'construction.projects', 'icon' => 'bi-kanban', 'permission' => 'projects.manage', 'tables' => ['construction_project_profiles']],
        ['label' => 'BOQ & Estimating', 'route' => 'construction.boqs', 'icon' => 'bi-list-columns', 'permission' => 'boq.view', 'tables' => ['construction_boqs']],
        ['label' => 'Tenders', 'route' => 'construction.tenders', 'icon' => 'bi-file-earmark-check', 'permission' => 'tenders.manage', 'tables' => ['construction_tenders']],
        ['label' => 'Site', 'route' => 'construction.site', 'icon' => 'bi-cone-striped', 'permission' => 'sites.manage', 'tables' => ['construction_sites']],
        ['label' => 'Materials', 'route' => 'construction.materials', 'icon' => 'bi-bricks', 'permission' => 'materials.manage', 'tables' => ['construction_materials']],
        ['label' => 'Contractors', 'route' => 'construction.contractors', 'icon' => 'bi-person-badge', 'permission' => 'contractors.manage', 'tables' => ['construction_contractors']],
        ['label' => 'Commercial', 'route' => 'construction.commercial', 'icon' => 'bi-cash-coin', 'permission' => 'construction.finance', 'tables' => ['construction_certificates']],
        ['label' => 'Quality', 'route' => 'construction.quality', 'icon' => 'bi-patch-check', 'permission' => 'quality.manage', 'tables' => ['construction_quality_inspections']],
        ['label' => 'Safety', 'route' => 'construction.safety', 'icon' => 'bi-shield-exclamation', 'permission' => 'safety.manage', 'tables' => ['construction_safety_incidents']],
        ['label' => 'Equipment', 'route' => 'construction.equipment', 'icon' => 'bi-truck-front', 'permission' => 'construction.view', 'tables' => ['construction_equipment']],
        ['label' => 'Handover', 'route' => 'construction.handover', 'icon' => 'bi-key', 'permission' => 'handover.manage', 'tables' => ['construction_handovers']],
        ['label' => 'Reports', 'route' => 'construction.reports', 'icon' => 'bi-bar-chart', 'permission' => 'construction.reports', 'tables' => ['construction_project_profiles']],
        ['label' => 'Mobile Site', 'route' => 'construction.mobile', 'icon' => 'bi-phone', 'permission' => 'construction.view', 'tables' => ['construction_site_reports']],
    ],
    'dashboard_features' => ['Project Progress', 'Budget vs Actual', 'Revenue vs Cost', 'Cash Flow', 'Material Consumption', 'Tender Conversion'],
    'sub_industries' => $subIndustries,
    'registration_sub_industries' => array_column($subIndustries, 'slug'),
    'roles' => ['Construction Administrator', 'Project Director', 'Project Manager', 'Construction Manager', 'Site Manager', 'Site Engineer', 'Quantity Surveyor', 'Estimator', 'Planner', 'Procurement Officer', 'Store Keeper', 'Safety Officer', 'Quality Officer', 'Foreman', 'Equipment Manager', 'Finance Manager', 'Accountant', 'Viewer'],
    'menu_structure' => ['Dashboard', 'Projects', 'BOQ & Estimating', 'Tenders', 'Site', 'Materials', 'Procurement', 'Contractors', 'Commercial', 'Labour', 'Equipment', 'Quality', 'Safety', 'Documents', 'Handover', 'Finance', 'Reports', 'Settings'],
    'tenant_isolated' => true,
    'role_permissions' => true,
    'dynamic_menus' => true,
    'dashboard_widgets' => true,
    'api_endpoints' => true,
    'subscription_activated' => true,
];
