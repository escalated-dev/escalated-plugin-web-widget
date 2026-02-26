<?php

namespace Escalated\Plugins\WebWidget\Support;

/**
 * Settings CRUD, API key management, domain validation, rate limiting,
 * and lifecycle management for the Web Widget plugin.
 *
 * Configuration is persisted as JSON on disk. Rate-limit counters are
 * stored in hourly JSON files that are automatically cleaned up.
 */
class Config
{
    /** @var string Plugin version identifier. */
    public const VERSION = '0.1.0';

    /** @var string Plugin slug used for hooks, routes, and asset paths. */
    public const SLUG = 'web-widget';

    /** @var string Base directory for config and data files. */
    protected string $configDir;

    /** @var string Full path to settings.json. */
    protected string $configFile;

    /** @var string Directory containing hourly rate-limit JSON files. */
    protected string $rateLimitDir;

    /** @var array|null In-memory cache of the current settings. */
    protected ?array $cache = null;

    /**
     * @param string|null $baseDir Override the base plugin directory (defaults to plugin root).
     */
    public function __construct(?string $baseDir = null)
    {
        $base = $baseDir ?? dirname(__DIR__);
        $this->configDir    = $base . '/config';
        $this->configFile   = $this->configDir . '/settings.json';
        $this->rateLimitDir = $this->configDir . '/rate_limits';
    }

    // -----------------------------------------------------------------
    // Paths
    // -----------------------------------------------------------------

    public function configDir(): string
    {
        return $this->configDir;
    }

    public function rateLimitDir(): string
    {
        return $this->rateLimitDir;
    }

    // -----------------------------------------------------------------
    // Defaults
    // -----------------------------------------------------------------

    /**
     * Return the full set of default configuration values.
     *
     * Keys:
     *   api_key             - Secret key authenticating embed script requests
     *   colors              - Widget colour scheme { primary, background, text, launcher }
     *   position            - Launcher placement: "bottom-right" or "bottom-left"
     *   greeting            - Header greeting message
     *   form_fields         - Ordered array of form field definitions
     *   allowed_domains     - Domains permitted to embed the widget (empty = allow all)
     *   kb_enabled          - Whether KB search is shown in the widget
     *   kb_categories       - KB category IDs searchable from the widget
     *   rate_limit_per_hour - Max form submissions per IP per hour
     */
    public function defaults(): array
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

    // -----------------------------------------------------------------
    // Read / Write
    // -----------------------------------------------------------------

    /**
     * Retrieve the current plugin configuration, merged with defaults.
     */
    public function get(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (!file_exists($this->configFile)) {
            return $this->defaults();
        }

        $json = file_get_contents($this->configFile);
        $data = json_decode($json, true);

        if (!is_array($data)) {
            return $this->defaults();
        }

        $this->cache = array_replace_recursive($this->defaults(), $data);

        return $this->cache;
    }

    /**
     * Retrieve a single configuration value using dot-notation.
     *
     * @param  string $key     Dot-notated key, e.g. "colors.primary".
     * @param  mixed  $default Fallback if the key is missing.
     * @return mixed
     */
    public function value(string $key, mixed $default = null): mixed
    {
        $config   = $this->get();
        $segments = explode('.', $key);

        foreach ($segments as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }
            $config = $config[$segment];
        }

        return $config;
    }

    /**
     * Persist plugin configuration to disk.
     */
    public function save(array $config): bool
    {
        $this->ensureDirectory($this->configDir);

        $config = array_replace_recursive($this->defaults(), $config);
        $json   = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $ok     = file_put_contents($this->configFile, $json, LOCK_EX) !== false;

        if ($ok) {
            $this->cache = $config;
        }

        return $ok;
    }

    /**
     * Update one or more keys without overwriting the entire config.
     */
    public function update(array $partial): bool
    {
        $current = $this->get();
        return $this->save(array_replace_recursive($current, $partial));
    }

    /**
     * Clear the in-memory cache so the next get() re-reads from disk.
     */
    public function clearCache(): void
    {
        $this->cache = null;
    }

    // -----------------------------------------------------------------
    // API key helpers
    // -----------------------------------------------------------------

    /**
     * Generate a new API key (32-char hex prefixed with "esc_wk_").
     */
    public function generateApiKey(): string
    {
        return 'esc_wk_' . bin2hex(random_bytes(16));
    }

    /**
     * Validate a given API key against the stored key using
     * a timing-safe comparison.
     */
    public function validateApiKey(string $key): bool
    {
        if ($key === '') {
            return false;
        }

        $stored = $this->value('api_key', '');

        if ($stored === '') {
            return false;
        }

        return hash_equals($stored, $key);
    }

    // -----------------------------------------------------------------
    // Domain validation
    // -----------------------------------------------------------------

    /**
     * Validate whether a request origin is allowed to use the widget.
     *
     * If no allowed domains are configured, all origins are accepted.
     *
     * @param  string $origin The Origin header (e.g. "https://example.com").
     */
    public function validateDomain(string $origin): bool
    {
        $allowed = $this->value('allowed_domains', []);

        if (empty($allowed)) {
            return true;
        }

        $parsed = parse_url($origin);
        $host   = $parsed['host'] ?? '';

        if ($host === '') {
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

            // Wildcard subdomain: *.example.com
            if (str_starts_with($domain, '*.')) {
                $baseDomain = substr($domain, 2);
                if ($host === $baseDomain || str_ends_with($host, '.' . $baseDomain)) {
                    return true;
                }
            }

            // Match with or without www prefix
            if ($host === 'www.' . $domain || 'www.' . $host === $domain) {
                return true;
            }
        }

        return false;
    }

    // -----------------------------------------------------------------
    // Rate limiting
    // -----------------------------------------------------------------

    /**
     * Get the number of submissions for an IP in the current hour window.
     */
    public function getRateCount(string $ip): int
    {
        $file = $this->rateLimitFile();

        if (!file_exists($file)) {
            return 0;
        }

        $data = json_decode(file_get_contents($file), true);

        if (!is_array($data)) {
            return 0;
        }

        return (int) ($data[md5($ip)] ?? 0);
    }

    /**
     * Increment the rate counter for an IP and return the new count.
     */
    public function incrementRateCount(string $ip): int
    {
        $this->ensureDirectory($this->rateLimitDir);

        $file = $this->rateLimitFile();
        $data = [];

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (!is_array($data)) {
                $data = [];
            }
        }

        $ipKey        = md5($ip);
        $data[$ipKey] = ((int) ($data[$ipKey] ?? 0)) + 1;

        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);

        return $data[$ipKey];
    }

    /**
     * Check whether an IP has exceeded the configured rate limit.
     */
    public function isRateLimited(string $ip): bool
    {
        $limit = (int) $this->value('rate_limit_per_hour', 60);

        if ($limit <= 0) {
            return false;
        }

        return $this->getRateCount($ip) >= $limit;
    }

    /**
     * Remove rate-limit files older than 2 hours.
     */
    public function cleanupRateLimits(): void
    {
        if (!is_dir($this->rateLimitDir)) {
            return;
        }

        $cutoff = time() - 7200;

        foreach (glob($this->rateLimitDir . '/*.json') as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    // -----------------------------------------------------------------
    // Lifecycle
    // -----------------------------------------------------------------

    /**
     * Run first-time setup: create directories, generate API key,
     * persist default settings.
     */
    public function activate(): void
    {
        $this->ensureDirectory($this->configDir);
        $this->ensureDirectory($this->rateLimitDir);

        if (!file_exists($this->configFile)) {
            $defaults            = $this->defaults();
            $defaults['api_key'] = $this->generateApiKey();
            $this->save($defaults);
        }

        if (function_exists('escalated_update_option')) {
            escalated_update_option('web_widget_plugin_version', self::VERSION);
        }
    }

    /**
     * Deactivation handler -- preserves configuration for re-activation.
     */
    public function deactivate(): void
    {
        if (function_exists('escalated_broadcast')) {
            escalated_broadcast('admin', 'web-widget.deactivated', [
                'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            ]);
        }

        if (function_exists('escalated_log')) {
            escalated_log('web-widget', 'Plugin deactivated. Settings preserved.');
        }
    }

    // -----------------------------------------------------------------
    // Internal
    // -----------------------------------------------------------------

    /**
     * Return the path to the current hour's rate-limit file.
     */
    protected function rateLimitFile(): string
    {
        return $this->rateLimitDir . '/' . date('Y-m-d-H') . '.json';
    }

    /**
     * Ensure a directory exists on disk.
     */
    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}
