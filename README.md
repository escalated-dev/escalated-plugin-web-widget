# @escalated-dev/plugin-web-widget

Embeddable website support widget for ticket submission. Visitors on your customer-facing website can submit tickets directly via a configurable chat-style widget.

## Features

- API key generated on activation — required for all widget requests
- Configurable branding: title, primary color, position, greeting
- Custom fields support
- Rate limiting per IP with configurable window and max tickets
- CORS origin allowlist
- Automatic contact creation/lookup by email
- Stale rate limit record cleanup (hourly cron)
- Admin configurator UI injected into Settings > Channels

## Hooks

| Type | Hook | Description |
|------|------|-------------|
| Action | `web_widget.ticket_created` | Logs ticket creation via widget |
| Filter | `ticket.channels` | Registers Web Widget as a ticket source channel |
| Filter | `ticket.sources` | Registers Web Widget as a ticket source |
| Cron | `every:1h` | Cleans up expired rate limit records |

## Endpoints

| Method | Path | Capability |
|--------|------|-----------|
| GET | `/config` | public (API key required) |
| POST | `/submit` | public (X-Widget-Key required) |
| GET | `/embed.js` | public |
| GET/POST | `/settings` | `manage_settings` |
