# SaaS Platform Foundation

This implementation adds an enterprise SaaS layer around the existing Laravel business system without removing current workflows.

## Added Platform Layers

- Tenants and tenant membership
- Tenant-aware model scope through `BelongsToTenant`
- Tenant-aware business scope through the existing `BelongsToBusiness`
- Active tenant resolution through `ActiveTenant`
- Module registry through `modules`, `tenant_modules`, and `industry_modules`
- Subscription plans, features, and usage tracking
- Tenant theme records and CSS variable injection
- Dashboard widget registry and tenant widget placement
- Dynamic navigation from enabled modules
- Tenant provisioning and onboarding route
- `/api/v1/context` foundation endpoint
- Industry module manifests under `Modules/`

## Migration Strategy

Run the SaaS foundation migration after the existing schema is in a healthy state:

```powershell
php artisan migrate --force
```

The migration adds nullable `tenant_id` columns to existing business-scoped tables and backfills current records into the starter `BAMA` tenant. Public document routes keep using explicit public tokens and route model loading where already implemented.

## Refactor Strategy

The current monolith remains operational while modules are extracted gradually:

1. Keep current controllers and routes active.
2. Register each current feature in `modules`.
3. Use `TenantModule::enabled('module-slug')` for route, menu, dashboard, and permission gating.
4. Move code into `Modules/<Name>` folders one module at a time.
5. Keep shared finance posting, document numbering, and portal behavior in services until extraction is complete.

## Tenant Hierarchy

Tenant -> Businesses -> Branches -> Departments -> Teams -> Users

`business_user` continues to support business-level IAM membership. `tenant_user` adds the SaaS-level membership boundary.
