# Real Estate Industry Module

The Real Estate industry is implemented as an isolated industry package under `Modules\RealEstate` and `industries/real_estate`.

It reuses shared ERP capabilities instead of duplicating them:

- CRM: tenants, buyers, and investors are extensions of shared `clients`.
- Finance, Accounting, Payments, ETIMS: rental billing and property sale invoices use shared invoices, payments, receipts, finance posting, and ETIMS-ready invoice records.
- Procurement: vendors for maintenance reuse shared suppliers and procurement records.
- Documents: leases, sale agreements, valuation reports, inspection reports, land titles, and contracts are linked to shared document templates and project documents.
- Communication: tenant, agent, branch, and internal messaging is designed for the shared Communication Center.
- Workflow and approvals: listing, lease, sale, maintenance, valuation, and commission approvals are represented as approval-ready statuses and permissions.

Primary web route: `real-estate.dashboard`

Primary API prefix: `/api/v1/industries/real-estate`

## Sub-Industries

- Real Estate Standard
- Multi-Branch Real Estate
- Enterprise Real Estate

## Main Domains

- Properties and portfolio
- Listings
- Units
- Tenants
- Leases
- Rental billing
- Utility billing, meter readings, and utility consumption
- Amenities and amenity bookings
- Tenant account ledgers and consolidated statements
- Buyers and sales
- Agents and commissions
- Maintenance and service requests
- Inspections
- Valuations
- Land parcels
- Development projects
- Reports and portal APIs

## Utilities, Amenities, And Statements

This enhancement adds Real Estate extensions for:

- Unlimited utility categories: water, electricity, internet, cable TV, security, garbage, sewer, parking, common area maintenance, service charges, and custom utilities.
- Meter-based billing with manual, bulk-upload, and smart-meter-ready reading sources.
- Amenity catalog and tenant bookings with optional shared Finance invoices.
- Tenant ledgers that consolidate rent, utilities, amenities, payments, credits, debits, refunds, and adjustments.
- Tenant statements for current month, previous month, custom ranges, and financial periods.
- Tenant offboarding with notice, lease termination, final inspection, utility reconciliation, final billing, deposit settlement, move-out, and archive steps.
- Tenant archiving that preserves lease, payment, utility, maintenance, document, finance, and audit history while removing the tenant from active lists and current billing cycles.
- Permanent tenant deletion guards that allow deletion only for record-free tenants by a super-admin user.

All charge invoices continue to use shared `invoices`, `invoice_items`, `payments`, `receipts`, Finance posting, Accounting, and ETIMS-ready flows.
