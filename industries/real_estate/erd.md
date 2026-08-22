# Real Estate ERD

## Utility And Amenity Extensions

- `real_estate_utility_types` belongs to a business and has many utility meters, rates, readings, bills, and consumption rows.
- `real_estate_utility_meters` belongs to a property, optional unit, optional tenant, and a utility type.
- `real_estate_meter_readings` belongs to a meter, property, optional unit, optional tenant, and utility type; it stores previous reading, current reading, consumption, rate, and charge.
- `real_estate_utility_bills` belongs to a tenant, optional lease, property, optional unit, utility type, optional reading, and shared `invoices`.
- `real_estate_amenities` belongs to a business and optionally a property.
- `real_estate_amenity_bookings` belongs to an amenity, tenant, optional unit, and optional shared invoice.
- `real_estate_tenant_ledgers` belongs to a tenant and optionally lease, property, unit, invoice, payment, and any ledgerable source.
- `real_estate_tenant_statements` belongs to a tenant and optional lease.
- `real_estate_utility_consumption` belongs to utility type and optionally tenant, unit, and meter reading.
- `real_estate_utility_invoices` links generated utility invoices back to tenants and optional statements.
- `real_estate_tenants` carries offboarding lifecycle fields for notice, lease termination, final inspection, utility reconciliation, final billing, deposit settlement, move-out, archive, restore, and notes.

Shared dependencies remain external: CRM is represented by `clients`, Finance by `invoices` and `payments`, Accounting by journal posting from shared Finance, and documents/communication/ETIMS remain shared modules.

## Tenant Offboarding Rules

Archiving preserves all related rows and releases current operations:

- Active leases are terminated and recurring billing is stopped.
- Linked units are marked vacant.
- Assigned utility meters are detached and made inactive.
- Open maintenance and service requests are closed.

Permanent deletion is blocked when lease, payment, utility, maintenance, service request, document, statement, amenity booking, or ledger records exist.

Core relationships:

- `real_estate_properties` belongs to `businesses`, optional `branches`, optional manager `users`.
- `real_estate_units` belongs to `real_estate_properties`.
- `real_estate_tenants` belongs to shared `clients`.
- `real_estate_buyers` belongs to shared `clients`.
- `real_estate_agents` optionally belongs to shared `users` and `branches`.
- `real_estate_listings` belongs to properties, optional units, optional agents.
- `real_estate_leases` belongs to properties, optional units, tenants, and optional document templates.
- `real_estate_rental_charges` belongs to leases and optional shared `invoices`.
- `real_estate_sales` belongs to properties, optional units, buyers, optional agents, optional shared `invoices`.
- `real_estate_commissions` belongs to agents and can morph to sales or future lease/listing records.
- `real_estate_maintenance_requests` belongs to properties, optional units, optional tenants, optional technicians, and optional suppliers.
- `real_estate_service_requests` belongs to optional tenants/properties/units and optional assigned users.
- `real_estate_inspections` belongs to properties, optional units, optional inspector users.
- `real_estate_valuations` belongs to properties and optional valuer users.
- `real_estate_land_parcels` optionally belongs to properties.
- `real_estate_development_projects` optionally links to shared `projects` and real-estate properties.
- `real_estate_documents` can morph to any real-estate domain record and link to shared document templates/project documents.

Accounting flow:

`Lease -> Rental Charge -> Shared Invoice -> Shared Payment/Receipt -> Finance Journal -> ETIMS Submission`

Sales flow:

`Property/Unit -> Buyer -> Sale -> Shared Invoice/Payment -> Commission -> Finance Reports`
