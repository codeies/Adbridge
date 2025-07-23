<?php


use Carbon_Fields\Container;
use Carbon_Fields\Field;

class AdbridgeCampaignPost
{

    /**
     * Constructor to initialize the plugin.
     */
    public function __construct()
    {
        // Register custom post type on initialization
        add_action('init', array($this, 'register_custom_post_type'));
        // Register custom taxonomy for locations
        add_action('init', array($this, 'register_locations_taxonomy'));
        add_action('init', array($this, 'register_categories_taxonomy'));
        // Add custom post type under the specified admin menu page
        //add_action('admin_menu', array($this, 'add_campaigns_to_menu'));

        // Hook to add meta fields using Carbon Fields
        add_action('carbon_fields_register_fields', array($this, 'add_custom_meta_fields'));
    }

    /**
     * Function to register the custom post type "Campaign".
     */
    public function register_custom_post_type()
    {
        $labels = array(
            'name'                  => _x('Campaigns', 'Post Type General Name', 'ad-rentals'),
            'singular_name'         => _x('Campaign', 'Post Type Singular Name', 'ad-rentals'),
            'menu_name'             => __('AdBridge', 'ad-rentals'),
            // Add other labels as needed
        );

        $args = array(
            'label'                 => __('Campaign', 'ad-rentals'),
            'description'           => __('Post Type for Campaigns', 'ad-rentals'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true, // We will add it to the menu manually
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
        );

        register_post_type('campaign', $args);
    }

    /**
     * Register custom taxonomy for locations.
     */
    public function register_locations_taxonomy()
    {
        $labels = array(
            'name'                       => _x('Locations', 'taxonomy general name', 'ad-rentals'),
            'singular_name'              => _x('Location', 'taxonomy singular name', 'ad-rentals'),
            // Add other labels as needed
        );

        $args = array(
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            //'rewrite'               => array('slug' => 'location'),
        );

        register_taxonomy('adrental_location', 'campaign', $args);
    }

    public function register_categories_taxonomy()
    {
        $labels = array(
            'name'                       => _x('Categories', 'taxonomy general name', 'ad-rentals'),
            'singular_name'              => _x('Category', 'taxonomy singular name', 'ad-rentals'),
            // Add other labels as needed
        );

        $args = array(
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            //'rewrite'               => array('slug' => 'category'),
        );

        register_taxonomy('adrental_category', 'campaign', $args);
    }

    /**
     * Add custom meta fields using Carbon Fields.
     */
    public function add_custom_meta_fields()
    {
        $default_attributes = [
            [
                'name' => 'Billboard Type',
                'attribute_value' => ''
            ],
            [
                'name' => 'Size',
                'attribute_value' => ''
            ],
            [
                'name' => 'Description',
                'attribute_value' => ''
            ]
        ];

        Container::make('post_meta', 'AdBridge Options')
            ->where('post_type', '=', 'campaign')
            ->add_fields(array(
                Field::make('select', 'adrentals_type', __('Campaign Type'))
                    ->add_options(array(
                        'billboard' => __('Billboard'),
                        'radio' => __('Radio'),
                        'tv' => __('TV'),
                    )),
                Field::make('complex', 'attributes_billboard', 'Attributes')
                    ->set_datastore(new Adrental_Serialized_Post_Meta_Datastore())
                    ->set_layout('tabbed-vertical')
                    ->set_min(1) // Minimum number of entries
                    ->set_max(5) // Maximum number of entries
                    ->add_fields([
                        Field::make('text', 'name', 'Name')
                            ->set_attribute('placeholder', 'Enter attribute name'),
                        Field::make('text', 'attribute_value', 'Value')
                            ->set_attribute('placeholder', 'Enter attribute value')
                    ])
                    ->set_header_template('<%- name %> - <%- attribute_value %>')
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'adrentals_type',
                            'value' => array('billboard'),
                            'compare' => 'IN',
                        ),
                    ))
                    ->set_default_value($default_attributes),
                Field::make('complex', 'attributes', 'Attributes')
                    ->set_datastore(new Adrental_Serialized_Post_Meta_Datastore())
                    ->set_layout('tabbed-vertical')
                    ->set_min(1) // Minimum number of entries
                    ->set_max(5) // Maximum number of entries
                    ->add_fields([
                        Field::make('text', 'name', 'Name')
                            ->set_attribute('placeholder', 'Enter attribute name'),
                        Field::make('text', 'attribute_value', 'Value')
                            ->set_attribute('placeholder', 'Enter attribute value')
                    ])
                    ->set_header_template('<%- name %> - <%- attribute_value %>')
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'adrentals_type',
                            'value' => array('tv', 'radio'),
                            'compare' => 'IN',
                        ),
                    )),
                //    ->set_default_value($default_attributes),

                Field::make('complex', 'jingle_slots', 'Jingle Slots')
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'adrentals_type',
                            'value' => array('radio', 'tv'),
                            'compare' => 'IN',
                        ),
                    ))
                    ->set_datastore(new Adrental_Serialized_Post_Meta_Datastore())
                    ->set_layout('tabbed-vertical')
                    ->add_fields(array(
                        Field::make('text', 'name', 'Name'),
                        Field::make('text', 'max_slots', 'Maximum Slots'),
                        Field::make('text', 'price_per_slot', 'Price Per Slot'),
                    ))
                    ->set_header_template('<%- name %> - <%- price_per_slot %>'),

                Field::make('complex', 'announcement_slots', 'Announcement Slots')
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'adrentals_type',
                            'value' => array('radio', 'tv'),
                            'compare' => 'IN',
                        ),
                    ))
                    ->set_datastore(new Adrental_Serialized_Post_Meta_Datastore())
                    ->set_layout('tabbed-vertical')
                    ->add_fields(array(
                        Field::make('text', 'name', 'Name'),
                        Field::make('text', 'max_slots', 'Maximum Slots'),
                        Field::make('text', 'price_per_slot', 'Price per slot'),
                    ))
                    ->set_header_template('<%- name %> - <%- price_per_slot %>'),

                Field::make('complex', 'arcon_terms', 'Arcon Terms')
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'adrentals_type',
                            'value' => array('radio', 'tv', 'billboard'),
                            'compare' => 'IN',
                        ),
                    ))
                    ->set_datastore(new Adrental_Serialized_Post_Meta_Datastore())
                    ->set_layout('tabbed-vertical')
                    ->add_fields(array(
                        Field::make('text', 'title', 'Title'),
                        Field::make('text', 'cost', 'Cost'),
                    ))
                    ->set_header_template('<%- title %> - <%- cost %>'),


                Field::make('text', 'daily_price', 'Daily Price')
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'adrentals_type',
                            'value' => 'billboard',
                            'compare' => '=',
                        ),
                    )),
                Field::make('text', 'daily_premium_price', 'Daily Premium Price')
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'adrentals_type',
                            'value' => 'billboard',
                            'compare' => '=',
                        ),
                    )),
                Field::make('text', 'weekly_price', 'Weekly Price')
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'adrentals_type',
                            'value' => 'billboard',
                            'compare' => '=',
                        ),
                    )),
                Field::make('text', 'weekly_premium_price', 'Weekly Premium Price')
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'adrentals_type',
                            'value' => 'billboard',
                            'compare' => '=',
                        ),
                    )),
                Field::make('text', 'monthly_price', 'Monthly Price')
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'adrentals_type',
                            'value' => 'billboard',
                            'compare' => '=',
                        ),
                    )),
                Field::make('text', 'monthly_premium_price', 'Monthly Premium Price')
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'adrentals_type',
                            'value' => 'billboard',
                            'compare' => '=',
                        ),
                    )),
            ));
    }

    /**
     * Add the custom post type "Campaigns" under the specified admin menu page.
     */
}

// Initialize the plugin
$AdbridgeCampaignPost = new AdbridgeCampaignPost();
