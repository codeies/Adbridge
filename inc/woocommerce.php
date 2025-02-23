<?php
add_action('rest_api_init', function () {
    // Get available payment gateways
    register_rest_route('adrentals/v1', '/available-gateways', array(
        'methods' => 'GET',
        'callback' => 'get_available_payment_gateways',
        'permission_callback' => '__return_true'
    ));

    // Create checkout session
    register_rest_route('adrentals/v1', '/create-checkout-session', array(
        'methods' => 'POST',
        'callback' => 'create_checkout_session',
        'permission_callback' => '__return_true'
    ));

    // Get user details
    register_rest_route('adrentals/v1', '/user-details', array(
        'methods' => 'GET',
        'callback' => 'get_user_details',
        'permission_callback' => '__return_true'
    ));
});

function get_available_payment_gateways()
{
    if (!class_exists('WooCommerce')) {
        return new WP_Error('woocommerce_required', 'WooCommerce is not active', array('status' => 400));
    }

    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
    $response = array();

    foreach ($available_gateways as $gateway) {
        if ($gateway->enabled === 'yes') {
            $response[] = array(
                'id' => $gateway->id,
                'title' => $gateway->title,
                'description' => $gateway->description,
                'icon' => $gateway->icon,
            );
        }
    }

    return $response;
}

function get_user_details()
{
    $current_user = wp_get_current_user();
    $user_data = array(
        'logged_in' => is_user_logged_in(),
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'phone' => ''
    );

    if ($current_user->ID !== 0) {
        $user_data['first_name'] = $current_user->first_name;
        $user_data['last_name'] = $current_user->last_name;
        $user_data['email'] = $current_user->user_email;
        $user_data['phone'] = get_user_meta($current_user->ID, 'billing_phone', true);
    }

    return $user_data;
}

function create_checkout_session(WP_REST_Request $request)
{
    if (!class_exists('WooCommerce')) {
        return new WP_Error('woocommerce_required', 'WooCommerce is not active', array('status' => 400));
    }

    $params = $request->get_params();

    try {
        // Create a new order
        $order = wc_create_order();

        // Get the billboard product
        $billboard = wc_get_product(get_option('_adrental_wc_product'));

        if (!$billboard) {
            throw new Exception('Billboard product not found');
        }

        // Add billboard as line item with custom meta
        $item_id = $order->add_product(
            $billboard,
            1,
            array(
                'total' => $params['total'],
                'name' => $billboard->get_name() // Ensure product name is included
            )
        );

        // Add custom item meta
        if ($item_id) {
            wc_add_order_item_meta($item_id, 'Duration', '1 Day');
            wc_add_order_item_meta($item_id, 'Location', $params['location'] ?? '');
        }

        // Add order meta
        $order->update_meta_data('campaign_type', 'billboard');
        $order->update_meta_data('billboard_id', $params['billboard_id']);
        $order->update_meta_data('duration', $params['duration']);

        // Add customer details if provided
        if (!empty($params['customer_details'])) {
            $order->set_billing_first_name($params['customer_details']['first_name']);
            $order->set_billing_last_name($params['customer_details']['last_name']);
            $order->set_billing_email($params['customer_details']['email']);
            $order->set_billing_phone($params['customer_details']['phone']);
        }

        // Set payment method
        $order->set_payment_method($params['payment_method']);

        // Prevent guest checkout
        update_option('woocommerce_enable_guest_checkout', 'no');

        // Set order status
        $order->set_status('pending');

        // Save the order
        $order->save();

        // Calculate totals
        $order->calculate_totals();

        // Get checkout URL
        $checkout_url = $order->get_checkout_payment_url();

        return array(
            'success' => true,
            'order_id' => $order->get_id(),
            'checkout_url' => $checkout_url
        );
    } catch (Exception $e) {
        return new WP_Error('checkout_creation_failed', $e->getMessage(), array('status' => 400));
    }
}

// Add custom success redirect after payment
add_action('woocommerce_thankyou', 'redirect_after_successful_order', 10, 1);
function redirect_after_successful_order($order_id)
{
    $order = wc_get_order($order_id);

    if ($order && $order->is_paid()) {
        $campaign_type = $order->get_meta('campaign_type');

        if ($campaign_type === 'billboard') {
            wp_redirect(home_url('/campaign/success'));
            exit;
        }
    }
}

// Disable guest checkout
add_filter('woocommerce_enable_guest_checkout', '__return_false');
