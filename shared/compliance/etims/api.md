# ETIMS API

Authenticated routes are mounted under `/api/v1/shared/compliance/etims`.

| Method | Endpoint | Permission | Purpose |
| --- | --- | --- | --- |
| GET | `/dashboard?industry=retail` | `etims.view` | Return submitted, pending, failed, credit note, debit note, and compliance rate KPIs |
| GET | `/submissions?industry=retail&status=Validated` | `etims.view` | List queue records for audit and support |
| POST | `/submissions/retry` | `etims.retry` | Retry pending, offline queued, and failed submissions |

Submission records store:

- ETIMS invoice number
- ETIMS receipt number
- QR code payload
- Submission status
- Submission timestamp
- Validation timestamp
- Error messages
- Source model reference
- Full fiscal payload
