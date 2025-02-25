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

class AdBridge
{
    function __construct()
    {
        // add_action('init',[$this,'initialize']);
        add_action('admin_enqueue_scripts', [$this, 'loadAssets']);
        add_action('wp_enqueue_scripts', [$this, 'loadAssets']);
        add_action('admin_menu', [$this, 'adminMenu']);
        add_filter('script_loader_tag', [$this, 'loadScriptAsModule'], 10, 3);
        add_filter('script_loader_tag', [$this, 'loadScriptAsModuleTwo'], 10, 3);
        add_shortcode('adbridge_booking', [$this, 'wp_vite_react_render_shortcode']);
    }

    // function shortocode render()
    public function wp_vite_react_render_shortcode()
    {
        wp_enqueue_script('wp-vite-react-core');
        wp_enqueue_style('wp-vite-react-script');
        // wp_enqueue_style('wp-vite-react-style');

        include_once(plugin_dir_path(__FILE__) . "/inc/frontend.php");
    }

    // function load script as module
    function loadScriptAsModule($tag, $handle, $src)
    {
        if ('wp-vite-react-core' !== $handle) {
            return $tag;
        }
        $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
        return $tag;
    }

    // function load script as module
    function loadScriptAsModuleTwo($tag, $handle, $src)
    {
        if ('wp-vite-react-script' !== $handle) {
            return $tag;
        }
        $tag = '
        <script type="module" crossorigin >
        import RefreshRuntime from "' . esc_url($src) . '";
        RefreshRuntime.injectIntoGlobalHook(window);
        window.$RefreshReg$ = () => {};
        window.$RefreshSig$ = () => (type) => type;
        window.__vite_plugin_react_preamble_installed__ = true;
        </script>';
        return $tag;
    }


    // Add admin menu
    function adminMenu()
    {
        add_menu_page('WP React', 'WP React', 'manage_options', 'admin/admin.php', [$this, 'loadAdminPage'], 'dashicons-vault', 6);
    }

    // Admin page render
    function loadAdminPage()
    {
        wp_enqueue_script('wp-vite-react-core');
        wp_enqueue_script('wp-vite-react-script');
        // wp_enqueue_style('wp-vite-react-style');

        $pluginUrl = plugin_dir_url(__FILE__);
        wp_localize_script('wp-vite-react-core', 'AdBridge', [
            'url' => $pluginUrl,
            'nonce' => wp_create_nonce('wp_rest'),
        ]);

        include_once(plugin_dir_path(__FILE__) . "/inc/admin.php");
    }

    // Load assets  for admin and frontend
    function loadAssets()
    {
        // wp_register_script('wp-vite-react-core', plugins_url('dist/assets/index-0340b01b.js', __FILE__), [], time(), true);
        // wp_register_style('wp-vite-react-style', plugins_url('dist/assets/index-f25b5597.css', __FILE__), [], time(), 'all');

        wp_register_script('wp-vite-react-core', 'http://localhost:5173/src/main.jsx', ['wp-vite-react-script'], time(), true);


        wp_register_script(
            'wp-vite-react-script',
            'http://localhost:5173/@react-refresh',
            [],
            null,
            true
        );
    }
}
require_once plugin_dir_path(__FILE__) . 'inc/class-campaign-status-manager.php';
include_once(plugin_dir_path(__FILE__) . "/inc/message_scheduler.php");
include_once(plugin_dir_path(__FILE__) . "/inc/adbridge_campaign_orders_admin.php");
include_once(plugin_dir_path(__FILE__) . "/inc/woocommerce.php");


// Register activation hook correctly
function adbridge_campaign_order_install()
{
    AdBridge_Campaign_Order::get_instance()->install();
}
register_activation_hook(__FILE__, 'adbridge_campaign_order_install');

new AdBridge();
