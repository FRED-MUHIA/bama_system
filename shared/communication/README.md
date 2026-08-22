# Shared Communication Module

`shared/communication` is the ERP-wide collaboration layer for Retail, Fitness & Gym, Restaurant, Pharmacy, Manufacturing, Wholesale, Hospitality, and Services.

Industries must consume this module through `Shared\Communication\Contracts\CommunicationServiceContract`. They should not create their own messaging, notification, announcement, file sharing, or channel systems.

## Capabilities

- Direct messaging
- Group, department, branch, role, industry, team, project, and ERP record channels
- Announcements with priority, scheduled publishing, read tracking, and acknowledgement tracking
- Task and record discussions for orders, purchase orders, inventory transfers, memberships, customers, and other ERP records
- ERP event messages for sales, refunds, stock alerts, membership expiry, invoices, payments, goods received, leave requests, and onboarding events
- Mentions for `@user`, `@department`, `@role`, `@branch`, and `@everyone`
- File, image, document, and media attachment metadata that can point to shared document records
- Notification center for in-app, push, email, and SMS delivery preferences
- Read receipts, delivered status, presence events, typing events, and WebSocket broadcast events
- Communication permission matrix and audit logging

## Integration

Industry services should inject:

```php
use Shared\Communication\Contracts\CommunicationServiceContract;
```

Examples:

```php
$communication->postErpEvent('retail.low_stock', 'Low stock for SKU-1001', [
    'industry' => 'retail',
    'related_type' => Product::class,
    'related_id' => $product->id,
]);
```

```php
$channel = $communication->createChannel([
    'name' => 'Purchase Order PO-1001',
    'type' => 'Record',
    'record_type' => PurchaseOrder::class,
    'record_id' => $purchaseOrder->id,
    'member_ids' => [$managerId, $procurementId],
], $actor);
```

## Shared Dependencies

This module reuses platform tenants, businesses, users, roles, departments, branches, teams, projects, documents, notifications preferences, and audit conventions. It does not duplicate industry-specific workflows.
