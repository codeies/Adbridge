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

        // Replace REST API with AJAX hooks
        add_action('wp_ajax_create_campaign_order', [$this, 'handle_campaign_order']);
        add_action('wp_ajax_nopriv_create_campaign_order', [$this, 'ajax_not_logged_in']);

        // Enqueue scripts for AJAX
        //add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        add_action('woocommerce_order_status_changed', [$this, 'update_campaign_order_status'], 10, 4);

        add_action('woocommerce_before_calculate_totals', [$this, 'customize_cart_item_details'], 20, 1);

        // Hook to store campaign_id in order meta when order is created
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'add_campaign_id_to_order_item'], 10, 4);

        // Hook to update campaign with order ID after order is created
        add_action('woocommerce_checkout_order_processed', [$this, 'update_campaign_with_order_id'], 10, 3);

        add_filter('woocommerce_cart_item_thumbnail', [$this, 'customize_cart_item_thumbnail'], 10, 3);
        add_action('user_register', [$this, 'add_wallet_credit_on_registration'], 10, 1);

        $this->init_thumbnail_hooks();
        //add_action('wp_scheduled_delete', [$this, 'cleanup_old_abandoned_campaigns']);
    }
    function add_featured_image_to_order_item($item, $cart_item_key, $values, $order)
    {
        if (isset($values['featured_image'])) {
            $item->add_meta_data('_campaign_featured_image', $values['featured_image']);
        }
    }
    function customize_order_item_thumbnail($thumbnail, $item)
    {
        // Get the campaign_id from the order item
        $campaign_id = $item->get_meta('_campaign_id');
        $featured_image = $item->get_meta('_campaign_featured_image');

        if ($featured_image) {
            return '<img src="' . esc_url($featured_image) . '"  width="100%"  alt="Campaign Image" class="adbridge-campaign-thumbnail" />';
        } else if ($campaign_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'adbridge_campaign_order';

            // Get campaign data from database
            $campaign_data = $wpdb->get_var($wpdb->prepare(
                "SELECT campaign_data FROM {$table_name} WHERE campaign_id = %s LIMIT 1",
                $campaign_id
            ));

            if ($campaign_data) {
                $campaign = json_decode($campaign_data, true);

                // Extract featured image from campaign data
                if (isset($campaign['campaign_details']['featured_image']) && !empty($campaign['campaign_details']['featured_image'])) {
                    $featured_image = $campaign['campaign_details']['featured_image'];
                    return '<img src="' . esc_url($featured_image) . '" alt="Campaign ' . esc_attr($campaign_id) . '" class="adbridge-campaign-thumbnail" />';
                }
            }
        }

        return $thumbnail;
    }

    // Method for admin order thumbnails
    function customize_admin_order_item_thumbnail($thumbnail, $item_id)
    {
        $item = WC_Order_Factory::get_order_item($item_id);
        $featured_image = $item->get_meta('_campaign_featured_image');

        if ($featured_image) {
            return '<img src="' . esc_url($featured_image) . '" alt="Campaign Image" class="adbridge-campaign-thumbnail" style="max-width: 50px;" />';
        }

        return $thumbnail;
    }

    // Method for email order thumbnails
    function customize_email_order_item_thumbnail($thumbnail, $item)
    {
        $featured_image = $item->get_meta('_campaign_featured_image');

        if ($featured_image) {
            return '<img src="' . esc_url($featured_image) . '" alt="Campaign Image" class="adbridge-campaign-thumbnail" style="max-width: 100px;" />';
        }

        return $thumbnail;
    }

    public function init_thumbnail_hooks()
    {
        // Your existing cart thumbnail hook is already in the constructor

        // Add hooks for order items
        add_filter('woocommerce_order_item_thumbnail', [$this, 'customize_order_item_thumbnail'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'add_featured_image_to_order_item'], 10, 4);

        // For admin order page
        add_filter('woocommerce_admin_order_item_thumbnail', [$this, 'customize_admin_order_item_thumbnail'], 10, 2);

        // For order emails
        add_filter('woocommerce_email_order_item_thumbnail', [$this, 'customize_email_order_item_thumbnail'], 10, 2);
    }

    public function add_wallet_credit_on_registration($user_id)
    {
        $amount = get_option('adbridge_signup_bonus', 10000); // Get the configured amount, default to 10000 if not set
        $wallet = new Woo_Wallet_Wallet();
        $wallet->credit($user_id, $amount, 'Sign-up Bonus');
    }

    /**
     * Add campaign_id to order item meta
     */
    public function add_campaign_id_to_order_item($item, $cart_item_key, $values, $order)
    {
        if (isset($values['campaign_id'])) {
            $item->add_meta_data('_campaign_id', $values['campaign_id']);
        }
    }

    /**
     * Update campaign with order ID when an order is processed
     */
    public function update_campaign_with_order_id($order_id, $posted_data, $order)
    {


        //error_log("update_campaign_with_order_id: ");
        global $wpdb;

        // Get line items from the order
        $order = wc_get_order($order_id);

        foreach ($order->get_items() as $item) {

            // Get campaign_id from the order item meta
            $campaign_id = $item->get_meta('_campaign_id');

            if ($campaign_id) {

                // Update the campaign record with the order ID
                $wpdb->update(
                    $this->table_name,
                    ['wc_order_id' => $order_id],
                    ['campaign_id' => $campaign_id],
                    ['%d'], // wc_order_id is an integer
                    ['%s']  // campaign_id is a string
                );

                // Also store campaign ID in the main order meta for easier reference
                update_post_meta($order_id, '_campaign_id', $campaign_id);
            }
        }
    }
    /**
     * Handle unauthorized AJAX requests
     */
    public function ajax_not_logged_in()
    {
        wp_send_json_error(array('message' => 'You must be logged in to create a campaign order.'));
        wp_die();
    }


    function customize_cart_item_details($cart)
    {
        // Remove unnecessary admin/AJAX checks
        if (is_admin() && !defined('DOING_AJAX')) return;

        // Loop through cart items
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {

            // Check if the cart item has the custom campaign data
            if (isset($cart_item['campaign_id']) && isset($cart_item['total_cost'])) {
                /*     echo '<pre>';
                print_r($cart_item);
                die(); */
                $cart_item['data']->set_price($cart_item['total_cost']);
                $cart_item['data']->set_name($cart_item['custom_title']);
            }
            if (isset($cart_item['featured_image']) && !empty($cart_item['featured_image'])) {
                $cart_item['data']->set_image_id(attachment_url_to_postid($cart_item['featured_image']));
            }
        }
    }

    function customize_cart_item_thumbnail($thumbnail, $cart_item, $cart_item_key)
    {
        if (isset($cart_item['featured_image']) && !empty($cart_item['featured_image'])) {
            return '<img src="' . esc_url($cart_item['featured_image']) . '" alt="' . esc_attr($cart_item['data']->get_name()) . '" />';
        }
        return $thumbnail;
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

    public function update_plugin()
    {
        global $wpdb;
        $table_name = $this->table_name;
        $current_version = get_option('adbridge_plugin_version', '1.0'); // Default version

        // Version 1.1: Add `user_id` column
        if (version_compare($current_version, '1.1', '<')) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'user_id'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN user_id BIGINT(20) NOT NULL AFTER id");
            }
            update_option('adbridge_plugin_version', '1.1');
        }

        // Future updates go here (example)
        /*         if (version_compare($current_version, '1.2', '<')) {
            // Example future update: Adding a new column
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'new_column'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN new_column TEXT DEFAULT NULL AFTER campaign_status");
            }
            update_option('my_plugin_version', '1.2');
        } */
    }

    private function get_or_create_product()
    {
        $product_id = get_option('_adbridge_wc_product');
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

    /**
     * Handle campaign order creation via AJAX
     */
    public function handle_campaign_order()
    {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'adbridge_campaign_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            wp_die();
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User not logged in'));
            wp_die();
        }

        defined('WC_ABSPATH') || exit;

        // Load cart functions which are loaded only on the front-end.
        include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
        include_once WC_ABSPATH . 'includes/class-wc-cart.php';

        if (is_null(WC()->cart)) {
            wc_load_cart();
        }

        global $wpdb;

        // Get campaign data from POST
        $params = isset($_POST['campaign_data']) ? $_POST['campaign_data'] : '';

        if (is_string($params)) {
            $params = json_decode(stripslashes($params), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(array('message' => 'Invalid JSON data: ' . json_last_error_msg()));
                wp_die();
            }
        }

        $campaign_id = uniqid('camp_');

        // Save media files
        $media_file_id = $this->save_media_file($campaign_id, $params);
        $arcon_permit_id = $this->save_arcon_permit($campaign_id);

        // Prepare campaign data
        $campaign_data = [
            'user_id' => get_current_user_id(), // Ensure the user ID is included
            'campaign_id' => $campaign_id,
            'campaign_type' => sanitize_text_field($params['campaign_type']),
            'campaign_data' => json_encode($params),
            'media_file' => $media_file_id ? (string)$media_file_id : null,
            'arcon_permit' => $arcon_permit_id ? (string)$arcon_permit_id : null,
            'total_cost' => floatval($params['total_cost']),
            'start_date' => sanitize_text_field($params['campaign_details']['start_date'] ?? $params[$params['campaign_type']]['startDate'] ?? null),
            'end_date' => sanitize_text_field($params['campaign_details']['end_date'] ?? $params[$params['campaign_type']]['endDate'] ?? null),
            'status' => 'abandoned',
        ];

        $wpdb->insert(
            $this->table_name,
            $campaign_data,
            ['%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s']
        );

        $adbridge_order_id = $wpdb->insert_id; // Get the inserted ID

        do_action('adbridge_new_abandoned_cart', $adbridge_order_id);

        // **Add product to cart instead of creating order**
        $product_id = $this->product_id;
        if ($params['campaign_type'] == 'billboard') {
            $title = ucfirst($params['campaign_type']) . " Ad - " . $params['campaign_details']['billboard_name'];
        } else {
            $title = ucfirst($params['campaign_type']) . " Ad - " . $params['campaign_details']['station_name'];
        }

        $featured_image = $params['campaign_details']['featured_image'];

        $cart_item_data = [
            'campaign_id' => $campaign_id,
            'custom_title' => $title,
            'total_cost' => $params['total_cost'],
            'featured_image' => $featured_image,
            'unique_key' => md5(microtime() . rand())
        ];

        // Add the product to the cart with custom cart item data
        $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);

        if ($cart_item_key) {
            wp_send_json([
                'success' => true,
                'campaign_id' => $campaign_id,
                'checkout_url' => wc_get_checkout_url(),
            ]);
        } else {
            wp_send_json([
                'success' => false,
                'message' => "Order Processing Failed",
            ]);
        }
        wp_die(); // Required to terminate AJAX request properly
    }


    public function update_campaign_order_status($order_id, $old_status, $new_status, $order)
    {
        global $wpdb;
        
        // Get the order
        $order = wc_get_order($order_id);
        if (!$order) return;

        // Get campaign_id from order items
        foreach ($order->get_items() as $item) {
            $campaign_id = $item->get_meta('_campaign_id');
            if ($campaign_id) {
                $status = ($new_status === 'completed') ? 'completed' : 'abandoned';
                $wpdb->update(
                    $this->table_name,
                    ['status' => $status],
                    ['campaign_id' => $campaign_id],
                    ['%s'],
                    ['%s']
                );
            }
        }
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
