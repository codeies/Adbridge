<?php

/**
 * Admin Campaign Orders Table View
 * 
 * This file creates an admin page to view all campaign orders stored in the adbridge_campaign_order table
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Adbridge_Campaign_Orders_Admin
{

    // Table name
    private $table_name;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'adbridge_campaign_order';

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Add custom styles
        add_action('admin_head', array($this, 'add_custom_styles'));
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'edit.php?post_type=campaign', // Parent slug (moves it under "Campaign" post type)
            'Campaign Orders', // Page title
            'Campaign Orders', // Menu title
            'manage_options', // Capability
            'campaign_orders', // Menu slug
            array($this, 'render_admin_page') // Callback function
        );
    }

    /**
     * Add custom styles for the admin page
     */
    public function add_custom_styles()
    {
        echo '<style>
            .campaign-orders-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
            }
            .campaign-orders-table th, 
            .campaign-orders-table td {
                padding: 10px;
                text-align: left;
                border: 1px solid #ddd;
            }
            .campaign-orders-table th {
                background-color: #f1f1f1;
                font-weight: bold;
            }
            .campaign-orders-table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .campaign-orders-table tr:hover {
                background-color: #f1f1f1;
            }
            .order-details-btn {
                cursor: pointer;
                color: #0073aa;
                text-decoration: underline;
            }
            .status-abandoned {
                color: #999;
                font-style: italic;
            }
            .status-pending {
                color: #f39c12;
                font-weight: bold;
            }
            .status-active {
                color: #27ae60;
                font-weight: bold;
            }
            .status-completed {
                color: #2980b9;
            }
            .status-cancelled {
                color: #e74c3c;
            }
            .order-modal {
                display: none;
                position: fixed;
                z-index: 999;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.4);
            }
            .order-modal-content {
                background-color: #fefefe;
                margin: 5% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 80%;
                max-height: 80%;
                overflow-y: auto;
            }
            .close-modal {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }
            .campaign-filter {
                margin: 15px 0;
                display: flex;
                gap: 10px;
                align-items: center;
            }
            .export-btn {
                margin-left: auto;
            }
            pre {
                white-space: pre-wrap;
                word-wrap: break-word;
                background: #f5f5f5;
                padding: 10px;
                border-radius: 4px;
                max-height: 300px;
                overflow-y: auto;
            }
        </style>';
    }

    /**
     * Render the admin page
     */
    public function render_admin_page()
    {
        global $wpdb;

        // Process any actions
        if (isset($_GET['action']) && $_GET['action'] === 'export' && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'export_campaigns')) {
            $this->export_campaigns();
        }

        // Get filter values
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $type_filter = isset($_GET['campaign_type']) ? sanitize_text_field($_GET['campaign_type']) : '';

        // Build the query
        $query = "SELECT * FROM {$this->table_name} WHERE 1=1";
        $params = array();

        if (!empty($status_filter)) {
            $query .= " AND status = %s";
            $params[] = $status_filter;
        }

        if (!empty($type_filter)) {
            $query .= " AND campaign_type = %s";
            $params[] = $type_filter;
        }

        $query .= " ORDER BY created_at DESC";

        // Execute the query
        if (!empty($params)) {
            $campaigns = $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            $campaigns = $wpdb->get_results($query);
        }

        // Get unique campaign types and statuses for filters
        $types = $wpdb->get_col("SELECT DISTINCT campaign_type FROM {$this->table_name}");
        $statuses = $wpdb->get_col("SELECT DISTINCT status FROM {$this->table_name}");

?>
        <div class="wrap">
            <h1>Campaign Orders</h1>

            <!-- Filters -->
            <div class="campaign-filter">
                <form method="get">
                    <input type="hidden" name="page" value="adbridge-campaign-orders">

                    <label for="status">Status:</label>
                    <select name="status" id="status">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo esc_attr($status); ?>" <?php selected($status_filter, $status); ?>>
                                <?php echo esc_html(ucfirst($status)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="campaign_type">Campaign Type:</label>
                    <select name="campaign_type" id="campaign_type">
                        <option value="">All Types</option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?php echo esc_attr($type); ?>" <?php selected($type_filter, $type); ?>>
                                <?php echo esc_html(ucfirst($type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="button">Filter</button>
                </form>

                <!-- Export Button -->
                <div class="export-btn">
                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'export'), 'export_campaigns')); ?>" class="button button-primary">
                        Export to CSV
                    </a>
                </div>
            </div>

            <!-- Table -->
            <table class="campaign-orders-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Campaign ID</th>
                        <th>Order ID</th>
                        <th>Type</th>
                        <th>Total Cost</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Payment</th>
                        <th>Campaign</th>
                        <th>ARCON Permit</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($campaigns)): ?>
                        <tr>
                            <td colspan="11">No campaign orders found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td><?php echo esc_html($campaign->id); ?></td>
                                <td><?php echo esc_html($campaign->campaign_id); ?></td>
                                <td>
                                    <?php if (!empty($campaign->wc_order_id)): ?>
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $campaign->wc_order_id . '&action=edit')); ?>" target="_blank">
                                            #<?php echo esc_html($campaign->wc_order_id); ?>
                                        </a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(ucfirst($campaign->campaign_type)); ?></td>
                                <td><?php echo wc_price($campaign->total_cost); ?></td>
                                <td><?php echo $campaign->start_date ? esc_html(date('M j, Y', strtotime($campaign->start_date))) : '—'; ?></td>
                                <td><?php echo $campaign->end_date ? esc_html(date('M j, Y', strtotime($campaign->end_date))) : '—'; ?></td>
                                <td class="status-<?php echo esc_attr(strtolower($campaign->status)); ?>">
                                    <?php echo esc_html(ucfirst($campaign->status)); ?>
                                </td>
                                <td class="status-<?php echo esc_attr(strtolower($campaign->campaign_status)); ?>">
                                    <?php echo esc_html(ucfirst($campaign->campaign_status)); ?>
                                </td>
                                <td>
                                    <?php if (!empty($campaign->arcon_permit)): ?>
                                        <a href="<?php echo esc_url(wp_get_attachment_url($campaign->arcon_permit)); ?>" target="_blank">View Permit</a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($campaign->created_at))); ?></td>
                                <td>
                                    <span class="order-details-btn" data-id="<?php echo esc_attr($campaign->id); ?>">View Details</span>
                                    <div id="order-modal-<?php echo esc_attr($campaign->id); ?>" class="order-modal">
                                        <div class="order-modal-content">
                                            <span class="close-modal">&times;</span>
                                            <h2>Campaign Details</h2>
                                            <h3>Basic Information</h3>
                                            <table class="widefat">
                                                <tr>
                                                    <th>Campaign ID</th>
                                                    <td><?php echo esc_html($campaign->campaign_id); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Type</th>
                                                    <td><?php echo esc_html(ucfirst($campaign->campaign_type)); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Status</th>
                                                    <td class="status-<?php echo esc_attr(strtolower($campaign->status)); ?>">
                                                        <?php echo esc_html(ucfirst($campaign->status)); ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Total Cost</th>
                                                    <td><?php echo wc_price($campaign->total_cost); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Created</th>
                                                    <td><?php echo esc_html(date('F j, Y, g:i a', strtotime($campaign->created_at))); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Last Updated</th>
                                                    <td><?php echo esc_html(date('F j, Y, g:i a', strtotime($campaign->updated_at))); ?></td>
                                                </tr>
                                            </table>

                                            <h3>Campaign Data</h3>
                                            <?php
                                            $campaign_data = json_decode($campaign->campaign_data, true);
                                            $relevant_data = [];

                                            switch ($campaign->campaign_type) {
                                                case 'billboard':
                                                    $relevant_data = [
                                                        'Billboard Name' => $campaign_data['campaign_details']['billboard_name'],
                                                        'Location' => $campaign_data['campaign_details']['location'],
                                                        'Duration Type' => $campaign_data['campaign_details']['duration_type'],
                                                        'Start Date' => $campaign_data['campaign_details']['start_date'],
                                                        'End Date' => $campaign_data['campaign_details']['end_date'],
                                                        'Media Type' => $campaign_data['campaign_details']['media_type'],
                                                        'Media URL' => $campaign_data['campaign_details']['media_url']
                                                    ];
                                                    break;
                                                case 'radio':
                                                    $relevant_data = [
                                                        'Station Name' => $campaign_data['campaign_details']['station_name'],
                                                        'Session' => $campaign_data['campaign_details']['session'],
                                                        'Spots' => $campaign_data['campaign_details']['spots'],
                                                        'Start Date' => $campaign_data['campaign_details']['start_date'],
                                                        'End Date' => $campaign_data['campaign_details']['end_date'],
                                                        'Script Type' => $campaign_data['campaign_details']['script_type'],
                                                        'Jingle Creation Type' => $campaign_data['campaign_details']['jingle_creation_type']
                                                    ];
                                                    break;
                                                case 'tv':
                                                    $relevant_data = [
                                                        'Station Name' => $campaign_data['campaign_details']['station_name'],
                                                        'Session' => $campaign_data['campaign_details']['session'],
                                                        'Spots' => $campaign_data['campaign_details']['spots'],
                                                        'Start Date' => $campaign_data['campaign_details']['start_date'],
                                                        'End Date' => $campaign_data['campaign_details']['end_date'],
                                                        'Script Type' => $campaign_data['campaign_details']['script_type'],
                                                        'Jingle Creation Type' => $campaign_data['campaign_details']['jingle_creation_type']
                                                    ];
                                                    break;
                                                default:
                                                    $relevant_data = $campaign_data;
                                                    break;
                                            }
                                            ?>
                                            <table class="widefat">
                                                <?php foreach ($relevant_data as $key => $value): ?>
                                                    <tr>
                                                        <th><?php echo esc_html($key); ?></th>
                                                        <td><?php echo esc_html($value); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>

                                            <?php if (!empty($campaign->media_file)): ?>
                                                <h3>Media File</h3>
                                                <?php
                                                $media_url = wp_get_attachment_url($campaign->media_file);
                                                $media_type = get_post_mime_type($campaign->media_file);
                                                if (strpos($media_type, 'image') !== false):
                                                ?>
                                                    <img src="<?php echo esc_url($media_url); ?>" style="max-width: 100%; height: auto;">
                                                <?php elseif (strpos($media_type, 'video') !== false): ?>
                                                    <video controls style="max-width: 100%;">
                                                        <source src="<?php echo esc_url($media_url); ?>" type="<?php echo esc_attr($media_type); ?>">
                                                        Your browser does not support the video tag.
                                                    </video>
                                                <?php else: ?>
                                                    <a href="<?php echo esc_url($media_url); ?>" target="_blank">Download Media File</a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <script>
            jQuery(document).ready(function($) {
                // Modal functionality
                $('.order-details-btn').click(function() {
                    var id = $(this).data('id');
                    $('#order-modal-' + id).css('display', 'block');
                });

                $('.close-modal').click(function() {
                    $(this).closest('.order-modal').css('display', 'none');
                });

                $(window).click(function(event) {
                    if ($(event.target).hasClass('order-modal')) {
                        $('.order-modal').css('display', 'none');
                    }
                });
            });
        </script>
<?php
    }

    /**
     * Export campaigns to CSV
     */
    private function export_campaigns()
    {
        global $wpdb;

        // Build the query with filters
        $query = "SELECT * FROM {$this->table_name} WHERE 1=1";
        $params = array();

        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $query .= " AND status = %s";
            $params[] = sanitize_text_field($_GET['status']);
        }

        if (isset($_GET['campaign_type']) && !empty($_GET['campaign_type'])) {
            $query .= " AND campaign_type = %s";
            $params[] = sanitize_text_field($_GET['campaign_type']);
        }

        $query .= " ORDER BY created_at DESC";

        // Execute the query
        if (!empty($params)) {
            $campaigns = $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            $campaigns = $wpdb->get_results($query);
        }

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="campaign-orders-export-' . date('Y-m-d') . '.csv"');

        // Create output stream
        $output = fopen('php://output', 'w');

        // Add CSV header row
        fputcsv($output, array(
            'ID',
            'Campaign ID',
            'WC Order ID',
            'Campaign Type',
            'Total Cost',
            'Start Date',
            'End Date',
            'Status',
            'ARCON Permit',
            'Created At',
            'Updated At'
        ));

        // Add data rows
        foreach ($campaigns as $campaign) {
            fputcsv($output, array(
                $campaign->id,
                $campaign->campaign_id,
                $campaign->wc_order_id,
                $campaign->campaign_type,
                $campaign->total_cost,
                $campaign->start_date,
                $campaign->end_date,
                $campaign->status,
                $campaign->arcon_permit,
                $campaign->created_at,
                $campaign->updated_at
            ));
        }

        fclose($output);
        exit;
    }
}

// Initialize the class
$campaign_orders_admin = new Adbridge_Campaign_Orders_Admin();
