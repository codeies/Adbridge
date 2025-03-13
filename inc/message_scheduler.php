<?php

namespace Codeies\AdRentals;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use WP_User;
use WP_Post;

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
        // Registration hooks
        add_action('user_register', [$this, 'scheduleRegistrationMessages'], 10, 1);



        add_action('adbridge_new_aboundent_cart', [$this, 'scheduleAboundentMessages'], 10, 1);

        // Campaign hooks
        add_action('woocommerce_order_status_completed', [$this, 'scheduleBookingConfirmationMessages']);
        add_action('woocommerce_order_status_completed', [$this, 'clearAbandonedCartMessages']);

        add_action('adbridge_campaign_started', [$this, 'scheduleCampaignStartMessages'], 10, 1);
        add_action('adbridge_campaign_ended', [$this, 'schedulePostCampaignMessages'], 10, 1);





        // User deletion hook
        add_action('delete_user', [$this, 'clearAllScheduledMessages'], 10, 1);

        // Message sending handlers
        add_action('send_scheduled_message', [$this, 'sendMessage'], 10, 3);
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

        $order_id = $cart_data->wc_order_id;
        $order = wc_get_order($order_id);

        $user_id = $order->get_user_id(); // Get the user ID associated with the order


        // Get notification configurations
        $sms_notifications = $this->filterNotificationsByTrigger(
            carbon_get_theme_option('abandoned_cart_sms'),
            'abandoned_cart'
        );

        $email_notifications = $this->filterNotificationsByTrigger(
            carbon_get_theme_option('abandoned_cart_email'),
            'abandoned_cart'
        );

        $meta_data = [
            'cart_id' => $adbridge_order_id,
            //'cart_contents' => isset($cart_data->cart_contents) ? $cart_data->cart_contents : '',
            'abandoned_at' => isset($cart_data->created_at) ? $cart_data->created_at : current_time('mysql'),
        ];

        // Schedule SMS messages
        if ($this->isSmsEnabled()) {
            $this->scheduleMessages($user_id, self::TYPE_SMS, $sms_notifications, self::TRIGGER_ABANDONED_CART, $meta_data);
        }

        // Schedule Email messages
        if ($this->isEmailEnabled()) {
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
        foreach ($notifications as $notification) {
            $delay = isset($notification['delay_hours']) ? (int) $notification['delay_hours'] : 0;
            $template_id = isset($notification['template']) ? (int) $notification['template'] : 0;

            if ($template_id > 0) {
                $this->scheduleSingleMessage($user_id, $type, $template_id, $delay, $trigger, $meta_data);
            }
        }
    }

    /**
     * Schedule a single message
     */
    private function scheduleSingleMessage(int $user_id, string $type, int $template_id, int $delay_hours, string $trigger, array $meta_data = []): void
    {
        $timestamp = time() + ($delay_hours * HOUR_IN_SECONDS);

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

        // Process message content
        $message = $this->processTemplate($template->post_content, $user, $message_data['meta_data'] ?? []);
        $subject = get_post_meta($template_id, '_template_subject', true);

        // Send message based on type
        if ($message_data['type'] === self::TYPE_SMS) {
            $this->sendSms($user, $message);
        } elseif ($message_data['type'] === self::TYPE_EMAIL) {
            $this->sendEmail($user, $subject, $message);
        }

        // Clean up metadata
        delete_user_meta($user_id, "scheduled_message_{$hook_id}");

        // Log the sent message
        $this->logMessageSent($user_id, $message_data['type'], $template_id, $message_data['trigger']);
    }

    /**
     * Process template with placeholders
     */
    private function processTemplate(string $content, WP_User $user, array $meta_data = []): string
    {
        global $wpdb;

        // Basic user placeholders
        $placeholders = [
            '{{user_name}}' => $user->display_name,
            '{{user_email}}' => $user->user_email,
            '{{user_phone}}' => get_user_meta($user->ID, 'phone', true),
            '{{first_name}}' => $user->first_name,
            '{{last_name}}' => $user->last_name,
        ];

        // Order-related placeholders (fetching from wp_adbridge_campaign_order table)
        if (isset($meta_data['order_id'])) {
            $order_data = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM wp_adbridge_campaign_order WHERE wc_order_id = %d", $meta_data['order_id']),
                ARRAY_A
            );

            if ($order_data) {
                $placeholders['{{order_id}}'] = $order_data['wc_order_id'];
                $placeholders['{{order_total}}'] = $order_data['total_cost'];
                $placeholders['{{order_date}}'] = date_i18n(get_option('date_format'), strtotime($order_data['created_at']));
                $placeholders['{{campaign_id}}'] = $order_data['campaign_id'];
                $placeholders['{{campaign_type}}'] = $order_data['campaign_type'];
                $placeholders['{{campaign_start_date}}'] = date_i18n(get_option('date_format'), strtotime($order_data['start_date']));
                $placeholders['{{campaign_end_date}}'] = date_i18n(get_option('date_format'), strtotime($order_data['end_date']));

                // Generate checkout URL if WooCommerce order exists
                $order = wc_get_order($order_data['wc_order_id']);
                if ($order) {
                    $placeholders['{{checkout_url}}'] = $order->get_checkout_payment_url();
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



    /**
     * Send SMS message
     */
    private function sendSms(WP_User $user, string $message): bool
    {
        $api_key = carbon_get_theme_option('adrental_sms_api_key');
        $api_secret = carbon_get_theme_option('adrental_sms_api_secret');
        $phone = get_user_meta($user->ID, 'phone', true);

        if (empty($phone) || empty($api_key) || empty($api_secret)) {
            return false;
        }

        // Implement Routee API SMS sending
        $response = wp_remote_post('https://connect.routee.net/sms', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("{$api_key}:{$api_secret}"),
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'to' => $phone,
                'body' => $message,
                'from' => apply_filters('adrentals_sms_sender_id', 'AdRentals')
            ])
        ]);

        if (is_wp_error($response)) {
            $this->logError('SMS sending failed: ' . $response->get_error_message(), [
                'user_id' => $user->ID,
                'phone' => $phone
            ]);
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code >= 200 && $response_code < 300;
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
    private function logMessageSent(int $user_id, string $type, int $template_id, string $trigger): void
    {
        // Implement message logging for reporting
        $log = [
            'user_id' => $user_id,
            'type' => $type,
            'template_id' => $template_id,
            'trigger' => $trigger,
            'sent_at' => current_time('mysql'),
        ];

        // Store in custom table or use WP options for simplicity
        $logs = get_option('adrentals_message_logs', []);
        $logs[] = $log;
        update_option('adrentals_message_logs', $logs);
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
}

// Initialize the class
new MessageScheduler();
