# Real Estate API

Base prefix: `/api/v1/industries/real-estate`

## Tenant Portal And Statements

- `GET /tenants/{tenant}/ledger`
- `GET /tenants/{tenant}/payments`
- `GET /tenants/{tenant}/statements`
- `GET /tenants/{tenant}/utilities`
- `GET /tenants/archive`
- `GET /tenants/{tenant}/offboarding`
- `POST /tenants/{tenant}/notice`
- `POST /tenants/{tenant}/offboarding`
- `POST /tenants/{tenant}/archive`
- `POST /tenants/{tenant}/restore`

## Utility Billing

- `POST /utility/readings`
- `POST /utility/bills`

## Report Exports

- `GET /reports/tenant-payments/export`
- `GET /reports/tenant-ledger/export`
- `GET /reports/utility-billing/export`
- `GET /reports/move-outs/export`
- `GET /reports/archived-tenants/export`
- `GET /reports/tenant-history/export`
- `GET /reports/vacancy/export`

Existing endpoints remain available for dashboard, listings, leases, tenant portal, buyer portal, agent portal, and service requests.

All endpoints are under:

`/api/v1/industries/real-estate`

Authentication, tenant isolation, module checks, and permission checks are handled by shared middleware.

## Endpoints

`GET /dashboard`

Returns executive metrics for properties, units, occupancy, rent, sales pipeline, and maintenance.

`GET /listings`

Returns public-ready listings with property, unit, and agent data.

`GET /leases/{lease}`

Returns lease details with tenant CRM profile, property, unit, charges, and invoices.

`GET /tenant-portal/{tenant}`

Prepared tenant portal endpoint for leases, invoices, maintenance requests, service requests, and CRM details.

`GET /buyer-portal/{buyer}`

Prepared buyer portal endpoint for reservations, sales, invoices, and agreements.

`GET /agent-portal/{agent}`

Prepared agent portal endpoint for listings, sales, and commissions.

`POST /service-requests`

Creates a maintenance-backed service request for tenant-facing repairs and property issues.

## Shared Integrations

Rental billing creates shared `invoices`, so payments, receipts, accounting, reports, and ETIMS continue through existing shared modules.
