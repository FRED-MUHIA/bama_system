# Communication Permission Matrix

| Permission | Purpose |
| --- | --- |
| `communication.view` | View accessible channels, messages, notifications, and announcements |
| `communication.send` | Send messages, replies, and reactions |
| `communication.create_group` | Create employee-managed group conversations when enabled |
| `communication.manage_group` | Manage group surfaces and membership workflows |
| `communication.create_channel` | Create scoped company, department, branch, team, role, industry, and record channels |
| `communication.manage_channel` | Pin messages and administer assigned conversations |
| `communication.upload` | Share approved files and voice-note attachments |
| `communication.delete_own` | Delete messages from the current user's view |
| `communication.moderate` | Edit or delete messages for moderation when allowed |
| `communication.announcements.create` | Publish company, branch, department, and industry announcements |
| `communication.announcements.manage` | Manage announcement workflows and acknowledgements |
| `communication.mass_mention` | Mention everyone or here |
| `communication.audit` | View communication audit history |
| `communication.settings` | Configure communication controls for the business |
| `communication.manage` | Legacy broad management permission |
| `communication.admin` | Cross-channel administrative access |
| `communication.announce` | Legacy announcement publishing permission |
| `communication.reports` | View communication analytics and audit reports |

## Matrix Examples

| Role | Allowed |
| --- | --- |
| Cashier | Message store manager, cashier role channel, assigned branch channel |
| Warehouse Staff | Message warehouse manager, warehouse channel, procurement channel when granted |
| Trainer | Message members and gym manager through shared channels |
| Accountant | Finance department channel, invoice/payment task discussions |
| Manager | Branch, department, role, team, and announcement channels within assigned scope |

Rules are stored in `communication_permissions` and enforced by `CommunicationService`.
