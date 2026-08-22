# Retail Industry Package

Retail is implemented as an isolated industry package in `Modules/Retail` and documented here under the requested industry path.

## Shared ERP Contracts

Retail reuses existing shared modules:

- Finance and Accounting: shared ledger, journals, posting, period controls, and reports.
- Payments: shared payment methods and POS payment records.
- CRM: `clients` are extended by `retail_customer_profiles` and `retail_loyalty_accounts`.
- Inventory Core: `products`, `product_categories`, `stock_movements`, and `StockService` remain the inventory source of truth.
- HR/User Management: retail staff are `users` with IAM roles and permissions.
- Procurement: suppliers, purchase orders, GRNs, supplier invoices, and payments remain shared ERP models.
- Reporting, Documents, Audit Logs, Notifications, Dashboard Framework, and Workflow Engine are consumed through existing routes/services and extension points.

## Runtime Modules

The web surface is registered under `/retail`:

- Dashboard
- Point of Sale
- Products
- Inventory
- Warehousing
- Orders
- Customers
- Loyalty Programs
- Promotions
- Gift Cards
- Returns
- Procurement
- Suppliers
- Branches
- Ecommerce
- Smart Product Scanning & Verification
- ETIMS Fiscal Compliance Integration via `shared/compliance/etims`
- Analytics
- Reports
- Settings

API endpoints are under `/api/v1/industries/retail`.

## Sub-Industries

- Retail Standard
- Multi-Branch Retail
- Enterprise Retail

## Deliverables Map

- Database schema and migrations: `database/migrations/2026_07_25_000001_create_retail_management_system.php`
- Models, repositories, services, controllers: `Modules/Retail`
- UI pages and forms: `resources/views/retail`
- Navigation and widgets: `Modules/Retail/module.php`
- API documentation: `industries/retail/api.md`
- ERD relationships: `industries/retail/erd.md`
- Permission matrix: `industries/retail/permission-matrix.md`
- Tests: `tests/Feature/RetailManagementTest.php`
- Shared ETIMS compliance module: `shared/compliance/etims`

## Smart Product Scanning & Verification

Smart Scanning extends Retail POS, Product Catalog, Promotions, Inventory, Warehousing, Compliance, Audit Logs, and Reporting.

Supported inputs:

- QR codes
- 1D barcodes
- 2D barcodes
- Data Matrix-style decoded payloads
- Mobile camera scans
- Self-checkout camera scans
- POS scanner device input

Core services:

- `QrDecoderService`
- `ProductIdentificationService`
- `SmartProductScanningService`
- `ProductComplianceService`
- `BatchTrackingService`
- `FraudDetectionService`
- `ScanAuditService`
- `SmartScanningAnalyticsService`
