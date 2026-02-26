import { defineEscalatedPlugin } from '@escalated-dev/escalated';
import WebWidgetConfigurator from './components/WebWidgetConfigurator.vue';

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
                icon: 'code',
                category: 'channels',
            },
        ],
        menuItems: [
            {
                id: 'web-widget-config',
                label: 'Web Widget',
                icon: 'code-bracket',
                route: '/settings/web-widget',
            },
        ],
        pageComponents: {
            'web-widget-config': WebWidgetConfigurator,
        },
    },

    hooks: {
        'ticket.created': (ticket) => {
            // Track tickets created via web widget
        },
    },

    setup(context) {
        context.provide('webWidget', {
            // Web widget service will be provided here
        });
    },
});
