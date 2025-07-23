<?php

namespace Codeies\AdRentals;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use WP_User;
use WP_Post;
use DateTime;

/**
 * Handles scheduling and sending of notification messages
 */
class MessageScheduler
{
    /**
     * Message types constants
     */
    const TYPE_SMS = 'sms';
    const TYPE_EMAIL = 'email';

    /**
     * Notification triggers
     */
    const TRIGGER_REGISTRATION = 'registration';
    const TRIGGER_ABANDONED_CART = 'abandoned_cart';
    const TRIGGER_BOOKING_CONFIRMATION = 'booking_confirmation';
    const TRIGGER_CAMPAIGN_START = 'campaign_start';
    const TRIGGER_POST_CAMPAIGN = 'post_campaign';

    /**
     * Constructor
     */
    public function __construct()
    {
        // Create logs table on plugin activation
        $this->createLogsTable();

        // Registration hooks
        add_action('user_register', [$this, 'scheduleRegistrationMessages'], 10, 1);



        add_action('adbridge_new_abandoned_cart', [$this, 'scheduleAboundentMessages'], 10, 1);

        // Campaign hooks
        add_action('woocommerce_order_status_completed', [$this, 'scheduleBookingConfirmationMessages']);
        add_action('woocommerce_order_status_completed', [$this, 'clearAbandonedCartMessages']);

        add_action('adbridge_campaign_started', [$this, 'scheduleCampaignStartMessages'], 10, 1);
        add_action('adbridge_campaign_ended', [$this, 'schedulePostCampaignMessages'], 10, 1);





        // User deletion hook
        add_action('delete_user', [$this, 'clearAllScheduledMessages'], 10, 1);

        // Message sending handlers
        add_action('send_scheduled_message', [$this, 'sendMessage'], 10, 3);

        add_action('admin_menu', [$this, 'displayLogsPage']);
    }

    /**
     * Create custom table for message logs if it doesn't exist
     */
    private function createLogsTable(): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'adrentals_message_logs';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            template_id bigint(20) NOT NULL,
            trigger_type varchar(50) NOT NULL,
            sent_at datetime NOT NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Check if columns exist before adding them
        $columns = $wpdb->get_col("SHOW COLUMNS FROM $table_name");
        if (!in_array('recipient', $columns)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN recipient varchar(255) DEFAULT NULL");
        }
        if (!in_array('response', $columns)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN response text DEFAULT NULL");
        }
    }

    /**
     * Schedule registration follow-up messages
     */
    public function scheduleRegistrationMessages(int $user_id): void
    {
        $sms_followups = carbon_get_theme_option('registration_sms_followups');
        $email_followups = carbon_get_theme_option('registration_email_followups');

        // Schedule SMS notifications
        if ($this->isSmsEnabled()) {
            $this->scheduleMessages($user_id, self::TYPE_SMS, $sms_followups, self::TRIGGER_REGISTRATION);
        }

        // Schedule Email notifications
        if ($this->isEmailEnabled()) {
            $this->scheduleMessages($user_id, self::TYPE_EMAIL, $email_followups, self::TRIGGER_REGISTRATION);
        }
    }

    public function scheduleAboundentMessages(int $adbridge_order_id): void
    {
        global $wpdb;

        // Get the abandoned cart data
        $cart_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}adbridge_campaign_order WHERE id = %d",
            $adbridge_order_id
        ));

        if (!$cart_data) {
            error_log("Abandoned cart data not found for ID: {$adbridge_order_id}");
            return;
        }

        $user_id = $cart_data->user_id; // Get the user ID associated with the order

        // Get notification configurations
        $sms_notifications = carbon_get_theme_option('abandoned_cart_sms');
        $email_notifications = carbon_get_theme_option('abandoned_cart_email');

        error_log("Abandoned cart notifications - SMS: " . print_r($sms_notifications, true));
        error_log("Abandoned cart notifications - Email: " . print_r($email_notifications, true));

        $meta_data = [
            'cart_id' => $adbridge_order_id,
            'abandoned_at' => isset($cart_data->created_at) ? $cart_data->created_at : current_time('mysql'),
        ];

        // Schedule SMS messages
        if ($this->isSmsEnabled() && !empty($sms_notifications)) {
            $this->scheduleMessages($user_id, self::TYPE_SMS, $sms_notifications, self::TRIGGER_ABANDONED_CART, $meta_data);
        }

        // Schedule Email messages
        if ($this->isEmailEnabled() && !empty($email_notifications)) {
            $this->scheduleMessages($user_id, self::TYPE_EMAIL, $email_notifications, self::TRIGGER_ABANDONED_CART, $meta_data);
        }
    }

    /**
     * Clear abandoned cart messages when an order is completed
     * 
     * @param int $order_id WooCommerce order ID
     */
    public function clearAbandonedCartMessages($order_id): void
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $user_id = $order->get_user_id();
        if (!$user_id) {
            return;
        }

        // Clear all scheduled abandoned cart messages for this user
        $this->clearScheduledMessagesByTrigger($user_id, self::TRIGGER_ABANDONED_CART);
    }

    /**
     * Schedule booking confirmation messages
     */
    public function scheduleBookingConfirmationMessages($order_id): void
    {
        //$order_id = 984;

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        //die();

        $user_id = $order->get_user_id();
        if (!$user_id) {
            return;
        }


        $sms_notifications = $this->filterNotificationsByTrigger(
            carbon_get_theme_option('campaign_activity_sms'),
            'booking_confirmation'
        );

        $email_notifications = $this->filterNotificationsByTrigger(
            carbon_get_theme_option('campaign_activity_email'),
            'booking_confirmation'
        );
        $email = carbon_get_theme_option('campaign_activity_sms');


        $meta_data = [
            'order_id' => $order_id,
        ];

        if ($this->isSmsEnabled()) {
            $this->scheduleMessages($user_id, self::TYPE_SMS, $sms_notifications, self::TRIGGER_BOOKING_CONFIRMATION, $meta_data);
        }

        if ($this->isEmailEnabled()) {
            $this->scheduleMessages($user_id, self::TYPE_EMAIL, $email_notifications, self::TRIGGER_BOOKING_CONFIRMATION, $meta_data);
        }
    }

    /**
     * Schedule campaign start messages
     */
    public function scheduleCampaignStartMessages(int $campaign_id): void
    {
        // Get campaign details
        $campaign = $this->getCampaignDetails($campaign_id);
        if (!$campaign) {
            return;
        }

        $user_id = $campaign->user_id;

        $sms_notifications = $this->filterNotificationsByTrigger(
            carbon_get_theme_option('campaign_activity_sms'),
            'campaign_start'
        );

        $email_notifications = $this->filterNotificationsByTrigger(
            carbon_get_theme_option('campaign_activity_email'),
            'campaign_start'
        );

        $meta_data = [
            'campaign_id' => $campaign_id,
            'order_id' => $campaign->wc_order_id,
            'campaign_type' => $campaign->campaign_type,
            'start_date' => $campaign->start_date,
            'end_date' => $campaign->end_date,
        ];

        if ($this->isSmsEnabled()) {
            $this->scheduleMessages($user_id, self::TYPE_SMS, $sms_notifications, self::TRIGGER_CAMPAIGN_START, $meta_data);
        }

        if ($this->isEmailEnabled()) {
            $this->scheduleMessages($user_id, self::TYPE_EMAIL, $email_notifications, self::TRIGGER_CAMPAIGN_START, $meta_data);
        }
    }

    /**
     * Schedule post-campaign messages
     */
    public function schedulePostCampaignMessages(int $campaign_id): void
    {
        // Get campaign details
        $campaign = $this->getCampaignDetails($campaign_id);
        if (!$campaign) {
            return;
        }

        $user_id = $campaign->user_id;

        $sms_notifications = $this->filterNotificationsByTrigger(
            carbon_get_theme_option('campaign_activity_sms'),
            'post_campaign'
        );

        $email_notifications = $this->filterNotificationsByTrigger(
            carbon_get_theme_option('campaign_activity_email'),
            'post_campaign'
        );

        $meta_data = [
            'campaign_id' => $campaign_id,
            'order_id' => $campaign->wc_order_id,
            'campaign_type' => $campaign->campaign_type,
            'start_date' => $campaign->start_date,
            'end_date' => $campaign->end_date,
        ];

        if ($this->isSmsEnabled()) {
            $this->scheduleMessages($user_id, self::TYPE_SMS, $sms_notifications, self::TRIGGER_POST_CAMPAIGN, $meta_data);
        }

        if ($this->isEmailEnabled()) {
            $this->scheduleMessages($user_id, self::TYPE_EMAIL, $email_notifications, self::TRIGGER_POST_CAMPAIGN, $meta_data);
        }
    }

    /**
     * Schedule messages based on configuration
     */
    private function scheduleMessages(int $user_id, string $type, array $notifications, string $trigger, array $meta_data = []): void
    {
        error_log("Attempting to schedule messages for user {$user_id}, type {$type}, trigger {$trigger}");
        error_log("Notifications configuration: " . print_r($notifications, true));
        
        foreach ($notifications as $notification) {
            $delay = isset($notification['delay_hours']) ? (int) $notification['delay_hours'] : 0;
            $template_id = isset($notification['template']) ? (int) $notification['template'] : 0;
            $condition = $notification['condition'] ?? 'all'; // Get the condition

            error_log("Processing notification - Delay: {$delay}, Template ID: {$template_id}, Condition: {$condition}");

            if ($template_id > 0) {
                $this->scheduleSingleMessage(
                    $user_id,
                    $type,
                    $template_id,
                    $delay,
                    $trigger,
                    array_merge($meta_data, ['condition' => $condition]) // Add to metadata
                );
            } else {
                error_log("Skipping notification - Invalid template ID: {$template_id}");
            }
        }
    }

    private function userHasPurchase(int $user_id): bool
    {
        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'status' => ['completed', 'processing'], // Adjust based on your requirements
            'limit' => 1,
            'return' => 'ids',
        ]);

        return !empty($orders);
    }

    /**
     * Schedule a single message
     */
    private function scheduleSingleMessage(int $user_id, string $type, int $template_id, int $delay_minutes, string $trigger, array $meta_data = []): void
    {
        // Get WordPress timezone
        $wp_timezone = wp_timezone();
        
        // Get current time in WordPress timezone
        $datetime = new DateTime('now', $wp_timezone);
        
        // Add delay minutes
        $datetime->modify("+{$delay_minutes} minutes");
        
        // Convert to timestamp (in UTC, as expected by wp_schedule_single_event)
        $timestamp = $datetime->getTimestamp();
    
        // Generate unique hook ID
        $hook_id = md5($user_id . $type . $template_id . $trigger . serialize($meta_data) . time());
    
        // Store metadata for retrieval during sending
        update_user_meta($user_id, "scheduled_message_{$hook_id}", [
            'type' => $type,
            'template_id' => $template_id,
            'trigger' => $trigger,
            'meta_data' => $meta_data,
            'scheduled_at' => time(),
            'scheduled_for' => $timestamp,
            'timezone' => $wp_timezone->getName(), // Store timezone for reference
        ]);
    
        wp_schedule_single_event(
            $timestamp,
            'send_scheduled_message',
            [$user_id, $template_id, $hook_id]
        );
    }

    /**
     * Send scheduled message
     */
    public function sendMessage(int $user_id, int $template_id, string $hook_id): void
    {
        $user = get_userdata($user_id);
        $template = get_post($template_id);

        if (!$user instanceof WP_User || !$template || $template->post_type !== 'template') {
            return;
        }

        // Get message metadata
        $message_data = get_user_meta($user_id, "scheduled_message_{$hook_id}", true);
        if (!$message_data) {
            return;
        }

        $condition = $message_data['meta_data']['condition'] ?? 'all';
        if ($condition !== 'all') {
            $has_purchase = $this->userHasPurchase($user_id);

            // Determine if we should skip sending
            if (($condition === 'has_purchase' && !$has_purchase) ||
                ($condition === 'no_purchase' && $has_purchase)
            ) {
                delete_user_meta($user_id, "scheduled_message_{$hook_id}");
                return;
            }
        }

        // Process message content
        $message = $this->processTemplate($template->post_content, $user, $message_data['meta_data'] ?? []);
        $subject = get_the_title($template_id);

        // Send message based on type
        if ($message_data['type'] === self::TYPE_SMS) {
            $this->sendSms($user, $message);
        } elseif ($message_data['type'] === self::TYPE_EMAIL) {
            $this->sendEmail($user, $subject, $message);
        }

        // Clean up metadata
        delete_user_meta($user_id, "scheduled_message_{$hook_id}");

        // Log the sent message
        $this->logMessageSent($user_id, $message_data['type'], $template_id, $message_data['trigger'], $message_data['meta_data']['recipient'] ?? '', $message_data['meta_data']['response'] ?? '');
    }

    /**
     * Process template with placeholders
     */
    private function processTemplate(string $content, WP_User $user, array $meta_data = []): string
    {
        global $wpdb;

        // Basic user placeholders
        $placeholders = [
            '{user_name}' => $user->display_name,
            '{user_email}' => $user->user_email,
            '{user_phone}' => get_user_meta($user->ID, 'phone', true),
            '{first_name}' => $user->first_name,
            '{last_name}' => $user->last_name,
        ];

        // Order-related placeholders (fetching from dynamically prefixed table)
        if (isset($meta_data['order_id'])) {
            $table_name = $wpdb->prefix . 'adbridge_campaign_order'; // Using dynamic table prefix

            $order_data = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$table_name} WHERE wc_order_id = %d", $meta_data['order_id']),
                ARRAY_A
            );

            if ($order_data) {
                $placeholders += [
                    '{order_id}' => $order_data['wc_order_id'],
                    '{order_total}' => $order_data['total_cost'],
                    '{order_date}' => date_i18n(get_option('date_format'), strtotime($order_data['created_at'])),
                    '{campaign_id}' => $order_data['campaign_id'],
                    '{campaign_type}' => ucfirst($order_data['campaign_type']), // Capitalizing first letter
                    '{campaign_start_date}' => date_i18n(get_option('date_format'), strtotime($order_data['start_date'])),
                    '{campaign_end_date}' => date_i18n(get_option('date_format'), strtotime($order_data['end_date'])),
                    '{campaign_status}' => $order_data['campaign_status'],
                    '{campaign_arcon_permit}' => $order_data['arcon_permit'],
                ];

                if (!empty($order_data['campaign_data'])) {
                    $campaign_data = json_decode($order_data['campaign_data'], true);
                    $campaign_details = $campaign_data['campaign_details'] ?? [];

                    // General campaign placeholders
                    $placeholders['{campaign_name}'] = $campaign_details['station_name'] ?? $campaign_details['billboard_name'] ?? 'N/A';
                    $placeholders['{campaign_duration}'] = $campaign_details['duration'] ?? $campaign_details['duration_type'] ?? 'N/A';
                    $placeholders['{campaign_location}'] = $campaign_details['location'] ?? 'N/A';
                    $placeholders['{campaign_media_type}'] = $campaign_details['media_type'] ?? 'N/A';
                    $placeholders['{campaign_media_url}'] = $campaign_details['media_url'] ?? 'N/A';

                    // Dynamic content for different campaign types
                    $extraDetails = '';

                    if ($order_data['campaign_type'] === 'radio') {
                        $extraDetails .= "🎙 ** Radio Campaign Details:**\n";
                        $extraDetails .= "- Number of Ad Spots: " . ($campaign_details['spots'] ?? 'N/A') . "\n";
                        $extraDetails .= "- Session: " . ($campaign_details['session'] ?? 'N/A') . "\n";
                        $extraDetails .= "- Script Type: " . ($campaign_details['script_type'] ?? 'N/A') . "\n";
                        $extraDetails .= "- Jingle Text: " . ($campaign_details['jingle_text'] ?? 'N/A') . "\n";
                    } elseif ($order_data['campaign_type'] === 'tv') {
                        $extraDetails .= "📺 **TV Campaign Details:**\n";
                        $extraDetails .= "- Channel Name: " . ($campaign_details['channel_name'] ?? 'N/A') . "\n";
                        $extraDetails .= "- Number of Airings: " . ($campaign_details['airings'] ?? 'N/A') . "\n";
                        $extraDetails .= "- Airing Time: " . ($campaign_details['airing_time'] ?? 'N/A') . "\n";
                        $extraDetails .= "- Ad Duration: " . ($campaign_details['ad_duration'] ?? 'N/A') . "\n";
                    } elseif ($order_data['campaign_type'] === 'billboard') {
                        $extraDetails .= "🛑 **Billboard Campaign Details:**\n";
                        $extraDetails .= "- Duration Type: " . ($campaign_details['duration_type'] ?? 'N/A') . "\n";
                        $extraDetails .= "- Number of Days: " . ($campaign_details['num_days'] ?? 'N/A') . "\n";
                        $extraDetails .= "- Number of Weeks: " . ($campaign_details['num_weeks'] ?? 'N/A') . "\n";
                        $extraDetails .= "- Number of Months: " . ($campaign_details['num_months'] ?? 'N/A') . "\n";
                    }

                    // Add extra details placeholder
                    $placeholders['{campaign_extra_details}'] = nl2br($extraDetails);
                }


                // Generate checkout URL if WooCommerce order exists
                $order = wc_get_order($order_data['wc_order_id']);
                if ($order) {
                    $placeholders['{checkout_url}'] = $order->get_checkout_payment_url();
                }
            }
        }

        // Allow plugins to add their own placeholders
        $placeholders = apply_filters('adrentals_message_placeholders', $placeholders, $user, $meta_data);

        return str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $content
        );
    }



    private function getRouteeAccessToken(): ?string
    {
        // Check if a valid token is cached
        $cached_token = get_option('adrentals_routee_access_token');
        $token_expires = get_option('adrentals_routee_token_expires', 0);

        if ($cached_token && $token_expires > time() + 60) { // Add 60s buffer
            return $cached_token;
        }

        // Retrieve application credentials
        $app_id = carbon_get_theme_option('adrental_sms_api_key');
        $app_secret = carbon_get_theme_option('adrental_sms_api_secret');

        if (empty($app_id) || empty($app_secret)) {
            $this->logError('Missing Routee application credentials');
            return null;
        }

        // Encode credentials in Base64
        $credentials = base64_encode("{$app_id}:{$app_secret}");

        // Request access token
        $response = wp_remote_post('https://auth.routee.net/oauth/token', [
            'headers' => [
                'Authorization' => "Basic {$credentials}",
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'grant_type' => 'client_credentials',
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            $this->logError('Failed to retrieve Routee access token', ['error' => $response->get_error_message()]);
            return null;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200 || empty($response_body['access_token'])) {
            $this->logError('Invalid Routee token response', [
                'code' => $response_code,
                'response' => $response_body,
            ]);
            return null;
        }

        // Cache token and expiration
        $access_token = $response_body['access_token'];
        $expires_in = $response_body['expires_in'] ?? 3600; // Default to 1 hour
        update_option('adrentals_routee_access_token', $access_token);
        update_option('adrentals_routee_token_expires', time() + $expires_in);

        return $access_token;
    }

    private function formatPhoneNumber(string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove spaces, dashes, and other characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Ensure the number starts with a '+' followed by country code
        if (!preg_match('/^\+\d{10,15}$/', $phone)) {
            // Add country code if missing (e.g., assume US +1 for this example)
            if (preg_match('/^\d{10}$/', $phone)) {
                $phone = '+1' . $phone; // Adjust country code based on your needs
            } else {
                error_log("Invalid phone number format: {$phone}");
                return null;
            }
        }

        return $phone;
    }
    /**
     * Send SMS message
     */
    public function sendSms(WP_User $user, string $message): bool
    {
        // Get access token
        $api_token = $this->getRouteeAccessToken();
        if (!$api_token) {
            error_log("SMS sending failed: Unable to obtain Routee access token. User ID: {$user->ID}");
            $this->logMessageSent($user->ID, self::TYPE_SMS, 0, 'sms_failure', '', 'Failed to obtain access token');
            return false;
        }

        // Get and format phone number
        $phone = get_user_meta($user->ID, 'billing_phone', true);
        $phone = $this->formatPhoneNumber($phone);

        if (empty($phone)) {
            error_log("SMS sending failed: Invalid or missing phone number. User ID: {$user->ID}, Phone: " . ($phone ?: 'N/A'));
            $this->logMessageSent($user->ID, self::TYPE_SMS, 0, 'sms_failure', '', 'Invalid or missing phone number');
            return false;
        }

        // Get sender ID from settings
        $sender_id = carbon_get_theme_option('adrental_sms_sender_id');
        if (empty($sender_id)) {
            $sender_id = get_bloginfo('name'); // Fallback to site name if sender ID is not set
        }

        // Send SMS via Routee API
        $response = wp_remote_post('https://connect.routee.net/sms', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_token,
                'Content-Type' => 'application/json',
                'Expect' => '',
            ],
            'body' => json_encode([
                'body' => $message,
                'to' => $phone,
                'from' => $sender_id,
            ]),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            error_log("SMS sending failed: " . $response->get_error_message() . ". User ID: {$user->ID}, Phone: {$phone}");
            $this->logMessageSent($user->ID, self::TYPE_SMS, 0, 'sms_failure', $phone, $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code < 200 || $response_code >= 300) {
            error_log("SMS sending failed: HTTP Code {$response_code}, Response: {$response_body}. User ID: {$user->ID}, Phone: {$phone}");
            $this->logMessageSent($user->ID, self::TYPE_SMS, 0, 'sms_failure', $phone, "HTTP Code {$response_code}, Response: {$response_body}");
            return false;
        }

        error_log("SMS sent successfully to {$phone}. User ID: {$user->ID}");
        $this->logMessageSent($user->ID, self::TYPE_SMS, 0, 'sms_success', $phone, $response_body);
        return true;
    }



    /**
     * Send email message
     */
    private function sendEmail(WP_User $user, string $subject, string $message): bool
    {
        $to = $user->user_email;
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        // Use the business name as sender
        $site_name = get_bloginfo('name');
        $headers[] = 'From: ' . $site_name . ' <' . get_option('admin_email') . '>';

        // Allow HTML content
        $message = wpautop($message);

        // Apply email template if available
        $message = apply_filters('adrentals_email_template', $message, $user);

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Filter notifications by specific trigger
     */
    private function filterNotificationsByTrigger(array $notifications, string $trigger_name): array
    {
        return array_filter($notifications, function ($notification) use ($trigger_name) {
            return isset($notification['trigger']) && $notification['trigger'] === $trigger_name;
        });
    }

    /**
     * Get campaign details from database
     */
    private function getCampaignDetails(int $campaign_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'adbridge_campaign_order';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $campaign_id
        ));
    }

    /**
     * Clear all scheduled messages for a user
     */
    public function clearAllScheduledMessages(int $user_id): void
    {
        global $wpdb;

        // Get all scheduled message meta keys
        $meta_keys = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_key FROM {$wpdb->usermeta} 
            WHERE user_id = %d AND meta_key LIKE %s",
            $user_id,
            'scheduled_message_%'
        ));

        // Clear each scheduled event
        foreach ($meta_keys as $meta_key) {
            $hook_id = str_replace('scheduled_message_', '', $meta_key);
            $this->clearScheduledMessage($user_id, $hook_id);
        }
    }

    /**
     * Clear scheduled messages by trigger type
     */
    private function clearScheduledMessagesByTrigger(int $user_id, string $trigger): void
    {
        global $wpdb;

        // Get all scheduled message meta keys
        $meta_keys = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_key FROM {$wpdb->usermeta} 
            WHERE user_id = %d AND meta_key LIKE %s",
            $user_id,
            'scheduled_message_%'
        ));

        // Check each message and clear if trigger matches
        foreach ($meta_keys as $meta_key) {
            $meta_value = get_user_meta($user_id, $meta_key, true);
            if (isset($meta_value['trigger']) && $meta_value['trigger'] === $trigger) {
                $hook_id = str_replace('scheduled_message_', '', $meta_key);
                $this->clearScheduledMessage($user_id, $hook_id);
            }
        }
    }

    /**
     * Clear a specific scheduled message
     */
    private function clearScheduledMessage(int $user_id, string $hook_id): void
    {
        $meta_key = "scheduled_message_{$hook_id}";
        $meta_value = get_user_meta($user_id, $meta_key, true);

        if ($meta_value) {
            $template_id = $meta_value['template_id'] ?? 0;
            wp_clear_scheduled_hook('send_scheduled_message', [$user_id, $template_id, $hook_id]);
            delete_user_meta($user_id, $meta_key);
        }
    }

    /**
     * Log message sent for reporting
     */
    private function logMessageSent(int $user_id, string $type, int $template_id, string $trigger, string $recipient = '', string $response = ''): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'adrentals_message_logs';
        $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'type' => $type,
                'template_id' => $template_id,
                'trigger_type' => $trigger,
                'sent_at' => current_time('mysql'),
                'recipient' => $recipient,
                'response' => $response,
            ],
            ['%d', '%s', '%d', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Log error message
     */
    private function logError(string $message, array $context = []): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AdRentals MessageScheduler: ' . $message . ' ' . json_encode($context));
        }
    }

    /**
     * Check if SMS notifications are enabled
     */
    private function isSmsEnabled(): bool
    {
        return (bool) carbon_get_theme_option('adrental_enable_sms');
    }

    /**
     * Check if email notifications are enabled
     */
    private function isEmailEnabled(): bool
    {
        return (bool) carbon_get_theme_option('adrental_enable_email');
    }

    /**
     * Display logs page in admin area
     */
    public function displayLogsPage(): void
    {
        add_submenu_page(
            'edit.php?post_type=campaign',
            'Message Logs',
            'Message Logs',
            'manage_options',
            'adrentals-message-logs',
            [$this, 'renderLogsPage']
        );
    }

    /**
     * Render logs page content
     */
    public function renderLogsPage(): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'adrentals_message_logs';
        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY sent_at DESC", ARRAY_A);
        ?>
        <div class="wrap">
            <h1>Message Logs</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Type</th>
                        <th>Template ID</th>
                        <th>Trigger</th>
                        <th>Sent At</th>
                        <th>Recipient</th>
                        <th>Response</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log['id']); ?></td>
                            <td><?php echo esc_html($log['user_id']); ?></td>
                            <td><?php echo esc_html($log['type']); ?></td>
                            <td><?php echo esc_html($log['template_id']); ?></td>
                            <td><?php echo esc_html($log['trigger_type']); ?></td>
                            <td><?php echo esc_html($log['sent_at']); ?></td>
                            <td><?php echo esc_html($log['recipient']); ?></td>
                            <td><?php echo esc_html($log['response']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

// Initialize the class
$scheduler = new MessageScheduler();

// Create an instance of the MessageScheduler class
/* add_action('init', function () use ($scheduler) {
    $user_id = 1; // Example user ID
    $user = get_userdata($user_id);

    if (!$user instanceof WP_User) {
        error_log("Debug: Invalid user ID {$user_id}");
        echo "Invalid user ID {$user_id}";
        return;
    }

    // Define a test message
    $test_message = "This is a test SMS message for debugging purposes.";

    // Call the sendSms method directly
    $result = $scheduler->sendSms($user, $test_message);

    // Output the result for debugging
    if ($result) {
        echo "SMS sent successfully to user ID {$user_id}";
    } else {
        echo "Failed to send SMS to user ID {$user_id}. Check error logs for details.";
    }
});
 */