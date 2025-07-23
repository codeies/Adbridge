<?php


class AdBridge_APIs
{
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_custom_taxonomies_endpoint'));
        add_action('rest_api_init', array($this, 'register_campaigns_endpoint'));
        add_action('rest_api_init', array($this, 'register_jingles_announcements_endpoint'));
    }

    public function register_custom_taxonomies_endpoint()
    {
        register_rest_route('adrentals/v1', '/taxonomies', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_custom_taxonomies'),
            'permission_callback' => '__return_true',
        ));
    }

    public function get_custom_taxonomies()
    {
        $taxonomies = array('adrental_location', 'adrental_category');
        $result = array();

        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ));

            if (!is_wp_error($terms)) {
                $result[$taxonomy] = array_map(function ($term) {
                    return array(
                        'id' => $term->term_id,
                        'name' => $term->name,
                    );
                }, $terms);
            }
        }

        return new \WP_REST_Response($result, 200);
    }

    public function register_campaigns_endpoint()
    {
        register_rest_route('adrentals/v1', '/campaigns', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_campaigns'),
            'permission_callback' => '__return_true',
        ));
    }

    public function get_campaigns(\WP_REST_Request $request)
    {
        // Initialize query arguments for fetching campaigns
        $args = array(
            'post_type'      => 'campaign',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'tax_query'      => array(),
            'meta_query'     => array(),
        );

        // Handle taxonomy filter for adrental_category
        $adrental_category = $request->get_param('adrental_category');
        if (!empty($adrental_category)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'adrental_category',
                'field'    => 'id',
                'terms'    => $adrental_category,
            );
        }

        // Handle taxonomy filter for adrental_location
        $adrental_location = $request->get_param('adrental_location');
        if (!empty($adrental_location)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'adrental_location',
                'field'    => 'id',
                'terms'    => $adrental_location,
            );
        }

        // Handle meta query for _adrentals_type
        $adrentals_type = $request->get_param('adrentals_type');
        if (!empty($adrentals_type)) {
            $args['meta_query'][] = array(
                'key'     => '_adrentals_type',
                'value'   => $adrentals_type,
                'compare' => 'LIKE',
            );
        }

        // Fetch campaigns based on the constructed arguments
        $campaigns = get_posts($args);

        // Prepare an array to store the results
        $result = array();

        // Define the list of durations to fetch from post meta
        $durations = array(
            'Daily',
            'Daily Premium',
            'Weekly',
            'Weekly Premium',
            'Monthly',
            'Monthly Premium',
        );

        // Iterate through each campaign to build the result array
        foreach ($campaigns as $campaign) {
            // Initialize an array to store duration prices
            $duration_prices = array();

            // Fetch prices for each duration from post meta
            foreach ($durations as $duration) {
                $meta_key = strtolower(str_replace(' ', '_', $duration)) . '_price';
                $duration_prices[] = array(
                    'type'  => $duration,
                    'price' => (float) get_post_meta($campaign->ID, '_' . $meta_key, true),
                );
            }
            if ($adrentals_type == 'billboard') {
                $attributes = get_post_meta($campaign->ID, '_attributes_billboard', true);
            } else {
                $attributes = get_post_meta($campaign->ID, '_attributes', true);
            }

            $attrubutes_value = array();
            if (is_array($attributes)) {
                foreach ($attributes as $attribute) {
                    $attrubutes_value[] = array(
                        'attribute'  => $attribute['name'][0]['value'],
                        'value' =>  $attribute['attribute_value'][0]['value'],
                    );
                }
            } else {
                $attributes = [];
            }
            $jingleSlots = get_post_meta($campaign->ID, '_jingle_slots', true);

            $jingles_value = array();
            if (is_array($jingleSlots)) {
                foreach ($jingleSlots as $attribute) {
                    $jingles_value[] = array(
                        'name'  => $attribute['name'][0]['value'],
                        'max_slots' =>  $attribute['max_slots'][0]['value'],
                        'price_per_slot' =>  $attribute['price_per_slot'][0]['value'],
                    );
                }
            } else {
                $jingleSlots = [];
            }

            $announcementSlots = get_post_meta($campaign->ID, '_announcement_slots', true);

            $announcements_value = array();
            if (is_array($announcementSlots)) {
                foreach ($announcementSlots as $attribute) {
                    $announcements_value[] = array(
                        'name'  => $attribute['name'][0]['value'],
                        'max_slots' =>  $attribute['max_slots'][0]['value'],
                        'price_per_slot' =>  $attribute['price_per_slot'][0]['value'],
                    );
                }
            } else {
                $announcementSlots = [];
            }

            $acronTerms = get_post_meta($campaign->ID, '_arcon_terms', true);

            $acron_value = array();
            if (is_array($acronTerms)) {
                foreach ($acronTerms as $attribute) {;
                    $acron_value[] = array(
                        'name'  => $attribute['title'][0]['value'],
                        'cost' =>  $attribute['cost'][0]['value'],
                    );
                }
            }

            if (empty($acron_value)) {
                $acronTerms = carbon_get_theme_option('adbridge_arcon_terms');
                if (is_array($acronTerms)) {
                    foreach ($acronTerms as $attribute) {;
                        $acron_value[] = array(
                            'name'  => $attribute['title'],
                            'cost' =>  $attribute['cost'],
                        );
                    }
                }
            }
            // Build the campaign details array
            $campaign_details = array(
                'id'                => $campaign->ID,
                'featured_image'    => get_the_post_thumbnail_url($campaign->ID, 'full'),
                'title'             => get_the_title($campaign),
                'content'           => get_the_content(null, false, $campaign),
                'adrental_category' => wp_get_post_terms($campaign->ID, 'adrental_category', array('fields' => 'names')),
                'adrental_location' => wp_get_post_terms($campaign->ID, 'adrental_location', array('fields' => 'names')),
                '_adrentals_type'   => get_post_meta($campaign->ID, '_adrentals_type', true),
                'durations'         => $duration_prices,
                'attributes'        => $attrubutes_value,
                'jingles'           => $jingles_value,
                'announcements'     => $announcements_value,
                'acron'     => $acron_value,
            );

            // Add the campaign details to the result array
            $result[] = $campaign_details;
        }

        // Return the result as a WP_REST_Response
        return new \WP_REST_Response($result, 200);
    }

    public function register_jingles_announcements_endpoint()
    {
        register_rest_route('adrentals/v1', '/campaign/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_jingles_announcements'),
            'permission_callback' => '__return_true',
        ));
    }

    public function get_jingles_announcements(\WP_REST_Request $request)
    {
        $post_id = $request->get_param('id');

        // Fetch jingle slots
        $jingleSlots = get_post_meta($post_id, '_jingle_slots', true);
        $jingles_value = array();
        if (is_array($jingleSlots)) {
            foreach ($jingleSlots as $attribute) {
                $jingles_value[] = array(
                    'name' => $attribute['name'][0]['value'],
                    'max_slots' => $attribute['max_slots'][0]['value'],
                    'price_per_slot' => $attribute['price_per_slot'][0]['value'],
                );
            }
        } else {
            $jingleSlots = [];
        }

        // Fetch announcement slots
        $announcementSlots = get_post_meta($post_id, '_announcement_slots', true);
        $announcements_value = array();
        if (is_array($announcementSlots)) {
            foreach ($announcementSlots as $attribute) {
                $announcements_value[] = array(
                    'name' => $attribute['name'][0]['value'],
                    'max_slots' => $attribute['max_slots'][0]['value'],
                    'price_per_slot' => $attribute['price_per_slot'][0]['value'],
                );
            }
        } else {
            $announcementSlots = [];
        }

        // Build the result array
        $result = array(
            'jingles' => $jingles_value,
            'announcements' => $announcements_value,
        );

        // Return the result as a WP_REST_Response
        return new \WP_REST_Response($result, 200);
    }
}

new AdBridge_APIs();
