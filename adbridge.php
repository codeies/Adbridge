<?php

/**
 * Plugin Name: AdBridge
 * Description: AdBridge 
 * Author URI:  https://codeies.com
 * Plugin URI:  https://codeies.com
 * Version:     1.0.0
 * Author:      Codeies
 * Text Domain: adbridge
 * Domain Path: /i18n
 */

// Ensure WooCommerce is active before initializing the plugin
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function () {
        echo '<div class="error"><p><strong>AdBridge</strong> requires WooCommerce to be installed and activated. Please install WooCommerce first.</p></div>';
    });

    add_action('admin_init', function () {
        deactivate_plugins(plugin_basename(__FILE__));
    });

    return; // Stop execution of the plugin
}

class AdBridge
{
    function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'loadAssets']);
        add_filter('script_loader_tag', [$this, 'loadScriptAsModule'], 10, 3);
        add_filter('script_loader_tag', [$this, 'loadScriptAsModuleTwo'], 10, 3);
        add_shortcode('adbridge_booking', [$this, 'adbridge_booking_shortcode']);
        add_action('after_setup_theme', [$this, 'crb_load']);
    }

    function crb_load()
    {
        require_once('vendor/autoload.php');
        require_once(plugin_dir_path(__FILE__) . "inc/adrental/serialize-postdata.php");
        require_once(plugin_dir_path(__FILE__) . "inc/adrental/settings.php");

        \Carbon_Fields\Carbon_Fields::boot();
        new AdBridge_Plugin_Options();
    }

    public function adbridge_booking_shortcode()
    {
        if (!is_user_logged_in()) {
            $login_url = wp_login_url(get_permalink());
            return '<p>You must be <a href="' . esc_url($login_url) . '">logged in</a> to access this page.</p>';
        }

        wp_enqueue_script('adbridge-react-core');
        wp_enqueue_style('adbridge-react-script');

        ob_start();
        include_once(plugin_dir_path(__FILE__) . "/inc/frontend.php");
        return ob_get_clean();
    }

    function loadScriptAsModule($tag, $handle, $src)
    {
        if ('adbridge-react-core' !== $handle) {
            return $tag;
        }
        return '<script type="module" src="' . esc_url($src) . '"></script>';
    }

    function loadScriptAsModuleTwo($tag, $handle, $src)
    {
        if ('adbridge-react-script' !== $handle) {
            return $tag;
        }
        return '
        <script type="module" crossorigin>
        import RefreshRuntime from "' . esc_url($src) . '";
        RefreshRuntime.injectIntoGlobalHook(window);
        window.$RefreshReg$ = () => {};
        window.$RefreshSig$ = () => (type) => type;
        window.__vite_plugin_react_preamble_installed__ = true;
        </script>';
    }

    function loadAssets()
    {
        $debug = true;

        if (!$debug) {
            wp_enqueue_style('adrentals-style', plugin_dir_url(__FILE__) . '/dist/assets/index-1060073d.css', [], '1.0', 'all');
            wp_enqueue_script('adbridge-react-core', plugin_dir_url(__FILE__) . '/dist/assets/index-463f7eca.js', [], '1.0', true);
        } else {
            wp_register_script('adbridge-react-core', 'http://localhost:5173/src/main.jsx', ['adbridge-react-script'], time(), true);
        }

        wp_register_script(
            'adbridge-react-script',
            'http://localhost:5173/@react-refresh',
            [],
            null,
            true
        );

        wp_localize_script('adbridge-react-core', 'adbridgeData', [
            'restUrl'   => esc_url_raw(rest_url()),
            'ajaxUrl'   => esc_url_raw(admin_url('admin-ajax.php')),
            'nonce'     => wp_create_nonce('wp_rest'),
        ]);

        wp_enqueue_script('adbridge-react-core');
        wp_enqueue_script('adbridge-react-script');
    }
}

require_once(plugin_dir_path(__FILE__) . "inc/adrental/campaign-post.php");
require_once(plugin_dir_path(__FILE__) . "inc/adrental/template-post.php");
require_once(plugin_dir_path(__FILE__) . "inc/adrental/apis.php");
require_once(plugin_dir_path(__FILE__) . "inc/adrental/settings.php");

require_once plugin_dir_path(__FILE__) . 'inc/class-campaign-status-manager.php';
include_once(plugin_dir_path(__FILE__) . "/inc/message_scheduler.php");
include_once(plugin_dir_path(__FILE__) . "/inc/adbridge_campaign_orders_admin.php");
include_once(plugin_dir_path(__FILE__) . "/inc/woocommerce.php");

function adbridge_campaign_order_install()
{
    AdBridge_Campaign_Order::get_instance()->install();
}
register_activation_hook(__FILE__, 'adbridge_campaign_order_install');

new AdBridge();
