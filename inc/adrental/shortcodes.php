<?php

class AdBridge_Shortcodes {
    public function __construct() {
        add_shortcode('adrental_notification', array($this, 'render_notification_message'));
        add_action('woo_wallet_before_my_wallet_content', array($this, 'display_notification_in_wallet'));
    }

    public function render_notification_message($atts) {
        // Only show to logged in users
        if (!is_user_logged_in()) {
            return '';
        }

        // Get the notification message from settings
        $message = carbon_get_theme_option('adrental_notification_message');
        
        if (empty($message)) {
            return '';
        }

        // Wrap the message in a div with a class for styling
        return '<div class="adrental-notification-message">' . wpautop($message) . '</div>';
    }

    public function display_notification_in_wallet() {
        // Only show to logged in users
        if (!is_user_logged_in()) {
            return;
        }

        // Get the notification message from settings
        $message = carbon_get_theme_option('adrental_notification_message');
        
        if (empty($message)) {
            return;
        }

        // Display the notification message
        echo '<div class="adrental-notification-message wallet-notification">' . wpautop($message) . '</div>';
    }
}

// Initialize the shortcodes
new AdBridge_Shortcodes(); 