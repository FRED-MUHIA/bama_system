# Smart Product Scanning & Verification

## Workflow

1. Capture image, camera input, or scanner device input.
2. Decode barcode, QR, 2D, or Data Matrix-style payload.
3. Extract SKU, barcode, QR product code, GTIN, UPC, EAN, or internal product number.
4. Lookup product through the shared Product Catalog.
5. Apply RetailPromotionService pricing.
6. Verify expiry, recall, quarantine, product status, compliance status, and age restrictions.
7. Return a POS-ready cart item.
8. Optionally update shared Product stock and Retail inventory balances.
9. Track batch movement when a batch is present.
10. Generate scan event, verification record, fraud signal, and audit log.

## Tables

- `scan_devices`
- `scan_events`
- `product_batches`
- `product_batch_movements`
- `product_expiry`
- `product_verification`
- `scan_audit_logs`
- `self_checkout_transactions`
- `camera_scan_events`

## Compliance Blocks

The engine blocks sale when a product is:

- Expired
- Recalled
- Quarantined
- Disabled
- Non-compliant
- Age restricted without DOB, ID verification, or manager override

## POS Integration

Successful scans return a `cart_item` payload compatible with existing POS item fields:

- `product_id`
- `title`
- `description`
- `quantity`
- `unit_price`
- `discount`
- `tax_rate`

## Inventory Integration

When `update_inventory` is true, the scanning engine calls RetailInventoryService, which in turn uses the shared StockService. This keeps stock movements, branch stock, warehouse stock, and audit records aligned with the existing ERP inventory model.
