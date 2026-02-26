import { defineEscalatedPlugin } from '@escalated-dev/escalated';
import WebWidgetConfigurator from './components/WebWidgetConfigurator.vue';
import WidgetPreview from './components/WidgetPreview.vue';

export default defineEscalatedPlugin({
    name: 'Web Widget',
    slug: 'web-widget',
    version: '0.1.0',
    description: 'Embeddable JavaScript widget for websites with contact form and KB search',

    extensions: {
        settingsPanels: [
            {
                id: 'web-widget-settings',
                title: 'Web Widget',
                component: WebWidgetConfigurator,
                icon: 'code-bracket',
                category: 'channels',
            },
        ],
        menuItems: [
            {
                id: 'web-widget-config',
                label: 'Web Widget',
                icon: 'code-bracket',
                route: '/admin/web-widget',
                parent: 'channels',
                order: 30,
                capability: 'manage_settings',
            },
        ],
        pageComponents: {
            'admin.web-widget': WebWidgetConfigurator,
            'web-widget.preview': WidgetPreview,
        },
    },

    hooks: {
        /**
         * Track tickets created via web widget for analytics.
         */
        'ticket.created': (ticket, context) => {
            if (ticket?.source !== 'web-widget' && ticket?.channel !== 'web-widget') {
                return;
            }

            const service = context?.$escalated?.inject?.('webWidget');
            if (!service) return;

            service.trackWidgetTicket(ticket);
        },

        /**
         * Register web widget as a ticket channel.
         */
        'ticket.channels': (channels) => {
            return [
                ...channels,
                {
                    id: 'web-widget',
                    name: 'Web Widget',
                    icon: 'code-bracket',
                    description: 'Tickets submitted via the embeddable website widget',
                },
            ];
        },

        /**
         * Register web widget as a ticket source.
         */
        'ticket.sources': (sources) => {
            return [
                ...sources,
                {
                    id: 'web-widget',
                    name: 'Web Widget',
                    icon: 'code-bracket',
                },
            ];
        },

        /**
         * Add web widget entry to admin settings navigation.
         */
        'admin.settings.nav': (items) => {
            return [
                ...items,
                {
                    id: 'web-widget-config',
                    label: 'Web Widget',
                    icon: 'code-bracket',
                    section: 'channels',
                    order: 30,
                },
            ];
        },
    },

    setup(context) {
        const { reactive, ref } = context.vue || {};
        const _reactive = reactive || ((o) => o);
        const _ref = ref || ((v) => ({ value: v }));

        // ------------------------------------------------------------------
        // Reactive state
        // ------------------------------------------------------------------
        const state = _reactive({
            settings: {
                api_key: '',
                colors: {
                    primary: '#6366f1',
                    background: '#ffffff',
                    text: '#1f2937',
                    launcher: '#6366f1',
                },
                position: 'bottom-right',
                greeting: 'Hi there! How can we help you?',
                form_fields: [
                    { name: 'name', label: 'Name', type: 'text', required: true },
                    { name: 'email', label: 'Email', type: 'email', required: true },
                    { name: 'subject', label: 'Subject', type: 'text', required: false },
                    { name: 'message', label: 'Message', type: 'textarea', required: true },
                ],
                allowed_domains: [],
                kb_enabled: true,
                kb_categories: [],
                rate_limit_per_hour: 60,
            },
            kb_categories_available: [],
            loading: false,
            widgetTicketCount: 0,
        });

        const saving = _ref(false);

        // ------------------------------------------------------------------
        // API helpers
        // ------------------------------------------------------------------
        const apiBase = () => {
            if (context.route) {
                return context.route('plugins.web-widget.api');
            }
            return '/api/plugins/web-widget';
        };

        async function apiRequest(path, options = {}) {
            const url = `${apiBase()}${path}`;
            const headers = {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {}),
            };

            if (options.body && typeof options.body === 'object') {
                headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(options.body);
            }

            const response = await fetch(url, { ...options, headers });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.message || `API request failed: ${response.status}`);
            }

            return response.json();
        }

        // ------------------------------------------------------------------
        // Settings operations
        // ------------------------------------------------------------------

        async function fetchSettings() {
            state.loading = true;
            try {
                const data = await apiRequest('/settings');
                state.settings = {
                    ...state.settings,
                    ...data,
                    colors: { ...state.settings.colors, ...(data.colors || {}) },
                };
            } catch (err) {
                console.error('[web-widget] Failed to fetch settings:', err);
            } finally {
                state.loading = false;
            }
        }

        async function saveSettings(settings) {
            saving.value = true;
            try {
                const data = await apiRequest('/settings', {
                    method: 'POST',
                    body: settings,
                });
                state.settings = {
                    ...state.settings,
                    ...data,
                    colors: { ...state.settings.colors, ...(data.colors || {}) },
                };
                return data;
            } catch (err) {
                console.error('[web-widget] Failed to save settings:', err);
                throw err;
            } finally {
                saving.value = false;
            }
        }

        // ------------------------------------------------------------------
        // API key management
        // ------------------------------------------------------------------

        async function regenerateApiKey() {
            try {
                const data = await apiRequest('/regenerate-api-key', {
                    method: 'POST',
                });
                state.settings.api_key = data.api_key || '';
                return data.api_key;
            } catch (err) {
                console.error('[web-widget] Failed to regenerate API key:', err);
                throw err;
            }
        }

        // ------------------------------------------------------------------
        // KB categories
        // ------------------------------------------------------------------

        async function fetchKbCategories() {
            try {
                const data = await apiRequest('/kb-categories');
                state.kb_categories_available = Array.isArray(data)
                    ? data
                    : (data.categories || []);
            } catch (err) {
                console.error('[web-widget] Failed to fetch KB categories:', err);
            }
        }

        // ------------------------------------------------------------------
        // Embed code generation (client-side mirror)
        // ------------------------------------------------------------------

        function generateEmbedCode(baseUrl) {
            const url = baseUrl
                ? `${baseUrl}/plugins/web-widget/widget.js`
                : '/plugins/web-widget/widget.js';
            const apiKey = state.settings.api_key || '';

            return [
                '<script>',
                '  (function(w,d,s,o){',
                '    var f=d.getElementsByTagName(s)[0],j=d.createElement(s);',
                '    j.async=true;j.src=o.url;j.dataset.apiKey=o.apiKey;',
                '    w.EscalatedWidgetConfig=o;f.parentNode.insertBefore(j,f);',
                '  })(window,document,"script",{',
                `    url: "${url}",`,
                `    apiKey: "${apiKey}"`,
                '  });',
                '</script>',
            ].join('\n');
        }

        // ------------------------------------------------------------------
        // Widget ticket tracking
        // ------------------------------------------------------------------

        function trackWidgetTicket(ticket) {
            state.widgetTicketCount++;

            if (function_exists?.('escalated_log')) {
                console.info('[web-widget] Ticket created via widget:', ticket?.id);
            } else {
                console.info('[web-widget] Ticket created via widget:', ticket?.id);
            }
        }

        // ------------------------------------------------------------------
        // Form field helpers
        // ------------------------------------------------------------------

        function addFormField(field = {}) {
            const newField = {
                name: field.name || `custom_${Date.now()}`,
                label: field.label || 'New Field',
                type: field.type || 'text',
                required: field.required || false,
            };

            state.settings.form_fields = [...state.settings.form_fields, newField];
            return newField;
        }

        function removeFormField(index) {
            const fields = [...state.settings.form_fields];
            fields.splice(index, 1);
            state.settings.form_fields = fields;
        }

        function reorderFormFields(fromIndex, toIndex) {
            const fields = [...state.settings.form_fields];
            const [moved] = fields.splice(fromIndex, 1);
            fields.splice(toIndex, 0, moved);
            state.settings.form_fields = fields;
        }

        // ------------------------------------------------------------------
        // Domain management
        // ------------------------------------------------------------------

        function addDomain(domain) {
            if (!domain || state.settings.allowed_domains.includes(domain)) {
                return;
            }
            state.settings.allowed_domains = [...state.settings.allowed_domains, domain];
        }

        function removeDomain(index) {
            const domains = [...state.settings.allowed_domains];
            domains.splice(index, 1);
            state.settings.allowed_domains = domains;
        }

        // ------------------------------------------------------------------
        // Provide the web widget service
        // ------------------------------------------------------------------
        context.provide('webWidget', {
            state,
            saving,
            // Settings
            fetchSettings,
            saveSettings,
            // API key
            regenerateApiKey,
            // KB
            fetchKbCategories,
            // Embed
            generateEmbedCode,
            // Tracking
            trackWidgetTicket,
            // Fields
            addFormField,
            removeFormField,
            reorderFormFields,
            // Domains
            addDomain,
            removeDomain,
        });
    },
});
