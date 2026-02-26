<?php

/**
 * Web Widget Plugin for Escalated
 *
 * Provides an embeddable JavaScript widget for websites that includes a
 * contact form (creates tickets) and knowledge base article search.
 * The widget is secured via API key, with domain whitelisting and
 * rate limiting on form submissions.
 *
 * Settings are persisted as a JSON file in the plugin's config directory.
 * Rate limit state is tracked in a separate JSON file per hour window.
 */

// Prevent direct access
if (!defined('ESCALATED_LOADED')) {
    exit('Direct access not allowed.');
}

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

define('ESC_WIDGET_VERSION', '0.1.0');
define('ESC_WIDGET_SLUG', 'web-widget');
define('ESC_WIDGET_CONFIG_DIR', __DIR__ . '/config');
define('ESC_WIDGET_CONFIG_FILE', ESC_WIDGET_CONFIG_DIR . '/settings.json');
define('ESC_WIDGET_RATE_LIMIT_DIR', ESC_WIDGET_CONFIG_DIR . '/rate_limits');

// ---------------------------------------------------------------------------
// Default configuration
// ---------------------------------------------------------------------------

/**
 * Return the default settings structure.
 *
 * Settings:
 *   api_key              - Secret key used to authenticate embed script requests
 *   colors               - Widget colour scheme { primary, background, text, launcher }
 *   position             - Widget launcher position ('bottom-right' or 'bottom-left')
 *   greeting             - Greeting message shown in the widget header
 *   form_fields          - Ordered array of form fields
 *   allowed_domains      - Array of domains allowed to embed the widget
 *   kb_enabled           - Whether KB search is enabled in the widget
 *   kb_categories        - Array of KB category IDs searchable from the widget
 *   rate_limit_per_hour  - Maximum form submissions per IP per hour
 */
function esc_widget_default_settings(): array
{
    return [
        'api_key'             => '',
        'colors'              => [
            'primary'    => '#6366f1',
            'background' => '#ffffff',
            'text'       => '#1f2937',
            'launcher'   => '#6366f1',
        ],
        'position'            => 'bottom-right',
        'greeting'            => 'Hi there! How can we help you?',
        'form_fields'         => [
            ['name' => 'name',    'label' => 'Name',    'type' => 'text',     'required' => true],
            ['name' => 'email',   'label' => 'Email',   'type' => 'email',    'required' => true],
            ['name' => 'subject', 'label' => 'Subject', 'type' => 'text',     'required' => false],
            ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => true],
        ],
        'allowed_domains'     => [],
        'kb_enabled'          => true,
        'kb_categories'       => [],
        'rate_limit_per_hour' => 60,
    ];
}

// ---------------------------------------------------------------------------
// Configuration storage helpers
// ---------------------------------------------------------------------------

/**
 * Read the current widget settings.
 */
function esc_widget_get_settings(): array
{
    if (!file_exists(ESC_WIDGET_CONFIG_FILE)) {
        return esc_widget_default_settings();
    }

    $json = file_get_contents(ESC_WIDGET_CONFIG_FILE);
    $data = json_decode($json, true);

    if (!is_array($data)) {
        return esc_widget_default_settings();
    }

    return array_replace_recursive(esc_widget_default_settings(), $data);
}

/**
 * Persist widget settings.
 */
function esc_widget_save_settings(array $settings): bool
{
    if (!is_dir(ESC_WIDGET_CONFIG_DIR)) {
        mkdir(ESC_WIDGET_CONFIG_DIR, 0755, true);
    }

    $settings = array_replace_recursive(esc_widget_default_settings(), $settings);
    $json     = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    return file_put_contents(ESC_WIDGET_CONFIG_FILE, $json, LOCK_EX) !== false;
}

// ---------------------------------------------------------------------------
// API key generation and validation
// ---------------------------------------------------------------------------

/**
 * Generate a new API key for the widget.
 *
 * The key is a 32-character hex string prefixed with 'esc_wk_'.
 *
 * @return string The newly generated API key.
 */
function esc_widget_generate_api_key(): string
{
    return 'esc_wk_' . bin2hex(random_bytes(16));
}

/**
 * Validate a given API key against the stored key.
 *
 * @param  string $key  The key to validate.
 * @return bool         True if the key matches the configured key.
 */
function esc_widget_validate_api_key(string $key): bool
{
    if (empty($key)) {
        return false;
    }

    $settings = esc_widget_get_settings();
    $stored   = $settings['api_key'] ?? '';

    if (empty($stored)) {
        return false;
    }

    return hash_equals($stored, $key);
}

// ---------------------------------------------------------------------------
// Domain validation
// ---------------------------------------------------------------------------

/**
 * Validate whether a request origin is allowed.
 *
 * If no allowed domains are configured, all origins are accepted.
 *
 * @param  string $origin  The Origin header value (e.g. 'https://example.com').
 * @return bool            True if the domain is allowed.
 */
function esc_widget_validate_domain(string $origin): bool
{
    $settings = esc_widget_get_settings();
    $allowed  = $settings['allowed_domains'] ?? [];

    // If no domains are configured, allow all
    if (empty($allowed)) {
        return true;
    }

    // Parse the origin to extract the hostname
    $parsed = parse_url($origin);
    $host   = $parsed['host'] ?? '';

    if (empty($host)) {
        // Try treating the entire origin as a hostname
        $host = strtolower(trim($origin));
    } else {
        $host = strtolower($host);
    }

    foreach ($allowed as $domain) {
        $domain = strtolower(trim($domain));

        if ($domain === '') {
            continue;
        }

        // Exact match
        if ($host === $domain) {
            return true;
        }

        // Wildcard subdomain match: *.example.com
        if (strpos($domain, '*.') === 0) {
            $baseDomain = substr($domain, 2);
            if ($host === $baseDomain || substr($host, -strlen('.' . $baseDomain)) === '.' . $baseDomain) {
                return true;
            }
        }

        // Allow match with or without www prefix
        if ($host === 'www.' . $domain || 'www.' . $host === $domain) {
            return true;
        }
    }

    return false;
}

// ---------------------------------------------------------------------------
// Rate limiting
// ---------------------------------------------------------------------------

/**
 * Get the current rate limit count for an IP address in the current hour.
 *
 * Rate limit data is stored in hourly JSON files that are automatically
 * cleaned up.
 *
 * @param  string $ip  The IP address.
 * @return int         Number of submissions in the current hour window.
 */
function esc_widget_get_rate_count(string $ip): int
{
    $hourKey = date('Y-m-d-H');
    $file    = ESC_WIDGET_RATE_LIMIT_DIR . '/' . $hourKey . '.json';

    if (!file_exists($file)) {
        return 0;
    }

    $data = json_decode(file_get_contents($file), true);

    if (!is_array($data)) {
        return 0;
    }

    $ipKey = md5($ip);

    return (int) ($data[$ipKey] ?? 0);
}

/**
 * Increment the rate limit counter for an IP address.
 *
 * @param  string $ip  The IP address.
 * @return int         The new count after incrementing.
 */
function esc_widget_increment_rate_count(string $ip): int
{
    if (!is_dir(ESC_WIDGET_RATE_LIMIT_DIR)) {
        mkdir(ESC_WIDGET_RATE_LIMIT_DIR, 0755, true);
    }

    $hourKey = date('Y-m-d-H');
    $file    = ESC_WIDGET_RATE_LIMIT_DIR . '/' . $hourKey . '.json';

    $data = [];
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data)) {
            $data = [];
        }
    }

    $ipKey = md5($ip);
    $data[$ipKey] = ((int) ($data[$ipKey] ?? 0)) + 1;

    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);

    return $data[$ipKey];
}

/**
 * Check whether an IP address has exceeded the rate limit.
 *
 * @param  string $ip  The IP address.
 * @return bool        True if the IP is rate limited.
 */
function esc_widget_is_rate_limited(string $ip): bool
{
    $settings = esc_widget_get_settings();
    $limit    = (int) ($settings['rate_limit_per_hour'] ?? 60);

    if ($limit <= 0) {
        return false; // No limit
    }

    return esc_widget_get_rate_count($ip) >= $limit;
}

/**
 * Clean up old rate limit files (older than 2 hours).
 */
function esc_widget_cleanup_rate_limits(): void
{
    if (!is_dir(ESC_WIDGET_RATE_LIMIT_DIR)) {
        return;
    }

    $cutoff = time() - 7200; // 2 hours ago

    foreach (glob(ESC_WIDGET_RATE_LIMIT_DIR . '/*.json') as $file) {
        if (filemtime($file) < $cutoff) {
            @unlink($file);
        }
    }
}

// ---------------------------------------------------------------------------
// Ticket creation from widget
// ---------------------------------------------------------------------------

/**
 * Create a ticket from a widget form submission.
 *
 * Validates the form data, checks rate limiting, and creates the ticket
 * via the platform API.
 *
 * @param  array  $formData   Form field values from the widget.
 * @param  string $ip         Submitter's IP address.
 * @param  string $origin     Request origin header.
 * @return array              { success, ticket_id?, error? }
 */
function esc_widget_create_ticket_from_widget(array $formData, string $ip = '', string $origin = ''): array
{
    $settings = esc_widget_get_settings();

    // Validate domain
    if (!empty($origin) && !esc_widget_validate_domain($origin)) {
        return ['success' => false, 'error' => 'domain_not_allowed'];
    }

    // Check rate limit
    if (!empty($ip) && esc_widget_is_rate_limited($ip)) {
        return ['success' => false, 'error' => 'rate_limit_exceeded'];
    }

    // Validate required fields
    $fields   = $settings['form_fields'] ?? [];
    $errors   = [];

    foreach ($fields as $field) {
        $name     = $field['name'] ?? '';
        $required = !empty($field['required']);
        $value    = trim((string) ($formData[$name] ?? ''));

        if ($required && $value === '') {
            $errors[] = $name;
        }

        // Basic email validation
        if ($field['type'] === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = $name . '_invalid';
        }
    }

    if (!empty($errors)) {
        return ['success' => false, 'error' => 'validation_failed', 'fields' => $errors];
    }

    // Sanitize input values
    $sanitized = [];
    foreach ($formData as $key => $value) {
        $sanitized[$key] = htmlspecialchars(strip_tags(trim((string) $value)), ENT_QUOTES, 'UTF-8');
    }

    // Build the ticket data
    $ticketData = [
        'subject'        => $sanitized['subject'] ?? ($sanitized['name'] ?? 'Web Widget Submission'),
        'body'           => $sanitized['message'] ?? '',
        'requester_name' => $sanitized['name'] ?? '',
        'requester_email'=> $sanitized['email'] ?? '',
        'channel'        => 'web-widget',
        'source'         => 'web-widget',
        'status'         => 'open',
        'priority'       => 'normal',
        'metadata'       => [
            'source'        => 'web-widget',
            'ip'            => $ip,
            'origin'        => $origin,
            'custom_fields' => [],
        ],
    ];

    // Append any custom fields to metadata
    $knownFields = ['name', 'email', 'subject', 'message'];
    foreach ($sanitized as $key => $value) {
        if (!in_array($key, $knownFields, true)) {
            $ticketData['metadata']['custom_fields'][$key] = $value;
        }
    }

    // Create the ticket via the platform
    $ticketId = null;

    if (function_exists('escalated_create_ticket')) {
        $result = escalated_create_ticket($ticketData);
        if (is_array($result)) {
            $ticketId = $result['id'] ?? null;
        } elseif (is_object($result)) {
            $ticketId = $result->id ?? null;
        }
    } else {
        // Stub: generate a placeholder ticket ID for development
        $ticketId = 'wt_' . bin2hex(random_bytes(8));
    }

    if (empty($ticketId)) {
        return ['success' => false, 'error' => 'ticket_creation_failed'];
    }

    // Increment rate limiter
    if (!empty($ip)) {
        esc_widget_increment_rate_count($ip);
    }

    // Broadcast the event
    if (function_exists('escalated_do_action')) {
        escalated_do_action('web_widget.ticket_created', [
            'ticket_id' => $ticketId,
            'ip'        => $ip,
            'origin'    => $origin,
        ]);
    }

    return ['success' => true, 'ticket_id' => $ticketId];
}

// ---------------------------------------------------------------------------
// Knowledge base article search (stub)
// ---------------------------------------------------------------------------

/**
 * Search knowledge base articles from the widget.
 *
 * Returns matching articles filtered by the configured KB categories.
 *
 * @param  string $query      Search query string.
 * @param  int    $limit      Maximum number of results.
 * @return array              Array of article summaries.
 */
function esc_widget_search_kb_articles(string $query, int $limit = 5): array
{
    $settings   = esc_widget_get_settings();
    $enabled    = !empty($settings['kb_enabled']);
    $categories = $settings['kb_categories'] ?? [];

    if (!$enabled) {
        return [];
    }

    if (empty(trim($query))) {
        return [];
    }

    // Delegate to the platform's KB search if available
    if (function_exists('escalated_search_kb')) {
        $filters = [];
        if (!empty($categories)) {
            $filters['category_ids'] = $categories;
        }
        $filters['limit'] = max(1, min(20, $limit));

        $results = escalated_search_kb($query, $filters);

        if (is_array($results)) {
            return array_map(function ($article) {
                return [
                    'id'       => $article['id'] ?? '',
                    'title'    => $article['title'] ?? '',
                    'excerpt'  => $article['excerpt'] ?? ($article['summary'] ?? ''),
                    'url'      => $article['url'] ?? '',
                    'category' => $article['category_name'] ?? '',
                ];
            }, array_slice($results, 0, $limit));
        }
    }

    // Stub: return empty results when the platform KB search is unavailable
    return [];
}

// ---------------------------------------------------------------------------
// Embed script generation
// ---------------------------------------------------------------------------

/**
 * Generate the HTML/JS embed snippet for the widget.
 *
 * @param  string|null $baseUrl  Override the base URL (defaults to platform URL).
 * @return string                The embed code snippet.
 */
function esc_widget_generate_embed_code(?string $baseUrl = null): string
{
    $settings = esc_widget_get_settings();
    $apiKey   = $settings['api_key'] ?? '';

    if ($baseUrl === null) {
        if (function_exists('escalated_url')) {
            $baseUrl = rtrim(escalated_url(''), '/');
        } else {
            $baseUrl = 'https://your-domain.com';
        }
    }

    $widgetUrl = $baseUrl . '/plugins/web-widget/widget.js';

    $snippet = '<script>' . "\n";
    $snippet .= '  (function(w,d,s,o){' . "\n";
    $snippet .= '    var f=d.getElementsByTagName(s)[0],j=d.createElement(s);' . "\n";
    $snippet .= '    j.async=true;j.src=o.url;j.dataset.apiKey=o.apiKey;' . "\n";
    $snippet .= '    w.EscalatedWidgetConfig=o;f.parentNode.insertBefore(j,f);' . "\n";
    $snippet .= '  })(window,document,"script",{' . "\n";
    $snippet .= '    url: "' . $widgetUrl . '",' . "\n";
    $snippet .= '    apiKey: "' . $apiKey . '"' . "\n";
    $snippet .= '  });' . "\n";
    $snippet .= '</script>';

    return $snippet;
}

// ---------------------------------------------------------------------------
// Public endpoint handler: embed script
// ---------------------------------------------------------------------------

/**
 * Serve the widget JavaScript file.
 *
 * The embed script bootstraps the widget on the customer's website.
 * It reads configuration from the API and renders the widget UI.
 *
 * @param  string $apiKey  The API key from the request.
 * @param  string $origin  The origin header from the request.
 * @return array           { content_type, body } or { error }
 */
function esc_widget_serve_embed_script(string $apiKey, string $origin = ''): array
{
    if (!esc_widget_validate_api_key($apiKey)) {
        return ['error' => 'invalid_api_key', 'status' => 403];
    }

    if (!empty($origin) && !esc_widget_validate_domain($origin)) {
        return ['error' => 'domain_not_allowed', 'status' => 403];
    }

    $settings = esc_widget_get_settings();

    // Build a minimal configuration object for the client
    $clientConfig = [
        'colors'    => $settings['colors'],
        'position'  => $settings['position'],
        'greeting'  => $settings['greeting'],
        'fields'    => $settings['form_fields'],
        'kb_enabled'=> !empty($settings['kb_enabled']),
    ];

    // TODO: In production, the widget.js file would be a full client-side
    // application. For now, return a stub that initialises the widget
    // with the server-provided configuration.
    $js  = "/* Escalated Web Widget v" . ESC_WIDGET_VERSION . " */\n";
    $js .= "(function(){\n";
    $js .= "  'use strict';\n";
    $js .= "  var cfg = " . json_encode($clientConfig, JSON_UNESCAPED_SLASHES) . ";\n";
    $js .= "  var apiKey = document.currentScript && document.currentScript.dataset.apiKey || '';\n";
    $js .= "  console.log('[EscalatedWidget] Initialised', cfg);\n";
    $js .= "  // TODO: Render widget DOM, attach event listeners, implement form submission & KB search\n";
    $js .= "})();\n";

    return [
        'content_type' => 'application/javascript; charset=utf-8',
        'body'         => $js,
    ];
}

// ---------------------------------------------------------------------------
// Public endpoint handler: form submission
// ---------------------------------------------------------------------------

/**
 * Handle a widget form submission.
 *
 * Validates the API key, origin, rate limit, and form data, then creates
 * a ticket.
 *
 * @param  array  $payload  { api_key, form_data: { name, email, subject, message, ... } }
 * @param  string $ip       The submitter's IP address.
 * @param  string $origin   The Origin header.
 * @return array            JSON-serialisable response.
 */
function esc_widget_handle_form_submission(array $payload, string $ip = '', string $origin = ''): array
{
    $apiKey = $payload['api_key'] ?? '';

    if (!esc_widget_validate_api_key($apiKey)) {
        return ['success' => false, 'error' => 'invalid_api_key'];
    }

    $formData = $payload['form_data'] ?? [];

    if (!is_array($formData) || empty($formData)) {
        return ['success' => false, 'error' => 'invalid_form_data'];
    }

    return esc_widget_create_ticket_from_widget($formData, $ip, $origin);
}

// ---------------------------------------------------------------------------
// Public endpoint handler: KB search
// ---------------------------------------------------------------------------

/**
 * Handle a widget KB search request.
 *
 * @param  array  $payload  { api_key, query }
 * @param  string $origin   The Origin header.
 * @return array            JSON-serialisable response.
 */
function esc_widget_handle_kb_search(array $payload, string $origin = ''): array
{
    $apiKey = $payload['api_key'] ?? '';

    if (!esc_widget_validate_api_key($apiKey)) {
        return ['success' => false, 'error' => 'invalid_api_key'];
    }

    if (!empty($origin) && !esc_widget_validate_domain($origin)) {
        return ['success' => false, 'error' => 'domain_not_allowed'];
    }

    $query = trim((string) ($payload['query'] ?? ''));
    $limit = (int) ($payload['limit'] ?? 5);

    $articles = esc_widget_search_kb_articles($query, $limit);

    return ['success' => true, 'articles' => $articles];
}

// ---------------------------------------------------------------------------
// Action: ticket.created -- tag tickets from widget
// ---------------------------------------------------------------------------

escalated_add_action('web_widget.ticket_created', function ($data) {
    $ticketId = $data['ticket_id'] ?? '';

    if (empty($ticketId)) {
        return;
    }

    if (function_exists('escalated_log')) {
        escalated_log('web-widget', "Ticket created via web widget: #{$ticketId} from {$data['origin']}");
    }
}, 10);

// ---------------------------------------------------------------------------
// Filter: ticket.channels -- add web widget as a channel
// ---------------------------------------------------------------------------

escalated_add_filter('ticket.channels', function (array $channels) {
    $channels[] = [
        'id'          => 'web-widget',
        'name'        => 'Web Widget',
        'icon'        => 'code-bracket',
        'description' => 'Tickets submitted via the embeddable website widget',
    ];

    return $channels;
}, 10);

// ---------------------------------------------------------------------------
// Filter: ticket.sources -- add web widget as a source
// ---------------------------------------------------------------------------

escalated_add_filter('ticket.sources', function (array $sources) {
    $sources[] = [
        'id'   => 'web-widget',
        'name' => 'Web Widget',
        'icon' => 'code-bracket',
    ];

    return $sources;
}, 10);

// ---------------------------------------------------------------------------
// Cron handler: clean up old rate limit files
// ---------------------------------------------------------------------------

escalated_add_action('escalated.cron.hourly', 'esc_widget_cleanup_rate_limits', 10);

// ---------------------------------------------------------------------------
// Page registration: widget configurator
// ---------------------------------------------------------------------------

escalated_register_page('admin/web-widget', [
    'title'      => 'Web Widget',
    'component'  => 'WebWidgetConfigurator',
    'capability' => 'manage_settings',
    'props'      => [
        'pluginSlug' => ESC_WIDGET_SLUG,
    ],
]);

// ---------------------------------------------------------------------------
// Admin menu item (under Channels)
// ---------------------------------------------------------------------------

escalated_register_menu_item([
    'id'         => 'web-widget-config',
    'label'      => 'Web Widget',
    'icon'       => 'code-bracket',
    'route'      => '/admin/web-widget',
    'parent'     => 'channels',
    'order'      => 30,
    'capability' => 'manage_settings',
]);

// ---------------------------------------------------------------------------
// Settings page component registration
// ---------------------------------------------------------------------------

escalated_add_page_component('admin.settings', 'channels', [
    'component' => 'WebWidgetConfigurator',
    'props'     => [
        'pluginSlug' => ESC_WIDGET_SLUG,
    ],
    'order' => 20,
]);

// ---------------------------------------------------------------------------
// Activation hook
// ---------------------------------------------------------------------------

escalated_add_action('escalated_plugin_activated_web-widget', function () {
    // Ensure config directory exists
    if (!is_dir(ESC_WIDGET_CONFIG_DIR)) {
        mkdir(ESC_WIDGET_CONFIG_DIR, 0755, true);
    }

    // Create default settings with a freshly generated API key
    if (!file_exists(ESC_WIDGET_CONFIG_FILE)) {
        $defaults = esc_widget_default_settings();
        $defaults['api_key'] = esc_widget_generate_api_key();
        esc_widget_save_settings($defaults);
    }

    // Ensure rate limit directory exists
    if (!is_dir(ESC_WIDGET_RATE_LIMIT_DIR)) {
        mkdir(ESC_WIDGET_RATE_LIMIT_DIR, 0755, true);
    }

    // Store plugin version
    if (function_exists('escalated_update_option')) {
        escalated_update_option('web_widget_plugin_version', ESC_WIDGET_VERSION);
    }
}, 10);

// ---------------------------------------------------------------------------
// Deactivation hook
// ---------------------------------------------------------------------------

escalated_add_action('escalated_plugin_deactivated_web-widget', function () {
    // Preserve settings so re-activation restores the configuration.
    // Full cleanup only happens on uninstall.

    if (function_exists('escalated_broadcast')) {
        escalated_broadcast('admin', 'web-widget.deactivated', [
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ]);
    }
}, 10);
