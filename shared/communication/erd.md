# Communication ERD

```text
communication_channels
  -> tenant_id, business_id
  -> owner_id users.id
  -> department_id departments.id
  -> branch_id branches.id
  -> team_id teams.id
  -> project_id projects.id
  -> record_type, record_id

channel_members
  -> communication_channel_id communication_channels.id
  -> user_id users.id

messages
  -> communication_channel_id communication_channels.id
  -> sender_id users.id
  -> parent_id messages.id
  -> related_type, related_id

message_reactions
  -> message_id messages.id
  -> user_id users.id

message_attachments
  -> message_id messages.id
  -> uploaded_by users.id
  -> document_media_id shared document media reference

announcements
  -> communication_channel_id communication_channels.id
  -> author_id users.id
  -> branch_id branches.id
  -> department_id departments.id

notifications
  -> user_id users.id
  -> notifiable_type, notifiable_id

notification_preferences
  -> user_id users.id

mentions
  -> message_id messages.id
  -> announcement_id announcements.id
  -> mentioned_type, mentioned_id

communication_permissions
  -> role_slug
  -> target_type, target_id, target_role_slug

communication_audit_logs
  -> user_id users.id
  -> auditable_type, auditable_id
```
