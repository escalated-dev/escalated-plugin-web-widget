<?php
/** Web Widget Plugin for Escalated -- thin entry point. */
if (!defined('ESCALATED_LOADED')) {
    exit('Direct access not allowed.');
}

require_once __DIR__ . '/Support/Config.php';
require_once __DIR__ . '/Services/EmbedGenerator.php';
require_once __DIR__ . '/Services/TicketCreator.php';
require_once __DIR__ . '/Handlers/WebhookHandler.php';

use Escalated\Plugins\WebWidget\Support\Config;
use Escalated\Plugins\WebWidget\Services\EmbedGenerator;
use Escalated\Plugins\WebWidget\Services\TicketCreator;
use Escalated\Plugins\WebWidget\Handlers\WebhookHandler;

$wConfig  = new Config();
$wEmbed   = new EmbedGenerator($wConfig);
$wTickets = new TicketCreator($wConfig);
$wHook    = new WebhookHandler($wConfig, $wTickets);

/* -- Event: log widget ticket creation -------------------------------- */
escalated_add_action('web_widget.ticket_created', function ($data) {
    if (function_exists('escalated_log')) {
        $id     = $data['ticket_id'] ?? '';
        $origin = $data['origin'] ?? 'widget';
        escalated_log('web-widget', "Ticket created via web widget: #{$id} from {$origin}");
    }
}, 10);

/* -- Filter: register web-widget as a ticket channel ------------------ */
escalated_add_filter('ticket.channels', function (array $ch) {
    $ch[] = ['id' => 'web-widget', 'name' => 'Web Widget', 'icon' => 'code-bracket',
             'description' => 'Tickets submitted via the embeddable website widget'];
    return $ch;
}, 10);

/* -- Filter: register web-widget as a ticket source ------------------- */
escalated_add_filter('ticket.sources', function (array $s) {
    $s[] = ['id' => 'web-widget', 'name' => 'Web Widget', 'icon' => 'code-bracket'];
    return $s;
}, 10);

/* -- Cron: clean up stale rate-limit files ---------------------------- */
escalated_add_action('escalated.cron.hourly', [$wConfig, 'cleanupRateLimits'], 10);

/* -- Admin page & menu ------------------------------------------------ */
escalated_register_page('admin/web-widget', [
    'title' => 'Web Widget', 'component' => 'WebWidgetConfigurator',
    'capability' => 'manage_settings', 'props' => ['pluginSlug' => Config::SLUG],
]);

escalated_register_menu_item([
    'id' => 'web-widget-config', 'label' => 'Web Widget', 'icon' => 'code-bracket',
    'route' => '/admin/web-widget', 'parent' => 'channels',
    'order' => 30, 'capability' => 'manage_settings',
]);

escalated_add_page_component('admin.settings', 'channels', [
    'component' => 'WebWidgetConfigurator',
    'props'     => ['pluginSlug' => Config::SLUG],
    'order'     => 20,
]);

/* -- Activation ------------------------------------------------------- */
escalated_add_action('escalated_plugin_activated_web-widget', function () use ($wConfig) {
    $wConfig->activate();
    if (function_exists('escalated_log')) {
        escalated_log('web-widget', 'Plugin activated, API key generated.');
    }
}, 10);

/* -- Deactivation ----------------------------------------------------- */
escalated_add_action('escalated_plugin_deactivated_web-widget', function () use ($wConfig) {
    $wConfig->deactivate();
}, 10);
