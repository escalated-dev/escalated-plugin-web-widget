# TODO: Escalated Plugin - Web Widget

## Backend
- [ ] Widget embed script generation and serving endpoint
- [ ] Widget configuration API (appearance, behavior, fields)
- [ ] Contact form submission handler (creates tickets)
- [ ] Knowledge base article search API for widget
- [ ] Widget authentication (API key per widget instance)
- [ ] Rate limiting and spam protection (honeypot, reCAPTCHA)
- [ ] File upload support from widget contact form
- [ ] Visitor identification and tracking
- [ ] Widget analytics collection (views, interactions, submissions)
- [ ] Multi-language support for widget content

## Frontend (Admin Configuration)
- [ ] Widget configurator with live preview
- [ ] Color scheme and branding customization
- [ ] Widget position selector (bottom-right, bottom-left, custom)
- [ ] Contact form field builder (add/remove/reorder fields)
- [ ] KB article category filter for widget search
- [ ] Embed code generator with copy button
- [ ] Widget analytics dashboard (usage stats, submission rates)
- [ ] Custom CSS override editor
- [ ] Welcome message and greeting configuration

## Frontend (Embeddable Widget)
- [ ] Lightweight standalone widget bundle (no Vue dependency)
- [ ] Floating launcher button with unread badge
- [ ] Contact form view with validation
- [ ] KB search view with article previews
- [ ] Article detail view within widget
- [ ] Success/confirmation screen after submission
- [ ] Responsive design for mobile devices
- [ ] Accessibility compliance (WCAG 2.1 AA)
- [ ] Animation and transition effects
- [ ] Shadow DOM isolation to prevent CSS conflicts

## Integration
- [ ] Ticket creation from contact form submissions
- [ ] Pre-fill form fields from URL parameters
- [ ] Visitor page tracking for context
- [ ] Integration with live chat plugin (if installed)
- [ ] Custom field support in contact form

## Configuration
- [ ] Widget API key management
- [ ] Allowed domains whitelist
- [ ] Default form fields selection
- [ ] KB categories visible in widget
- [ ] Widget language and text customization
- [ ] Business hours display
