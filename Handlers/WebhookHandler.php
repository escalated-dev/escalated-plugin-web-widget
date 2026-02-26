<?php

namespace Escalated\Plugins\WebWidget\Handlers;

use Escalated\Plugins\WebWidget\Support\Config;
use Escalated\Plugins\WebWidget\Services\TicketCreator;

/**
 * Handles inbound requests from the web widget: form submissions
 * and knowledge-base search queries.
 *
 * Each public method receives the decoded JSON payload and any
 * ambient request metadata (IP, Origin header), performs all
 * authentication / rate-limit / domain checks, and returns a
 * JSON-serialisable response array.
 */
class WebhookHandler
{
    protected Config $config;
    protected TicketCreator $ticketCreator;

    public function __construct(Config $config, TicketCreator $ticketCreator)
    {
        $this->config        = $config;
        $this->ticketCreator = $ticketCreator;
    }

    // -----------------------------------------------------------------
    // Form submission
    // -----------------------------------------------------------------

    /**
     * Handle a widget contact-form submission.
     *
     * Expected payload:
     *   { "api_key": "...", "form_data": { "name": ..., "email": ..., ... } }
     *
     * @param  array  $payload Decoded JSON body.
     * @param  string $ip      Submitter's IP address.
     * @param  string $origin  Value of the Origin request header.
     * @return array           JSON-serialisable response.
     */
    public function handleSubmission(array $payload, string $ip = '', string $origin = ''): array
    {
        // ----------------------------------------------------------
        // Authenticate
        // ----------------------------------------------------------
        $apiKey = $payload['api_key'] ?? '';

        if (!$this->config->validateApiKey($apiKey)) {
            return ['success' => false, 'error' => 'invalid_api_key'];
        }

        // ----------------------------------------------------------
        // Domain check
        // ----------------------------------------------------------
        if ($origin !== '' && !$this->config->validateDomain($origin)) {
            return ['success' => false, 'error' => 'domain_not_allowed'];
        }

        // ----------------------------------------------------------
        // Rate limit
        // ----------------------------------------------------------
        if ($ip !== '' && $this->config->isRateLimited($ip)) {
            return ['success' => false, 'error' => 'rate_limit_exceeded'];
        }

        // ----------------------------------------------------------
        // Validate payload structure
        // ----------------------------------------------------------
        $formData = $payload['form_data'] ?? [];

        if (!is_array($formData) || empty($formData)) {
            return ['success' => false, 'error' => 'invalid_form_data'];
        }

        // ----------------------------------------------------------
        // Create ticket
        // ----------------------------------------------------------
        $result = $this->ticketCreator->createFromWidget($formData);

        if (!($result['ok'] ?? false)) {
            return [
                'success' => false,
                'error'   => $result['error'] ?? 'ticket_creation_failed',
                'fields'  => $result['fields'] ?? [],
            ];
        }

        // ----------------------------------------------------------
        // Increment rate counter on successful submission
        // ----------------------------------------------------------
        if ($ip !== '') {
            $this->config->incrementRateCount($ip);
        }

        // ----------------------------------------------------------
        // Log the submission
        // ----------------------------------------------------------
        if (function_exists('escalated_log')) {
            escalated_log('web-widget', sprintf(
                'Ticket #%s created via widget from %s (IP: %s)',
                $result['ticket_id'],
                $origin ?: 'unknown origin',
                $ip ?: 'unknown'
            ));
        }

        return [
            'success'   => true,
            'ticket_id' => $result['ticket_id'],
        ];
    }

    // -----------------------------------------------------------------
    // KB search
    // -----------------------------------------------------------------

    /**
     * Handle a widget knowledge-base search request.
     *
     * Expected payload:
     *   { "api_key": "...", "query": "...", "limit": 5 }
     *
     * @param  array  $payload Decoded JSON body.
     * @param  string $origin  Value of the Origin request header.
     * @return array           JSON-serialisable response.
     */
    public function handleKbSearch(array $payload, string $origin = ''): array
    {
        // ----------------------------------------------------------
        // Authenticate
        // ----------------------------------------------------------
        $apiKey = $payload['api_key'] ?? '';

        if (!$this->config->validateApiKey($apiKey)) {
            return ['success' => false, 'error' => 'invalid_api_key'];
        }

        // ----------------------------------------------------------
        // Domain check
        // ----------------------------------------------------------
        if ($origin !== '' && !$this->config->validateDomain($origin)) {
            return ['success' => false, 'error' => 'domain_not_allowed'];
        }

        // ----------------------------------------------------------
        // Validate query
        // ----------------------------------------------------------
        $query = trim((string) ($payload['query'] ?? ''));
        $limit = max(1, min(20, (int) ($payload['limit'] ?? 5)));

        if ($query === '') {
            return ['success' => true, 'articles' => []];
        }

        // ----------------------------------------------------------
        // Check if KB search is enabled
        // ----------------------------------------------------------
        if (!$this->config->value('kb_enabled', true)) {
            return ['success' => true, 'articles' => []];
        }

        // ----------------------------------------------------------
        // Search via platform API or KB-AI plugin
        // ----------------------------------------------------------
        $articles = $this->searchArticles($query, $limit);

        return ['success' => true, 'articles' => $articles];
    }

    // -----------------------------------------------------------------
    // Internal
    // -----------------------------------------------------------------

    /**
     * Search knowledge-base articles using the platform's search API.
     *
     * Tries escalated_search_kb() first, then falls back to the
     * kb_ai.search action for AI-powered search results, and finally
     * the generic kb.search filter.
     *
     * @param  string $query  Search query.
     * @param  int    $limit  Maximum number of results.
     * @return array          Normalised array of article summaries.
     */
    protected function searchArticles(string $query, int $limit): array
    {
        $categories = $this->config->value('kb_categories', []);
        $filters    = ['limit' => $limit];

        if (!empty($categories)) {
            $filters['category_ids'] = $categories;
        }

        // Strategy 1: Platform's built-in KB search function
        if (function_exists('escalated_search_kb')) {
            $results = escalated_search_kb($query, $filters);

            if (is_array($results) && !empty($results)) {
                return $this->normalizeArticles($results, $limit);
            }
        }

        // Strategy 2: KB-AI plugin's semantic search action
        if (function_exists('escalated_apply_filters')) {
            $results = escalated_apply_filters('kb_ai.search', [], $query, $filters);

            if (is_array($results) && !empty($results)) {
                return $this->normalizeArticles($results, $limit);
            }
        }

        // Strategy 3: Generic kb.search filter
        if (function_exists('escalated_apply_filters')) {
            $results = escalated_apply_filters('kb.search', [], $query, $filters);

            if (is_array($results) && !empty($results)) {
                return $this->normalizeArticles($results, $limit);
            }
        }

        return [];
    }

    /**
     * Normalise raw article search results into a consistent shape
     * suitable for the widget frontend.
     */
    protected function normalizeArticles(array $results, int $limit): array
    {
        $articles = [];

        foreach (array_slice($results, 0, $limit) as $article) {
            $articles[] = [
                'id'       => $article['id'] ?? '',
                'title'    => $article['title'] ?? '',
                'excerpt'  => $article['excerpt'] ?? ($article['summary'] ?? ''),
                'url'      => $article['url'] ?? '',
                'category' => $article['category_name'] ?? '',
            ];
        }

        return $articles;
    }
}
