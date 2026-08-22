# Communication WebSocket Layer

The module provides Laravel broadcast events:

- `Shared\Communication\Events\MessagePosted`
- `Shared\Communication\Events\TypingIndicatorUpdated`
- `Shared\Communication\Events\PresenceStatusUpdated`

Channels:

```text
private-tenant.{tenant_id}.communication.channel.{channel_id}
private-tenant.{tenant_id}.communication.presence
```

These events provide live messaging, typing indicators, and presence status when the deployment enables Laravel broadcasting with a WebSocket driver.
