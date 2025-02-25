<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AdBridge_Campaign_Order
{
    private static $instance = null;
    private $table_name;
    private $product_id;
    private $uploads_dir;

    private function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'adbridge_campaign_order';
        $this->product_id = $this->get_or_create_product();

        // Create uploads directory path
        $uploads_dir = wp_upload_dir();
        $this->uploads_dir = $uploads_dir['basedir'] . '/adbridge-campaigns';

        // Create directory if it doesn't exist
        if (!file_exists($this->uploads_dir)) {
            wp_mkdir_p($this->uploads_dir);
        }

        add_action('rest_api_init', [$this, 'register_api_routes']);
        add_action('woocommerce_order_status_changed', [$this, 'update_campaign_order_status'], 10, 4);
        add_action('wp_scheduled_delete', [$this, 'cleanup_old_abandoned_campaigns']);
    }

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function install()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            campaign_id VARCHAR(50) NOT NULL,
            wc_order_id BIGINT(20) DEFAULT NULL,
            campaign_type VARCHAR(20) NOT NULL,
            campaign_data LONGTEXT NOT NULL,
            media_file BIGINT(20) DEFAULT NULL,
            arcon_permit BIGINT(20) DEFAULT NULL,
            total_cost DECIMAL(10,2) NOT NULL,
            start_date DATE DEFAULT NULL,
            end_date DATE DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'abandoned',
            campaign_status VARCHAR(20) DEFAULT 'scheduled',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY campaign_id (campaign_id),
            KEY wc_order_id (wc_order_id),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function register_api_routes()
    {
        register_rest_route('adrentals/v1', '/create-campaign-order', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_campaign_order'],
            'permission_callback' => '__return_true',
        ]);
    }

    private function get_or_create_product()
    {
        $product_id = get_option('_adrental_product_id');
        if ($product_id && get_post_status($product_id) === 'publish') {
            return $product_id;
        }

        // Add product creation logic here if needed
        return $product_id;
    }

    /**
     * Save media file from $_FILES to WordPress Media Library
     * 
     * @param string $campaign_id The campaign ID
     * @param array $params The request parameters
     * @return int|null The attachment ID or null on failure
     */
    private function save_media_file($campaign_id, $params)
    {
        //print_r($params);
        //die();
        if (!isset($_FILES['media_file'])) {
            return null;
        }

        $file_data = $_FILES['media_file'];
        // Check for upload errors
        if ($file_data['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Generate filename based on campaign type and ID
        $file_extension = pathinfo($file_data['name'], PATHINFO_EXTENSION);


        switch ($params['campaign_type']) {
            case 'billboard':
                $file_name = 'billboard_' . $campaign_id . '.' . $file_extension;
                break;
            case 'radio':
                $file_name = 'audio_' . $campaign_id . '.' . $file_extension;
                break;
            case 'tv':
                $file_name = 'video_' . $campaign_id . '.' . $file_extension;
                break;
            default:
                $file_name = 'media_' . $campaign_id . '.' . $file_extension;
        }

        // Prepare upload array for WordPress
        $upload = [
            'name' => $file_name,
            'type' => $file_data['type'],
            'tmp_name' => $file_data['tmp_name'],
            'error' => 0,
            'size' => $file_data['size']
        ];

        // Required for media handling
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Upload file to media library
        $attachment_id = media_handle_sideload($upload, 0, 'AdBridge Campaign: ' . $campaign_id);

        if (is_wp_error($attachment_id)) {
            return null;
        }

        // Store campaign reference in attachment meta
        update_post_meta($attachment_id, '_adbridge_campaign_id', $campaign_id);

        return $attachment_id;
    }

    /**
     * Save ARCON permit file from $_FILES
     */
    private function save_arcon_permit($campaign_id)
    {
        if (!isset($_FILES['arcon_permit'])) {
            return null;
        }

        $file_data = $_FILES['arcon_permit'];
        if ($file_data['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file_extension = pathinfo($file_data['name'], PATHINFO_EXTENSION);
        $file_name = 'permit_' . $campaign_id . '.' . $file_extension;

        $upload = [
            'name' => $file_name,
            'type' => $file_data['type'],
            'tmp_name' => $file_data['tmp_name'],
            'error' => 0,
            'size' => $file_data['size']
        ];

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_handle_sideload($upload, 0, 'AdBridge Permit: ' . $campaign_id);

        if (is_wp_error($attachment_id)) {
            return null;
        }

        update_post_meta($attachment_id, '_adbridge_campaign_id', $campaign_id);
        return $attachment_id;
    }

    /**
     * Get MIME type based on media type
     * 
     * @param string $media_type The media type
     * @return string The MIME type
     */
    private function get_mime_type($media_type)
    {
        switch ($media_type) {
            case 'image':
                return 'image/jpeg';
            case 'image-video':
                return 'image/jpeg';
            case 'video':
                return 'video/mp4';
            default:
                return 'image/jpeg';
        }
    }

    /**
     * Get file extension based on media type
     * 
     * @param string $media_type The media type
     * @return string The file extension with dot
     */
    private function get_file_extension($media_type)
    {
        switch ($media_type) {
            case 'image':
            case 'image-video':
                return '.jpg';
            case 'video':
                return '.mp4';
            default:
                return '.jpg';
        }
    }

    public function handle_campaign_order($request)
    {
        global $wpdb;
        $params = $request->get_params();
        $params = $params['campaign_data'];
        if (is_string($params)) {
            $params = json_decode($params, true); // true returns an array, not an object
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('JSON decode error in save_media_file: ' . json_last_error_msg());
                return false; // Exit gracefully if decoding fails
            }
        }

        //    die();
        $campaign_id = uniqid('camp_');

        // Save media files
        $media_file_id = $this->save_media_file($campaign_id, $params);
        $arcon_permit_id = $this->save_arcon_permit($campaign_id);

        // Prepare campaign data
        $campaign_data = [
            'campaign_id' => $campaign_id,
            'campaign_type' => sanitize_text_field($params['campaign_type']),
            'campaign_data' => json_encode($params),
            'media_file' => $media_file_id ? (string)$media_file_id : null,
            'arcon_permit' => $arcon_permit_id ? (string)$arcon_permit_id : null, // Add this column to your DB
            'total_cost' => floatval($params['total_cost']),
            'start_date' => sanitize_text_field($params['campaign_details']['start_date'] ?? $params[$params['campaign_type']]['startDate'] ?? null),
            'end_date' => sanitize_text_field($params['campaign_details']['end_date'] ?? $params[$params['campaign_type']]['endDate'] ?? null),
            'status' => 'abandoned',
        ];

        $wpdb->insert(
            $this->table_name,
            $campaign_data,
            ['%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s']
        );

        $order = wc_create_order();

        $order->add_product(wc_get_product($this->product_id), 1, ['subtotal' => $params['total_cost'], 'total' => $params['total_cost']]);
        $order->calculate_totals();

        update_post_meta($order->get_id(), '_campaign_id', $campaign_id);

        $wpdb->update(
            $this->table_name,
            ['wc_order_id' => $order->get_id()],
            ['campaign_id' => $campaign_id],
            ['%d'],
            ['%s']
        );

        $media_url = null;
        if ($media_file_path && is_numeric($media_file_path)) {
            $media_url = wp_get_attachment_url((int)$media_file_path);
        }

        return [
            'success' => true,
            'campaign_id' => $campaign_id,
            'order_id' => $order->get_id(),
            'checkout_url' => $order->get_checkout_payment_url(),
            'media_file_id' => $media_file_path ? (int)$media_file_path : null,
            'media_url' => $media_url,
        ];
    }

    public function update_campaign_order_status($order_id, $old_status, $new_status, $order)
    {
        global $wpdb;
        $campaign_id = get_post_meta($order_id, '_campaign_id', true);
        if (!$campaign_id) return;

        $status = ($new_status === 'completed') ? 'completed' : 'abandoned';
        $wpdb->update(
            $this->table_name,
            ['status' => $status],
            ['campaign_id' => $campaign_id],
            ['%s'],
            ['%s']
        );
    }

    public function cleanup_old_abandoned_campaigns()
    {
        global $wpdb;

        // First, get the list of media files to delete
        $abandoned_campaigns = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT media_file FROM {$this->table_name} WHERE status = 'abandoned' AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) AND media_file IS NOT NULL"
            )
        );

        // Delete the media files from WordPress Media Library
        foreach ($abandoned_campaigns as $campaign) {
            if (!empty($campaign->media_file) && is_numeric($campaign->media_file)) {
                wp_delete_attachment((int)$campaign->media_file, true);
            }
        }

        // Delete the database records
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE status = 'abandoned' AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )
        );
    }
}

AdBridge_Campaign_Order::get_instance();
