# Communication API

Authenticated endpoints are mounted under `/api/v1/shared/communication`.

| Method | Endpoint | Permission | Purpose |
| --- | --- | --- | --- |
| GET | `/channels` | `communication.view` | List channels visible to the current user |
| POST | `/channels` | `communication.create_channel` | Create group, department, branch, role, industry, announcement, or record channel |
| POST | `/channels/{channel}/read` | `communication.view` | Mark the channel read through a specific or latest message |
| GET | `/messages?channel_id=1` | `communication.view` | List channel message history with optional `q`, `before_id`, and `limit` |
| POST | `/messages` | `communication.send` | Send channel or direct message |
| PUT | `/messages/{message}` | `communication.send` | Edit an allowed message |
| DELETE | `/messages/{message}` | `communication.delete_own` | Delete for self or, with moderation permission, delete for everyone |
| POST | `/messages/{message}/reactions` | `communication.send` | Add a reaction |
| POST | `/messages/{message}/save` | `communication.view` | Save a message |
| DELETE | `/messages/{message}/save` | `communication.view` | Remove a saved message |
| POST | `/messages/{message}/pin` | `communication.manage_channel` | Pin a message |
| DELETE | `/messages/{message}/pin` | `communication.manage_channel` | Unpin a message |
| GET | `/announcements` | `communication.view` | List announcements visible to the current user |
| POST | `/announcements` | `communication.announcements.create` | Publish company, branch, department, or industry announcement |
| POST | `/announcements/{announcement}/acknowledge` | `communication.view` | Mark an announcement read or acknowledged |
| GET | `/notifications` | `communication.view` | List current user's notification center |
| POST | `/notifications` | `communication.manage` | Create notification through shared notification engine |
| GET | `/directory` | `communication.view` | Search employees in the active business |
| GET | `/search` | `communication.view` | Search conversations, messages, files, people, departments, and branches |
| GET | `/settings` | `communication.view` | Read business communication settings |
| PUT | `/settings` | `communication.settings` | Update business communication controls |
| POST | `/context` | `communication.create_channel` | Create or fetch a record discussion channel |

Message payloads support:

- `channel_id`
- `recipient_id` for direct messages
- `body`
- `message_type`
- `parent_id`
- `related_type`
- `related_id`
- `attachments[]`

Attachment metadata supports PDFs, spreadsheets, CSV, images, Word documents, and audio voice notes. `document_media_id` can reference the shared document system.
