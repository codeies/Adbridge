<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Campaign_Status_Manager
{
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'adbridge_campaign_order';

        // Hook the cron job function
        add_action('campaign_status_update_cron', array($this, 'update_campaign_statuses'));
    }

    // Activate the cron job
    public static function activate()
    {
        if (!wp_next_scheduled('campaign_status_update_cron')) {
            wp_schedule_event(time(), 'hourly', 'campaign_status_update_cron');
        }
    }

    // Deactivate the cron job
    public static function deactivate()
    {
        wp_clear_scheduled_hook('campaign_status_update_cron');
    }

    // Update campaign statuses
    public function update_campaign_statuses()
    {
        global $wpdb;
        $current_date = current_time('Y-m-d');

        // 1. Mark finished campaigns (end_date passed)
        $finished_campaigns = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$this->table_name}
             WHERE end_date < %s
             AND status = 'completed'
             AND campaign_status != 'finished'",
            $current_date
        ));

        if (!empty($finished_campaigns)) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->table_name}
                 SET campaign_status = 'finished'
                 WHERE end_date < %s
                 AND status = 'completed'
                 AND campaign_status != 'finished'",
                $current_date
            ));

            foreach ($finished_campaigns as $campaign) {
                do_action('adbridge_campaign_ended', $campaign->id);
            }
        }

        // 2. Activate campaigns (start_date passed and status completed)
        $active_campaigns = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$this->table_name}
             WHERE start_date <= %s
             AND status = 'completed'
             AND campaign_status NOT IN ('finished', 'active')",
            $current_date
        ));

        if (!empty($active_campaigns)) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->table_name}
                 SET campaign_status = 'active'
                 WHERE start_date <= %s
                 AND status = 'completed'
                 AND campaign_status NOT IN ('finished', 'active')",
                $current_date
            ));

            foreach ($active_campaigns as $campaign) {
                do_action('adbridge_campaign_started', $campaign->id);
            }
        }

        // 3. Expire abandoned campaigns (status abandoned with dates passed)
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_name}
             SET campaign_status = 'expired'
             WHERE status = 'abandoned'
             AND (start_date <= %s OR end_date <= %s)
             AND campaign_status NOT IN ('finished', 'expired')",
            $current_date,
            $current_date
        ));
    }
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('Campaign_Status_Manager', 'activate'));
register_deactivation_hook(__FILE__, array('Campaign_Status_Manager', 'deactivate'));

// Initialize the class
global $campaign_status_manager;
$campaign_status_manager = new Campaign_Status_Manager();
