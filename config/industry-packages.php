<?php

$coreModules = [
    'crm' => 'CRM',
    'projects' => 'Projects',
    'finance' => 'Finance',
    'accounting' => 'Accounting',
    'documents' => 'Documents',
    'reporting' => 'Reporting',
    'hr' => 'HR',
    'administration' => 'Administration',
    'portal' => 'Portal',
    'notifications' => 'Notifications',
];

$sharedFeatures = [
    [
        'name' => 'Mobile Money',
        'description' => 'Built-in support for M-Pesa and other local mobile payments alongside cash and cards.',
    ],
    [
        'name' => 'Inventory Control',
        'description' => 'Real-time stock updates and low-stock alerts.',
    ],
    [
        'name' => 'Offline Mode',
        'description' => 'Ability to record sales without an active internet connection and sync later.',
    ],
];

$sharedFeatureNames = array_column($sharedFeatures, 'name');
$constructionPackage = file_exists(base_path('Modules/Construction/module.php')) ? require base_path('Modules/Construction/module.php') : null;
$agriculturePackage = file_exists(base_path('Modules/Agriculture/module.php')) ? require base_path('Modules/Agriculture/module.php') : null;
$printingBrandingPackage = file_exists(base_path('Modules/PrintingBranding/module.php')) ? require base_path('Modules/PrintingBranding/module.php') : null;
$automotivePackage = file_exists(base_path('Modules/Automotive/module.php')) ? require base_path('Modules/Automotive/module.php') : null;
$retailPackage = file_exists(base_path('Modules/Retail/module.php')) ? require base_path('Modules/Retail/module.php') : null;

$industries = [
    'construction' => [
        $constructionPackage['name'] ?? 'Construction',
        $constructionPackage['description'] ?? 'Control BOQs, tenders, sites, contractors, materials, certificates, defects, and handover workflows.',
        $constructionPackage['modules'] ?? ['BOQ Management', 'Estimation', 'Tender Management', 'Site Management', 'Project Planning', 'Procurement', 'Contractor Management', 'Material Tracking'],
        [
            'sub_industries' => $constructionPackage['sub_industries'] ?? [],
            'registration_sub_industries' => $constructionPackage['registration_sub_industries'] ?? [],
            'dashboard_widgets' => $constructionPackage['dashboard_features'] ?? [],
            'reports' => $constructionPackage['reports'] ?? [],
            'roles' => $constructionPackage['roles'] ?? [],
            'menu_structure' => $constructionPackage['menu_structure'] ?? [],
        ],
    ],
    'real-estate' => ['Real Estate', 'Manage properties, units, leases, billing, tenants, inspections, sales, commissions, land, and valuations.', ['Property Management', 'Property Listings', 'Unit Management', 'Lease Management', 'Rental Billing', 'Property Maintenance', 'Tenant Management', 'Property Inspections', 'Sales Management', 'Commission Management', 'Land Management', 'Property Valuation']],
    'healthcare' => ['Healthcare', 'Run patient operations, doctors, appointments, clinical records, pharmacy, laboratory, billing, insurance, wards, and reports.', ['Patient Registration', 'Patient Records', 'Doctors Management', 'Appointments', 'Consultation Notes', 'Prescriptions', 'Pharmacy', 'Laboratory', 'Radiology', 'Medical Billing', 'Insurance Claims', 'Inpatient Management', 'Ward Management', 'Emergency Services', 'Medical Reports']],
    'education' => ['Education', 'Manage students, admissions, classes, timetables, exams, attendance, fees, parents, teachers, resources, transport, and library workflows.', ['Student Management', 'Admissions', 'Classes', 'Timetables', 'Exams', 'Grading', 'Attendance', 'Fee Management', 'Parent Portal', 'Teacher Management', 'Learning Resources', 'Academic Reports', 'School Transport', 'Library']],
    'university' => ['University', 'Coordinate faculties, departments, courses, semesters, registration, exams, graduation, research, hostels, student finance, and alumni.', ['Student Information System', 'Faculties', 'Departments', 'Courses', 'Semesters', 'Registration', 'Exams', 'Graduation Tracking', 'Research Management', 'Hostel Management', 'Student Finance', 'Alumni Management']],
    'retail' => [
        $retailPackage['name'] ?? 'Retail',
        $retailPackage['description'] ?? 'Unify POS, inventory, warehousing, catalog, loyalty, promotions, gift cards, customer accounts, returns, branches, and ecommerce.',
        $retailPackage['features'] ?? ['Point of Sale', 'Inventory', 'Warehousing', 'Product Catalog', 'Loyalty Programs', 'Promotions', 'Gift Cards', 'Customer Accounts', 'Returns Management', 'Branch Management', 'Ecommerce Integration'],
        [
            'sub_industries' => $retailPackage['sub_industries'] ?? [],
            'registration_sub_industries' => $retailPackage['registration_sub_industries'] ?? [],
            'dashboard_widgets' => ['Sales Today', 'Average Basket', 'Low Stock', 'Fast Moving Products', 'Customer Loyalty', 'Inventory Value'],
            'reports' => $retailPackage['reports'] ?? [],
            'roles' => $retailPackage['roles'] ?? [],
            'menu_structure' => $retailPackage['menu_structure'] ?? [],
        ],
    ],
    'wholesale' => ['Wholesale & Distribution', 'Operate inventory, warehouses, distribution routes, sales orders, purchase orders, fleet, pricing, transfers, and forecasting.', ['Inventory', 'Warehousing', 'Distribution Routes', 'Sales Orders', 'Purchase Orders', 'Fleet Tracking', 'Customer Pricing', 'Stock Transfers', 'Demand Forecasting']],
    'manufacturing' => ['Manufacturing', 'Track BOMs, planning, work orders, shop floor activity, quality, costing, inventory, maintenance, batches, and production reporting.', ['Bill of Materials', 'Production Planning', 'Work Orders', 'Shop Floor Management', 'Quality Assurance', 'Production Costing', 'Inventory Control', 'Machine Maintenance', 'Batch Tracking', 'Manufacturing Reports']],
    'hospitality' => [
        'Hospitality',
        'Run reservations, rooms, housekeeping, front desk, guest profiles, restaurant POS, events, billing, and loyalty.',
        ['Hotel Reservations', 'Room Management', 'Products / Stock', 'Housekeeping', 'Front Desk', 'Guest Profiles', 'Staff Management', 'Suppliers', 'Procurement', 'Restaurant POS', 'Event Booking', 'Billing', 'Customer Loyalty'],
        [
            'sub_industries' => [
                ['slug' => 'hotel', 'name' => 'Hotel', 'description' => 'Operate hotel reservations, rooms, guest services, front desk, housekeeping, restaurant, events, billing, and loyalty.', 'dashboard_features' => ['Executive KPIs', 'Risk Alerts', 'Workflow Performance', 'Compliance Status']],
                ['slug' => 'resort', 'name' => 'Resort', 'description' => 'Coordinate resort stays, amenities, guest experiences, maintenance, restaurants, event venues, and package revenue.', 'dashboard_features' => ['Executive KPIs', 'Risk Alerts', 'Workflow Performance', 'Compliance Status']],
                ['slug' => 'lodge', 'name' => 'Lodge', 'description' => 'Manage lodge rooms, guided stay operations, guest preferences, housekeeping, maintenance, billing, and retention.', 'dashboard_features' => ['Executive KPIs', 'Risk Alerts', 'Workflow Performance', 'Compliance Status']],
                ['slug' => 'guest-house', 'name' => 'Guest House', 'description' => 'Run guest house reservations, arrivals, room readiness, billing, service requests, and repeat guest engagement.', 'dashboard_features' => ['Executive KPIs', 'Risk Alerts', 'Workflow Performance', 'Compliance Status']],
                ['slug' => 'serviced-apartments', 'name' => 'Serviced Apartments', 'description' => 'Track serviced apartment stays, longer-term occupancy, housekeeping cycles, deposits, billing, and tenant services.', 'dashboard_features' => ['Executive KPIs', 'Risk Alerts', 'Workflow Performance', 'Compliance Status']],
                ['slug' => 'boutique-hotel', 'name' => 'Boutique Hotel', 'description' => 'Operate boutique guest experiences, VIP preferences, rooms, dining, events, service quality, and loyalty.', 'dashboard_features' => ['Executive KPIs', 'Risk Alerts', 'Workflow Performance', 'Compliance Status']],
                ['slug' => 'enterprise-hospitality', 'name' => 'Enterprise Hospitality', 'description' => 'Scale multi-property hospitality operations with executive KPIs, controls, cross-site workflows, and consolidated reporting.', 'dashboard_features' => ['Executive KPIs', 'Risk Alerts', 'Workflow Performance', 'Compliance Status']],
                ['slug' => 'restaurant-group', 'name' => 'Restaurant Group', 'description' => 'Extend existing POS with restaurant tables, waiters, kitchen orders, reservations, billing, and revenue reports.', 'dashboard_features' => ['Executive KPIs', 'Risk Alerts', 'Workflow Performance', 'Compliance Status']],
                ['slug' => 'conference-center', 'name' => 'Conference Center', 'description' => 'Manage conference rooms, event bookings, packages, catering, equipment, billing, and event revenue.', 'dashboard_features' => ['Executive KPIs', 'Risk Alerts', 'Workflow Performance', 'Compliance Status']],
                ['slug' => 'event-venue', 'name' => 'Event Venue', 'description' => 'Coordinate venues, bookings, catering, equipment, deposits, event billing, and revenue performance.', 'dashboard_features' => ['Executive KPIs', 'Risk Alerts', 'Workflow Performance', 'Compliance Status']],
                ['slug' => 'holiday-apartments', 'name' => 'Holiday Apartments', 'description' => 'Manage holiday apartment availability, reservations, cleaning schedules, guest services, and stay revenue.', 'dashboard_features' => ['Executive KPIs', 'Risk Alerts', 'Workflow Performance', 'Compliance Status']],
            ],
            'registration_sub_industries' => ['hotel', 'resort', 'lodge', 'guest-house', 'boutique-hotel', 'enterprise-hospitality', 'restaurant-group', 'conference-center', 'event-venue'],
            'dashboard_widgets' => ['Occupancy Rate', 'Available Rooms', "Today's Check-ins", "Today's Check-outs", 'Revenue Today', 'Monthly Revenue', 'Pending Reservations', 'Guest Satisfaction', 'Maintenance Requests', 'Restaurant Sales'],
            'reports' => ['Occupancy Reports', 'Revenue Reports', 'Guest Reports', 'Reservation Reports', 'Housekeeping Reports', 'Maintenance Reports', 'Restaurant Reports', 'Event Reports', 'Loyalty Reports'],
            'roles' => ['Hotel Manager', 'Front Desk Officer', 'Reservations Officer', 'Housekeeping Supervisor', 'Housekeeping Staff', 'Maintenance Officer', 'Restaurant Manager', 'Restaurant Staff', 'Events Coordinator', 'Finance Officer'],
            'menu_structure' => ['Dashboard', 'Reservations', 'Rooms', 'Products / Stock', 'Guests', 'Staff', 'Suppliers', 'Procurement', 'Front Desk', 'Check-In', 'Check-Out', 'Housekeeping', 'Maintenance', 'Restaurant POS', 'Events', 'Billing', 'Loyalty Program', 'Reports'],
        ],
    ],
    'restaurant' => ['Restaurant', 'Control restaurant POS, kitchen display, tables, reservations, online orders, delivery, menu, recipe costing, and loyalty.', ['Restaurant POS', 'Kitchen Display System', 'Table Management', 'Reservations', 'Online Ordering', 'Delivery Management', 'Menu Management', 'Recipe Costing', 'Loyalty Programs']],
    'logistics' => ['Logistics', 'Manage fleets, shipments, routes, delivery schedules, drivers, fuel, vehicle maintenance, billing, and warehouse operations.', ['Fleet Management', 'Shipment Tracking', 'Route Planning', 'Delivery Scheduling', 'Driver Management', 'Fuel Management', 'Vehicle Maintenance', 'Logistics Billing', 'Warehouse Operations']],
    'transport' => ['Transport', 'Operate vehicles, drivers, ticketing, routes, maintenance, fuel, and passenger workflows.', ['Vehicle Management', 'Driver Management', 'Ticketing', 'Route Scheduling', 'Fleet Maintenance', 'Fuel Tracking', 'Passenger Management']],
    'professional-services' => ['Professional Services', 'Manage clients, projects, timesheets, resources, retainers, billing, contracts, service requests, and utilization reports.', ['Clients', 'Projects', 'Timesheets', 'Resource Planning', 'Retainers', 'Billing', 'Contracts', 'Service Requests', 'Utilization Reports']],
    'legal' => ['Legal', 'Coordinate cases, clients, legal documents, court schedules, billing, contracts, matters, and compliance.', ['Case Management', 'Clients', 'Legal Documents', 'Court Scheduling', 'Legal Billing', 'Contract Management', 'Matter Tracking', 'Compliance']],
    'accounting-firm' => ['Accounting Firm', 'Run client accounting, tax, audit, payroll, financial statements, and compliance tracking.', ['Client Accounting', 'Tax Management', 'Audit Management', 'Payroll Processing', 'Financial Statements', 'Compliance Tracking']],
    'insurance' => ['Insurance', 'Manage policies, claims, underwriting, premiums, renewals, agents, and risk assessment.', ['Policy Management', 'Claims Management', 'Underwriting', 'Premium Collection', 'Renewals', 'Agents Management', 'Risk Assessment']],
    'banking' => ['Banking & SACCO', 'Operate members, savings, loans, repayments, interest, share capital, statements, and mobile banking.', ['Member Management', 'Savings Accounts', 'Loans', 'Repayments', 'Interest Calculations', 'Share Capital', 'Statements', 'Mobile Banking']],
    'microfinance' => ['Microfinance', 'Manage borrowers, loans, collections, group lending, savings, guarantors, and credit scoring.', ['Borrowers', 'Loans', 'Collections', 'Group Lending', 'Savings', 'Guarantors', 'Credit Scoring']],
    'ngo' => ['NGO & Non-Profit', 'Track donors, grants, programs, beneficiaries, fundraising, monitoring and evaluation, and impact reporting.', ['Donor Management', 'Grants Management', 'Programs', 'Beneficiaries', 'Fundraising', 'Monitoring & Evaluation', 'Impact Reporting']],
    'government' => ['Government', 'Manage citizen records, licensing, permits, revenue collection, assets, service requests, and public projects.', ['Citizen Records', 'Licensing', 'Permits', 'Revenue Collection', 'Asset Management', 'Service Requests', 'Public Projects']],
    'agriculture' => [
        $agriculturePackage['name'] ?? 'Agriculture',
        $agriculturePackage['description'] ?? 'Coordinate farms, livestock, crop plans, harvests, inputs, equipment, and agricultural finance.',
        $agriculturePackage['modules'] ?? ['Farm Management', 'Livestock Management', 'Crop Planning', 'Harvest Tracking', 'Input Management', 'Equipment Tracking', 'Agricultural Finance'],
        [
            'sub_industries' => $agriculturePackage['sub_industries'] ?? [],
            'registration_sub_industries' => $agriculturePackage['registration_sub_industries'] ?? [],
            'dashboard_widgets' => $agriculturePackage['dashboard_features'] ?? [],
            'reports' => $agriculturePackage['reports'] ?? [],
            'roles' => $agriculturePackage['roles'] ?? [],
            'menu_structure' => $agriculturePackage['menu_structure'] ?? [],
        ],
    ],
    'printing_branding' => [
        $printingBrandingPackage['name'] ?? 'Printing & Branding',
        $printingBrandingPackage['description'] ?? 'Manage print estimating, artwork approval, production jobs, materials, machines, dispatch, costing, and profitability.',
        $printingBrandingPackage['modules'] ?? ['CRM', 'Estimating', 'Job Production', 'Artwork Management', 'Proof Approval', 'Inventory', 'Dispatch', 'Costing', 'Reporting'],
        [
            'sub_industries' => $printingBrandingPackage['sub_industries'] ?? [],
            'registration_sub_industries' => $printingBrandingPackage['registration_sub_industries'] ?? [],
            'dashboard_widgets' => $printingBrandingPackage['dashboard_features'] ?? [],
            'reports' => $printingBrandingPackage['reports'] ?? [],
            'roles' => $printingBrandingPackage['roles'] ?? [],
            'menu_structure' => $printingBrandingPackage['menu_structure'] ?? [],
        ],
    ],
    'pharmacy' => ['Pharmacy', 'Run drug inventory, prescriptions, suppliers, expiry tracking, pharmacy POS, and insurance claims.', ['Drug Inventory', 'Prescriptions', 'Suppliers', 'Expiry Tracking', 'Pharmacy POS', 'Insurance Claims']],
    'media' => ['Media & Marketing', 'Manage campaigns, clients, content calendars, social media, leads, creative projects, and analytics.', ['Campaign Management', 'Clients', 'Content Calendar', 'Social Media', 'Leads', 'Creative Projects', 'Analytics']],
    'telecom' => ['Telecommunications', 'Operate subscribers, billing, SIM management, packages, usage tracking, and customer support.', ['Subscribers', 'Billing', 'SIM Management', 'Packages', 'Usage Tracking', 'Customer Support']],
    'automotive' => [
        $automotivePackage['name'] ?? 'Automotive',
        $automotivePackage['description'] ?? 'Manage vehicle sales, workshops, service bookings, spare parts, fleet maintenance, and warranty tracking.',
        $automotivePackage['modules'] ?? ['Vehicle Sales', 'Workshop Management', 'Service Bookings', 'Spare Parts', 'Fleet Maintenance', 'Warranty Tracking'],
        [
            'sub_industries' => $automotivePackage['sub_industries'] ?? [],
            'registration_sub_industries' => $automotivePackage['registration_sub_industries'] ?? [],
            'dashboard_widgets' => $automotivePackage['dashboard_features'] ?? [],
            'reports' => $automotivePackage['reports'] ?? [],
            'roles' => $automotivePackage['roles'] ?? [],
            'menu_structure' => $automotivePackage['menu_structure'] ?? [],
        ],
    ],
    'fitness' => ['Fitness & Gym', 'Run memberships, trainers, attendance, class schedules, payments, and fitness programs.', ['Memberships', 'Trainers', 'Attendance', 'Class Scheduling', 'Payments', 'Fitness Programs']],
    'salon' => [
        'Salon & Spa',
        'Manage appointments, staff schedules, services, POS, memberships, customer loyalty, consultations, treatments, packages, gift cards, product usage, commissions, chair and room management, wellness programs, and multi-branch operations.',
        ['Appointments', 'Staff Scheduling', 'Services', 'POS', 'Memberships', 'Customer Loyalty', 'Beauty Consultations', 'Treatments', 'Packages', 'Gift Cards', 'Client Profiles', 'Inventory Usage', 'Commission Management', 'Chair & Room Management', 'Wellness Programs', 'Multi-Branch Operations'],
        [
            'sub_industries' => [
                ['slug' => 'standard', 'name' => 'Salon & Spa Standard', 'description' => 'Run a single-location salon or spa with bookings, staff schedules, services, POS, memberships, loyalty, treatments, and product usage.', 'dashboard_features' => ['Appointments dashboard', 'Staff Scheduling dashboard', 'Services dashboard', 'POS dashboard', 'Client Loyalty dashboard', 'Product Usage dashboard']],
                ['slug' => 'multi-branch', 'name' => 'Multi-Branch Salon & Spa', 'description' => 'Coordinate branches, rooms, teams, permissions, consolidated reports, gift cards, and shared customer profiles.', 'dashboard_features' => ['Branch performance', 'Team workload', 'Consolidated revenue', 'Appointment capacity', 'Gift card liability', 'Cross-branch loyalty']],
                ['slug' => 'enterprise', 'name' => 'Enterprise Salon & Spa', 'description' => 'Scale salon and spa operations with executive controls, workflow templates, advanced permissions, compliance reports, and API integrations.', 'dashboard_features' => ['Executive KPIs', 'Risk alerts', 'Workflow performance', 'Compliance status', 'Commission accruals', 'Retention analytics']],
            ],
            'registration_sub_industries' => ['standard', 'multi-branch', 'enterprise'],
            'dashboard_widgets' => ['Appointments Today', 'Confirmed Today', 'Revenue MTD', 'Active Clients', 'Active Staff', 'Active Memberships', 'Product Consumption MTD', 'Commission Payable', 'Low Stock Items', 'Client Retention'],
            'reports' => ['Appointment Utilization', 'Service Revenue by Category', 'Staff Commission and Productivity', 'Membership Retention', 'Gift Card Liability', 'Product Consumption and Margin', 'Client Loyalty and Repeat Visits', 'Multi-Branch Operating Summary'],
            'roles' => ['Salon Owner', 'Salon Manager', 'Receptionist', 'Stylist', 'Spa Therapist', 'Cashier', 'Inventory Clerk', 'Branch Manager', 'Wellness Consultant', 'Finance Officer'],
            'menu_structure' => ['Dashboard', 'Appointments', 'Clients', 'Staff Scheduling', 'Services', 'POS', 'Memberships', 'Loyalty & Gift Cards', 'Beauty Consultations', 'Treatments', 'Product Usage', 'Commissions', 'Wellness Programs', 'Reports'],
        ],
    ],
    'events' => ['Event Management', 'Coordinate events, venues, ticketing, sponsors, vendors, registrations, and event finance.', ['Events', 'Venues', 'Ticketing', 'Sponsors', 'Vendors', 'Registrations', 'Event Finance']],
];

return [
    'core_modules' => $coreModules,
    'shared_features' => $sharedFeatures,
    'industries' => collect($industries)->map(function (array $definition, string $slug) use ($coreModules, $sharedFeatureNames) {
        [$name, $description, $modules, $overrides] = array_pad($definition, 4, []);
        $dashboardFeatures = array_slice(array_map(fn (string $module) => $module.' dashboard', $modules), 0, 4);
        $subIndustries = $overrides['sub_industries'] ?? [
            ['slug' => 'standard', 'name' => $name.' Standard', 'description' => $description, 'dashboard_features' => $dashboardFeatures],
            ['slug' => 'multi-branch', 'name' => 'Multi-Branch '.$name, 'description' => 'Coordinate multiple branches, teams, permissions, reports, and operating units for '.$name.'.', 'dashboard_features' => ['Branch performance', 'Team workload', 'Consolidated reporting', 'Approval queue']],
            ['slug' => 'enterprise', 'name' => 'Enterprise '.$name, 'description' => 'Scale '.$name.' operations with advanced controls, reports, workflows, and templates.', 'dashboard_features' => ['Executive KPIs', 'Risk alerts', 'Workflow performance', 'Compliance status']],
        ];

        return [
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
            'core_modules' => array_values($coreModules),
            'modules' => $modules,
            'menus' => array_map(fn (string $module) => ['label' => $module, 'module' => str($slug.'-'.$module)->slug()->value(), 'icon' => 'bi-grid'], $modules),
            'permissions' => array_merge(
                [$slug.'.view', $slug.'.manage', $slug.'.reports'],
                array_map(fn (string $module) => str($slug.'.'.$module.'.view')->slug('.')->value(), $modules)
            ),
            'reports' => $overrides['reports'] ?? ['Executive summary', 'Operational performance', 'Compliance report', 'Financial performance'],
            'workflows' => ['Create', 'Review', 'Approve', 'Post', 'Report'],
            'templates' => ['Default dashboard', 'Management report', 'Approval workflow', 'Document template'],
            'features' => array_values(array_unique(array_merge($sharedFeatureNames, array_slice($modules, 0, 6)))),
            'dashboard_features' => $overrides['dashboard_widgets'] ?? $dashboardFeatures,
            'sub_industries' => $subIndustries,
            'registration_sub_industries' => $overrides['registration_sub_industries'] ?? collect($subIndustries)->pluck('slug')->all(),
            'roles' => $overrides['roles'] ?? [],
            'menu_structure' => $overrides['menu_structure'] ?? array_map(fn (array $menu) => $menu['label'], array_map(fn (string $module) => ['label' => $module], $modules)),
        ];
    })->values()->all(),
];
