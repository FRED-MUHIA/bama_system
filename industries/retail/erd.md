# Retail ERD Relationships

## Shared Entity Extensions

- `products` 1:1 `retail_product_profiles`
- `products` 1:M `retail_product_variants` as parent products
- `products` M:M `products` through `retail_product_bundles`
- `products` 1:M `retail_inventory_balances`
- `products` 1:M `retail_inventory_movements`
- `clients` 1:1 `retail_customer_profiles`
- `clients` 1:1 `retail_loyalty_accounts`
- `clients` 1:M `retail_gift_cards`
- `suppliers` 1:1 `retail_supplier_profiles`
- `pos_orders` 1:1 `retail_sales_extensions`
- `pos_orders` 1:M `retail_return_authorizations`

## Warehouse and Inventory

- `branches` 1:M `retail_warehouses`
- `retail_warehouses` 1:M `retail_warehouse_zones`
- `retail_warehouses` 1:M `retail_warehouse_bins`
- `retail_warehouse_zones` 1:M `retail_warehouse_bins`
- `retail_inventory_balances` belongs to product, optional branch, warehouse, and bin
- `retail_inventory_movements` belongs to product and optionally branch, warehouse, bin, and morph source

## Loyalty, Promotions, Gift Cards

- `retail_loyalty_accounts` 1:M `retail_loyalty_transactions`
- `retail_loyalty_transactions` optionally belongs to `pos_orders`
- `retail_promotions` optionally links to `retail_sales_extensions`
- `retail_gift_cards` 1:M `retail_gift_card_transactions`
- `retail_gift_card_transactions` optionally belongs to `pos_orders`

## Returns, Orders, Delivery

- `retail_return_authorizations` 1:M `retail_return_items`
- `retail_return_items` optionally belongs to `pos_order_items` and `products`
- `retail_orders` 1:M `retail_order_items`
- `retail_orders` 1:1 `retail_deliveries`
- `retail_orders` optionally belongs to `clients`, `branches`, and shared `pos_orders`
- `retail_deliveries` optionally belongs to `users` as driver

All retail tables include `tenant_id` and `business_id` where appropriate and use the platform `BelongsToBusiness` tenant/business scope.

## Smart Product Scanning

- `scan_devices` belongs to optional branch and retail warehouse
- `scan_events` belongs to optional scan device, product, POS order, branch, warehouse, and cashier
- `product_batches` belongs to product, optional supplier, branch, and retail warehouse
- `product_batches` 1:M `product_batch_movements`
- `product_expiry` belongs to product and optional product batch
- `product_verification` belongs to optional scan event, product, and product batch
- `scan_audit_logs` belongs to optional scan event, scan device, product, user, and branch
- `self_checkout_transactions` belongs to optional scan device, client, POS order, and branch
- `camera_scan_events` belongs to optional scan event and scan device

Scanning integrates with Product Catalog lookup, RetailPromotionService pricing, ProductComplianceService checks, RetailInventoryService inventory updates, BatchTrackingService traceability, and shared IamService audit logging.
