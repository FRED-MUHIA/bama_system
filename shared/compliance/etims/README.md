# ETIMS Compliance Module

`shared/compliance/etims` is a reusable fiscal compliance module for Retail, Restaurant, Pharmacy, Wholesale, Manufacturing, and Services.

It owns ETIMS-specific concerns only:

- Fiscal invoice, credit note, and debit note submission records
- Receipt validation state
- QR payload generation
- Online, offline, batch, and retry queues
- ETIMS audit records
- Compliance dashboard metrics

It does not implement accounting, tax engines, invoicing engines, payment engines, reporting engines, user management, notifications, or document rendering. Industry modules submit fiscal documents through `EtimsComplianceServiceContract`.

## Retail Flow

Retail POS creates a sale through shared Documents, Payments, StockService, CRM, Loyalty, Gift Card, and Audit services. After the POS order is saved and stock is updated, Retail calls:

```php
EtimsComplianceServiceContract::submitSale($posOrder, ['industry' => 'retail']);
```

The shared module then queues and validates a fiscal invoice, stores the ETIMS invoice number, ETIMS receipt number, QR payload, status, timestamps, errors, and audit trail.
