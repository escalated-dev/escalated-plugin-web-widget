# Escalated Plugin: Web Widget

**Website:** [escalated.dev](https://escalated.dev)

Embeddable website support widget that allows visitors to submit tickets directly from your customer-facing website. Includes configurable branding, custom fields, rate limiting, and CORS origin restrictions.

## Features

- API key auto-generated on activation for secure widget requests
- Configurable branding: title, primary color, position, greeting message
- Custom fields support for gathering additional information
- Per-IP rate limiting with configurable window and max tickets
- CORS origin allowlist for restricting widget access
- Automatic contact creation or lookup by email
- Embed script snippet generation
- Stale rate limit record cleanup via hourly cron
- Admin configurator UI injected into Settings > Channels

## Configuration

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `api_key` | password | No | Widget API key, auto-generated on activation. Include in the embed script. |
| `title` | text | No | Widget title displayed to visitors. Defaults to `Support`. |
| `primary_color` | text | No | Primary brand color (hex). Defaults to `#4f46e5`. |
| `position` | select | No | Widget position: `bottom-right` or `bottom-left`. Defaults to `bottom-right`. |
| `greeting` | textarea | No | Greeting message displayed in the widget. |
| `show_avatar` | boolean | No | Show agent avatar in the widget. Defaults to `true`. |
| `allowed_origins` | json | No | CORS allowlist array. Use `["*"]` to allow all origins. |
| `custom_fields` | json | No | Array of `{ key, label, type, required }` custom field definitions. |
| `rate_limit` | json | No | Rate limit config: `{ max_tickets, window_minutes }`. Defaults to 5 tickets per 60 minutes. |
| `default_department_id` | text | No | Default department ID for widget-submitted tickets. |
| `default_priority` | select | No | Default ticket priority: `low`, `normal`, or `high`. Defaults to `normal`. |

## Admin Pages

- **admin/web-widget** — Configure widget appearance, custom fields, rate limits, and get the embed code.

## Hooks

### Actions
- `web_widget.ticket_created` — Fires when a ticket is submitted via the widget.

### Filters
- `ticket.channels` — Registers Web Widget as an available ticket channel.
- `ticket.sources` — Registers Web Widget as a ticket source.

### Cron
- `every:1h` — Cleans up expired rate limit records.

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/config` | Public endpoint returning widget display config (requires API key query param). |
| POST | `/submit` | Public endpoint for ticket submission (requires X-Widget-Key header). |
| GET | `/embed.js` | Returns the embeddable JavaScript snippet. |
| GET | `/settings` | Get plugin configuration. |
| POST | `/settings` | Save plugin configuration. |

## Installation

```bash
npm install @escalated-dev/plugin-web-widget
```

## License

MIT
