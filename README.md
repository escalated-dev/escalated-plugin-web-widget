# Escalated Plugin: Web Widget

Embeddable JavaScript widget that adds a customer support interface to any website, featuring a contact form for ticket submission and knowledge base article search for self-service resolution.

## Features (Planned)
- Lightweight embeddable JavaScript widget
- Contact form with customizable fields
- Knowledge base article search and display
- Visual configurator with live preview
- Color scheme and branding customization
- Position and behavior settings
- Embed code generator
- Spam protection (honeypot, reCAPTCHA)
- File upload support
- Mobile responsive design
- Shadow DOM isolation (no CSS conflicts)
- Widget usage analytics

## Installation

### Via ZIP Upload
1. Download the latest release ZIP from this repository
2. In Escalated admin, go to **Settings > Plugins**
3. Click **Upload Plugin** and select the ZIP file
4. Activate the plugin from the plugins list

### Via Composer
```bash
composer require escalated-dev/escalated-plugin-web-widget
```
Then activate the plugin from **Settings > Plugins** in Escalated admin.

### Requirements
- Escalated >= 0.6.0

## Status
This plugin is in early development. See TODO.md for implementation status.
