<?php

namespace Escalated\Plugins\WebWidget\Services;

use Escalated\Plugins\WebWidget\Support\Config;

/**
 * Creates helpdesk tickets from widget form submissions.
 *
 * Validates and sanitises input fields, delegates to the platform's
 * ticket creation API (or dispatches a hook action as a fallback),
 * and returns a normalised result.
 */
class TicketCreator
{
    protected Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    // -----------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------

    /**
     * Create a ticket from widget form data.
     *
     * @param  array $data Raw form values (name, email, subject, message, plus any custom fields).
     * @return array       ['ok' => true, 'ticket_id' => ...] or ['ok' => false, 'error' => ..., ...]
     */
    public function createFromWidget(array $data): array
    {
        // ----------------------------------------------------------
        // 1. Validate required input fields
        // ----------------------------------------------------------
        $errors = $this->validate($data);

        if (!empty($errors)) {
            return [
                'ok'     => false,
                'error'  => 'validation_failed',
                'fields' => $errors,
            ];
        }

        // ----------------------------------------------------------
        // 2. Sanitise all values
        // ----------------------------------------------------------
        $sanitized = $this->sanitize($data);

        // ----------------------------------------------------------
        // 3. Build the ticket payload
        // ----------------------------------------------------------
        $subject = $sanitized['subject'] !== ''
            ? $sanitized['subject']
            : ($sanitized['name'] !== '' ? 'Widget message from ' . $sanitized['name'] : 'Web Widget Submission');

        $ticketPayload = [
            'subject'         => $subject,
            'description'     => $sanitized['message'] ?? '',
            'channel'         => 'widget',
            'requester_name'  => $sanitized['name'] ?? '',
            'requester_email' => $sanitized['email'] ?? '',
            'status'          => 'open',
            'priority'        => 'normal',
            'source'          => 'web-widget',
            'metadata'        => [
                'origin'        => 'web-widget',
                'custom_fields' => $this->extractCustomFields($sanitized),
            ],
        ];

        // ----------------------------------------------------------
        // 4. Create the ticket via the platform
        // ----------------------------------------------------------
        $ticketId = null;

        if (function_exists('escalated_create_ticket')) {
            $result = escalated_create_ticket([
                'subject'         => $ticketPayload['subject'],
                'description'     => $ticketPayload['description'],
                'channel'         => $ticketPayload['channel'],
                'requester_name'  => $ticketPayload['requester_name'],
                'requester_email' => $ticketPayload['requester_email'],
            ]);

            if (is_array($result)) {
                $ticketId = $result['id'] ?? null;
            } elseif (is_object($result)) {
                $ticketId = $result->id ?? null;
            }
        } else {
            // Fallback: dispatch action so other plugins or the platform
            // core can pick up the creation request.
            if (function_exists('escalated_do_action')) {
                escalated_do_action('ticket.create', $ticketPayload);
            }

            // When dispatching via action, the ticket ID may not be
            // immediately available. Generate a tracking reference so
            // the widget can acknowledge the submission.
            $ticketId = 'wt_' . bin2hex(random_bytes(8));
        }

        if (empty($ticketId)) {
            return ['ok' => false, 'error' => 'ticket_creation_failed'];
        }

        // ----------------------------------------------------------
        // 5. Broadcast success event
        // ----------------------------------------------------------
        if (function_exists('escalated_do_action')) {
            escalated_do_action('web_widget.ticket_created', [
                'ticket_id' => $ticketId,
                'email'     => $sanitized['email'] ?? '',
                'name'      => $sanitized['name'] ?? '',
            ]);
        }

        return ['ok' => true, 'ticket_id' => $ticketId];
    }

    // -----------------------------------------------------------------
    // Validation
    // -----------------------------------------------------------------

    /**
     * Validate the form data against the configured fields.
     *
     * @return string[] List of field error identifiers.
     */
    protected function validate(array $data): array
    {
        $fields = $this->config->value('form_fields', []);
        $errors = [];

        foreach ($fields as $field) {
            $name     = $field['name'] ?? '';
            $required = !empty($field['required']);
            $type     = $field['type'] ?? 'text';
            $value    = trim((string) ($data[$name] ?? ''));

            if ($required && $value === '') {
                $errors[] = $name;
                continue;
            }

            if ($type === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[] = $name . '_invalid';
            }
        }

        return $errors;
    }

    // -----------------------------------------------------------------
    // Sanitisation
    // -----------------------------------------------------------------

    /**
     * Sanitise all form values: trim, strip tags, encode entities.
     */
    protected function sanitize(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            $clean[$key] = htmlspecialchars(
                strip_tags(trim((string) $value)),
                ENT_QUOTES,
                'UTF-8'
            );
        }

        return $clean;
    }

    /**
     * Extract any custom fields (fields beyond name, email, subject, message).
     */
    protected function extractCustomFields(array $sanitized): array
    {
        $known  = ['name', 'email', 'subject', 'message'];
        $custom = [];

        foreach ($sanitized as $key => $value) {
            if (!in_array($key, $known, true)) {
                $custom[$key] = $value;
            }
        }

        return $custom;
    }
}
