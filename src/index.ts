import { definePlugin } from '@escalated-dev/plugin-sdk'
import type { PluginContext } from '@escalated-dev/plugin-sdk'

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

interface WidgetSettings {
    api_key: string
    title: string
    primary_color: string
    position: 'bottom-right' | 'bottom-left'
    greeting: string
    show_avatar: boolean
    allowed_origins: string[]      // CORS whitelist
    custom_fields: Array<{
        key: string; label: string; type: string; required: boolean
    }>
    rate_limit: { max_tickets: number; window_minutes: number }
    default_department_id?: string | number
    default_priority?: string
}

interface WidgetSubmission {
    name: string
    email: string
    subject: string
    message: string
    custom_fields?: Record<string, unknown>
    origin?: string
    metadata?: Record<string, unknown>
}

// ---------------------------------------------------------------------------
// Rate limiting (stored in plugin store)
// ---------------------------------------------------------------------------

async function checkRateLimit(
    ctx: PluginContext,
    ip: string,
    settings: WidgetSettings,
): Promise<{ allowed: boolean; remaining: number }> {
    const { max_tickets, window_minutes } = settings.rate_limit
    const windowMs = window_minutes * 60_000
    const key = `rl_${ip}`

    const record = await ctx.store.get('rate_limits', key) as
        null | { count: number; window_start: string }

    if (!record || Date.now() - new Date(record.window_start).getTime() > windowMs) {
        await ctx.store.set('rate_limits', key, { count: 1, window_start: new Date().toISOString() })
        return { allowed: true, remaining: max_tickets - 1 }
    }

    if (record.count >= max_tickets) {
        return { allowed: false, remaining: 0 }
    }

    await ctx.store.set('rate_limits', key, { ...record, count: record.count + 1 })
    return { allowed: true, remaining: max_tickets - record.count - 1 }
}

async function getSettings(ctx: PluginContext): Promise<WidgetSettings> {
    const raw = await ctx.config.all()
    return {
        api_key: (raw.api_key as string) ?? '',
        title: (raw.title as string) ?? 'Support',
        primary_color: (raw.primary_color as string) ?? '#4f46e5',
        position: ((raw.position as string) ?? 'bottom-right') as 'bottom-right' | 'bottom-left',
        greeting: (raw.greeting as string) ?? 'How can we help you today?',
        show_avatar: raw.show_avatar !== false,
        allowed_origins: (raw.allowed_origins as string[] | undefined) ?? [],
        custom_fields: (raw.custom_fields as WidgetSettings['custom_fields'] | undefined) ?? [],
        rate_limit: (raw.rate_limit as WidgetSettings['rate_limit'] | undefined) ?? { max_tickets: 5, window_minutes: 60 },
        default_department_id: raw.default_department_id as string | number | undefined,
        default_priority: (raw.default_priority as string | undefined) ?? 'normal',
    }
}

// ---------------------------------------------------------------------------
// Plugin definition
// ---------------------------------------------------------------------------

export default definePlugin({
    name: 'web-widget',
    version: '0.1.0',
    description: 'Embeddable website support widget for ticket submission with configurable branding, custom fields, and rate limiting',

    config: [
        { name: 'api_key', label: 'Widget API Key', type: 'password',
            help: 'Generated on activation. Include in the widget embed script.' },
        { name: 'title', label: 'Widget Title', type: 'text', default: 'Support' },
        { name: 'primary_color', label: 'Primary Color', type: 'text', default: '#4f46e5' },
        { name: 'position', label: 'Widget Position', type: 'select',
            options: [
                { value: 'bottom-right', label: 'Bottom Right' },
                { value: 'bottom-left',  label: 'Bottom Left' },
            ],
            default: 'bottom-right',
        },
        { name: 'greeting', label: 'Greeting Message', type: 'textarea',
            default: 'How can we help you today?' },
        { name: 'show_avatar', label: 'Show Agent Avatar', type: 'boolean', default: true },
        { name: 'allowed_origins', label: 'Allowed Origins (CORS)', type: 'json', default: ['*'],
            help: 'Array of allowed origins, e.g. ["https://yoursite.com"]. Use ["*"] to allow all.' },
        { name: 'custom_fields', label: 'Custom Fields', type: 'json', default: [] },
        { name: 'rate_limit', label: 'Rate Limit', type: 'json',
            default: { max_tickets: 5, window_minutes: 60 } },
        { name: 'default_department_id', label: 'Default Department ID', type: 'text' },
        { name: 'default_priority', label: 'Default Ticket Priority', type: 'select',
            options: [
                { value: 'low',    label: 'Low' },
                { value: 'normal', label: 'Normal' },
                { value: 'high',   label: 'High' },
            ],
            default: 'normal',
        },
    ],

    onActivate: async (ctx) => {
        // Generate API key if not set
        const cfg = await ctx.config.all()
        if (!cfg.api_key) {
            const key = `ww_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 12)}`
            await ctx.config.set({ api_key: key })
            ctx.log.info('[web-widget] API key generated')
        }
        ctx.log.info('[web-widget] Plugin activated')
    },

    onDeactivate: async (ctx) => {
        ctx.log.info('[web-widget] Plugin deactivated')
    },

    // -----------------------------------------------------------------------
    // Action hooks
    // -----------------------------------------------------------------------

    actions: {
        'web_widget.ticket_created': async (event, ctx) => {
            const { ticket_id, origin } = event as { ticket_id: string | number; origin?: string }
            ctx.log.info('[web-widget] Ticket created via widget', { ticket_id, origin })
        },
    },

    // -----------------------------------------------------------------------
    // Filter hooks
    // -----------------------------------------------------------------------

    filters: {
        'ticket.channels': {
            priority: 10,
            handler: (channels) => [
                ...(channels as unknown[]),
                {
                    id: 'web-widget',
                    name: 'Web Widget',
                    icon: 'code-bracket',
                    description: 'Tickets submitted via the embeddable website widget',
                },
            ],
        },

        'ticket.sources': {
            priority: 10,
            handler: (sources) => [
                ...(sources as unknown[]),
                { id: 'web-widget', name: 'Web Widget', icon: 'code-bracket' },
            ],
        },
    },

    // -----------------------------------------------------------------------
    // Pages & components
    // -----------------------------------------------------------------------

    pages: [
        {
            route: 'admin/web-widget',
            component: 'WebWidgetConfigurator',
            layout: 'admin',
            capability: 'manage_settings',
            menu: { label: 'Web Widget', section: 'admin', position: 30, icon: 'code-bracket' },
        },
    ],

    components: [
        {
            page: 'admin.settings',
            slot: 'channels',
            component: 'WebWidgetConfigurator',
            props: { pluginSlug: 'web-widget' },
            order: 20,
            capability: 'manage_settings',
        },
    ],

    // -----------------------------------------------------------------------
    // Endpoints
    // -----------------------------------------------------------------------

    endpoints: {
        'GET /config': {
            // Public — fetched by the embeddable widget script
            handler: async (ctx, req) => {
                const apiKey = req.query.key
                const settings = await getSettings(ctx)

                if (!apiKey || apiKey !== settings.api_key) {
                    return { error: 'Invalid API key' }
                }

                // Return only public config (no secrets)
                return {
                    title: settings.title,
                    primary_color: settings.primary_color,
                    position: settings.position,
                    greeting: settings.greeting,
                    show_avatar: settings.show_avatar,
                    custom_fields: settings.custom_fields,
                }
            },
        },
        'POST /submit': {
            // Public — widget submits tickets here
            handler: async (ctx, req) => {
                const apiKey = req.headers['x-widget-key']
                const settings = await getSettings(ctx)

                if (apiKey !== settings.api_key) {
                    return { success: false, error: 'Unauthorized' }
                }

                const clientIp = req.headers['x-forwarded-for'] ?? 'unknown'
                const rateCheck = await checkRateLimit(ctx, String(clientIp), settings)
                if (!rateCheck.allowed) {
                    return { success: false, error: 'Rate limit exceeded. Please try again later.' }
                }

                const body = req.body as WidgetSubmission
                const { name, email, subject, message, custom_fields, origin } = body

                // Find or create contact
                let contact = await ctx.contacts.findByEmail(email)
                if (!contact) {
                    contact = await ctx.contacts.create({ name, email })
                }

                const ticket = await ctx.tickets.create({
                    title: subject,
                    status: 'open',
                    priority: settings.default_priority ?? 'normal',
                    requester_id: contact.id,
                    requester_type: 'contact',
                    department_id: settings.default_department_id ?? null,
                    metadata: {
                        source: 'web-widget',
                        origin: origin ?? '',
                        custom_fields: custom_fields ?? {},
                        message,
                    },
                })

                await ctx.emit('web_widget.ticket_created', { ticket_id: ticket.id, origin })

                return { success: true, ticket_id: ticket.id }
            },
        },
        'GET /embed.js': {
            // Public — returns the embeddable JavaScript snippet
            handler: async (ctx, req) => {
                const settings = await getSettings(ctx)
                // Return the embed snippet (host URL injected by bridge)
                return {
                    snippet: `<!-- Escalated Web Widget -->\n<script src="/plugins/web-widget/widget.js?key=${settings.api_key}" async></script>`,
                }
            },
        },
        'GET /settings': {
            capability: 'manage_settings',
            handler: async (ctx) => ctx.config.all(),
        },
        'POST /settings': {
            capability: 'manage_settings',
            handler: async (ctx, req) => {
                await ctx.config.set(req.body as Record<string, unknown>)
                return { success: true }
            },
        },
    },

    // -----------------------------------------------------------------------
    // Cron — clean up stale rate limit records hourly
    // -----------------------------------------------------------------------

    cron: {
        'every:1h': async (ctx) => {
            const settings = await getSettings(ctx)
            const windowMs = settings.rate_limit.window_minutes * 60_000
            const threshold = new Date(Date.now() - windowMs).toISOString()

            const stale = await ctx.store.query('rate_limits', {
                window_start: { $lt: threshold } as unknown as string,
            })

            for (const raw of stale) {
                const record = raw as unknown as { key: string }
                await ctx.store.delete('rate_limits', record.key)
            }

            if (stale.length > 0) {
                ctx.log.debug(`[web-widget] Cleaned ${stale.length} stale rate limit records`)
            }
        },
    },
})
