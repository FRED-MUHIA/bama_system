# Salon & Spa Industry Module

## Purpose

The Salon & Spa industry package extends the multi-tenant ERP platform with appointment-led service operations while reusing shared platform modules for CRM, POS, payments, finance, accounting, inventory, HR, reporting, notifications, documents, audit logs, and tenant/user management.

## Module Location

- Module manifest: `Modules/Salon/module.php`
- Models: `Modules/Salon/Models`
- Services: `Modules/Salon/Services`
- Repository: `Modules/Salon/Repositories/SalonRepository.php`
- Web controllers: `Modules/Salon/Controllers`
- API controllers: `Modules/Salon/Controllers/Api`
- Views: `resources/views/salon`
- Migration: `database/migrations/2026_07_28_000003_create_salon_spa_industry_module.php`

## Supported Packages

- Salon & Spa Standard
- Multi-Branch Salon & Spa
- Enterprise Salon & Spa

Each package provisions dashboards, menus, permissions, reports, workflows, templates, and industry widgets through the existing industry registry and module registry.

## Core Tables

- `salon_client_profiles`
- `salon_staff_profiles`
- `salon_services`
- `salon_resources`
- `salon_appointments`
- `salon_appointment_services`
- `salon_staff_schedules`
- `salon_consultations`
- `salon_treatments`
- `salon_membership_plans`
- `salon_memberships`
- `salon_packages`
- `salon_gift_cards`
- `salon_loyalty_accounts`
- `salon_product_consumptions`
- `salon_commissions`
- `salon_wellness_programs`
- `salon_wellness_enrollments`

All operational tables include `tenant_id` and `business_id` and inherit tenant/business scoping through `BelongsToBusiness`.

## Shared Module Boundaries

The Salon & Spa package does not duplicate shared systems:

- Clients are stored in the shared `clients` table and extended by `salon_client_profiles`.
- Product usage references shared `products` and posts stock movements through `App\Services\StockService`.
- POS links reference shared `pos_orders`.
- Invoices and payments reference shared `invoices` and `payments`.
- Permissions are managed through shared IAM.
- Dashboards use the shared dashboard-widget registry.

## Web Routes

- `GET /salon-spa`
- `GET|POST /salon-spa/appointments`
- `POST /salon-spa/appointments/{appointment}/complete`
- `GET|POST /salon-spa/clients`
- `GET|POST /salon-spa/staff`
- `GET|POST /salon-spa/services`
- `GET /salon-spa/pos`
- `GET /salon-spa/memberships`
- `GET /salon-spa/loyalty`
- `GET /salon-spa/consultations`
- `GET /salon-spa/treatments`
- `GET /salon-spa/inventory-usage`
- `POST /salon-spa/appointments/{appointment}/product-consumption`
- `GET /salon-spa/commissions`
- `GET /salon-spa/wellness`
- `GET /salon-spa/reports`

## API Routes

Authenticated API routes live under `/api/v1/industries/salon`:

- `GET /dashboard`
- `GET /appointments`
- `POST /appointments`
- `POST /appointments/{appointment}/complete`
- `GET /services`
- `GET /clients`

Public package discovery remains available through the existing industry package API:

- `GET /api/v1/industry-packages/salon`
- `GET /api/v1/industry-packages/salon/dashboard?sub_industry=multi-branch`

## Main Service Contract

`Modules\Salon\Contracts\SalonSpaServiceContract` defines the operational boundary:

- `dashboard()`
- `createService()`
- `createClientProfile()`
- `createStaffProfile()`
- `bookAppointment()`
- `completeAppointment()`
- `recordProductConsumption()`

## Operational Flow

1. Create or select a shared CRM client.
2. Create a Salon client profile with preferences, allergies, and beauty profile data.
3. Create staff, services, rooms/chairs, memberships, and wellness programs.
4. Book an appointment with one or more service lines.
5. Complete the appointment.
6. Completion updates client visit/spend totals, earns loyalty points, and creates commission accruals.
7. Product consumption records inventory usage and calls shared stock movement logic.
8. POS, invoice, and payment settlement remains in the shared commerce/finance modules.

## Roles

- Salon Owner
- Salon Manager
- Salon Receptionist
- Stylist
- Spa Therapist
- Salon Cashier
- Salon Inventory Clerk
- Salon Branch Manager
- Wellness Consultant
- Salon Finance Officer

## Reports

- Appointment Utilization
- Service Revenue by Category
- Staff Commission and Productivity
- Membership Retention
- Gift Card Liability
- Product Consumption and Margin
- Client Loyalty and Repeat Visits
- Multi-Branch Operating Summary
