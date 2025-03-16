<?php


class AdBridgeTemplatePostType
{
    private $variables;

    public function __construct()
    {
        $this->variables = $this->get_variables();
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_custom_meta_boxes'));
    }

    public function register_post_type()
    {
        $labels = array(
            'name' => __('Templates'),
            'singular_name' => __('Template'),
            'menu_name' => __('Templates'),
            'name_admin_bar' => __('Template'),
            'add_new' => __('Add New'),
            'add_new_item' => __('Add New Template'),
            'new_item' => __('New Template'),
            'edit_item' => __('Edit Template'),
            'view_item' => __('View Template'),
            'all_items' => __('Sms/Email Templates'),
            'search_items' => __('Search Templates'),
            'parent_item_colon' => __('Parent Templates:'),
            'not_found' => __('No templates found.'),
            'not_found_in_trash' => __('No templates found in Trash.')
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=campaign',
            'query_var' => true,
            'rewrite' => array('slug' => 'template'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor')
        );

        register_post_type('template', $args);
    }

    public function add_custom_meta_boxes()
    {
        add_meta_box(
            'template_variables',
            __('Template Variables'),
            array($this, 'render_custom_meta_box_content'),
            'template',
            'side',
            'default'
        );
    }

    public function render_custom_meta_box_content($post)
    {
        echo '<p>Use the following variables in your template:</p>';
        echo '<ul>';
        foreach ($this->variables as $variable => $description) {
            echo '<li><strong>{' . esc_html($variable) . '}</strong>: ' . esc_html($description) . '</li>';
        }
        echo '</ul>';
    }



    private function get_variables()
    {
        return array(
            'user_name' => 'User Display Name',
            'user_email' => 'User Email Address',
            'user_phone' => 'User Phone Number',
            'first_name' => 'User First Name',
            'last_name' => 'User Last Name',

            'order_id' => 'WooCommerce Order ID',
            'order_total' => 'Total Cost of the Order',
            'order_date' => 'Order Creation Date',

            'campaign_id' => 'Campaign ID',
            'campaign_type' => 'Campaign Type (Radio/Billboard)',
            'campaign_start_date' => 'Campaign Start Date',
            'campaign_end_date' => 'Campaign End Date',
            'campaign_status' => 'Campaign Status',
            'campaign_arcon_permit' => 'ARCON Permit Status',

            'campaign_name' => 'Campaign Name',
            'campaign_duration' => 'Campaign Duration',
            'campaign_location' => 'Campaign Location',
            'campaign_media_type' => 'Campaign Media Type',
            'campaign_media_url' => 'Campaign Media URL',
            'campaign_extra_details' => 'Campaign Extra Details',

            'checkout_url' => 'WooCommerce Checkout Payment URL',
        );
    }
}

new AdBridgeTemplatePostType();
