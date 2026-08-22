# Retail API

Retail POS fiscal compliance is delegated to the shared ETIMS module under `/api/v1/shared/compliance/etims`.

Base path: `/api/v1/industries/retail`

All endpoints require `auth`, tenant context, enabled Retail module, and the listed permission.

| Method | Endpoint | Permission | Purpose |
| --- | --- | --- | --- |
| GET | `/dashboard` | `retail.view` | Executive KPI payload |
| GET | `/products?q=` | `retail.products.view` | Product, SKU, barcode, brand search |
| GET | `/promotions` | `retail.promotions.view` | Active promotion list |
| POST | `/promotions` | `retail.promotions.manage` | Create promotion |
| POST | `/orders` | `retail.orders.manage` | Create retail order |
| GET | `/customers/{client}/loyalty` | `retail.loyalty.view` | Fetch or create loyalty account |
| POST | `/gift-cards` | `retail.gift-cards.manage` | Issue gift card |
| GET | `/gift-cards/{giftCard}/balance` | `retail.gift-cards.view` | Check gift card balance |
| POST | `/scan/product` | `retail.scanning.manage` | Decode, identify, verify, price, optionally update inventory |
| POST | `/scan/verify` | `retail.scanning.view` | Decode and verify product without stock movement |
| POST | `/scan/camera` | `retail.scanning.manage` | Register camera detection and identify product |
| POST | `/scan/self-checkout` | `retail.scanning.self-checkout` | Build self-checkout transaction from scans |
| GET | `/scan/history` | `retail.scanning.view` | Paginated scan event history |
| GET | `/scan/analytics` | `retail.scanning.reports` | Smart scanning KPIs, trends, top products, expiry alerts |

## Example Order Payload

```json
{
  "client_id": 1,
  "channel": "Online Store",
  "status": "Confirmed",
  "items": [
    {
      "product_id": 1,
      "title": "Retail item",
      "quantity": 2,
      "unit_price": 1500,
      "discount": 0,
      "tax_rate": 16
    }
  ]
}
```

## Extension Contracts

- Payments are captured through shared POS payment endpoints and payment methods.
- Accounting entries and ledgers remain in shared Finance/Accounting APIs.
- Procurement documents remain in shared ERP procurement APIs.
- Report exports resolve through the shared Reporting surface.

## Example Smart Scan Payload

```json
{
  "raw_value": "sku=SKU-100|batch_number=B-2026|expiry_date=2026-12-31",
  "input_type": "Scanner Device Input",
  "device_code": "REG-01-SCAN",
  "quantity": 1,
  "update_inventory": true
}
```

The response includes product details, current price, promotions, final price, inventory availability, compliance verification, fraud risk, and a POS-ready `cart_item`.
