<?php
// Ensure WordPress is loaded
defined('ABSPATH') || exit;

// Admin Dashboard page for the Advanced Woo Recommendations Plugin
function awr_dashboard_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'advanced-woo-recommendations'));
    }

    $total_recommendations = awr_get_total_recommendations();
    $total_sales = awr_get_total_sales_from_recommendations();
    $recent_recommendations = awr_get_recent_recommendations(5); // Get the 5 most recent recommendations

    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Advanced Woo Recommendations Dashboard', 'advanced-woo-recommendations'); ?></h1>

        <div class="awr-dashboard-stats">
            <h2><?php esc_html_e('Statistics', 'advanced-woo-recommendations'); ?></h2>
            <p><?php esc_html_e('Total recommendations delivered:', 'advanced-woo-recommendations'); ?> <?php echo esc_html($total_recommendations); ?></p>
            <p><?php esc_html_e('Total sales generated from recommendations:', 'advanced-woo-recommendations'); ?> <?php echo esc_html(wc_price($total_sales)); ?></p>
            <!-- You can add more dynamic stats based on plugin data -->
        </div>

        <div class="awr-dashboard-section">
            <h2><?php esc_html_e('Recent Recommendations', 'advanced-woo-recommendations'); ?></h2>
            <p><?php esc_html_e('Here you can see the most recent recommendations delivered to users.', 'advanced-woo-recommendations'); ?></p>
            <?php if (!empty($recent_recommendations)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Product', 'advanced-woo-recommendations'); ?></th>
                            <th><?php esc_html_e('User', 'advanced-woo-recommendations'); ?></th>
                            <th><?php esc_html_e('Date', 'advanced-woo-recommendations'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_recommendations as $recommendation) : ?>
                            <tr>
                                <td>
                                    <?php
                                        $product = wc_get_product($recommendation['product_id']);
                                        if ($product) {
                                            echo '<a href="' . esc_url(get_edit_post_link($recommendation['product_id'])) . '">' . esc_html($product->get_name()) . '</a>';
                                        } else {
                                            echo esc_html__('Product not found', 'advanced-woo-recommendations');
                                        }
                                    ?>
                                </td>
                                <td><?php echo esc_html($recommendation['user_id'] ? get_userdata($recommendation['user_id'])->user_login : __('Guest', 'advanced-woo-recommendations')); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($recommendation['date']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No recent recommendations found.', 'advanced-woo-recommendations'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// Function to get total recommendations (replace with actual logic)
/**
 * Get the total number of recommendations.
 *
 * @return int The total number of recommendations.
 */
function awr_get_total_recommendations(): int {
    // Example: Replace with your actual database query or logic
    return 1500;
}

function awr_get_total_sales_from_recommendations($start_date = null, $end_date = null) {
    try {
        // Check cache first
        $cache_key = 'awr_total_sales_' . $start_date . '_' . $end_date;
        $total_sales = get_transient($cache_key);

        if ($total_sales === false) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'awr_recommendations';

            // Build query with date filtering
            $query = "SELECT SUM(meta_value)
                FROM {$wpdb->prefix}wc_order_items AS items
                JOIN {$wpdb->prefix}wc_order_itemmeta AS meta ON items.order_item_id = meta.order_item_id
                WHERE meta.meta_key = '_line_total'
                AND items.order_item_id IN (
                    SELECT order_item_id FROM {$table_name}
                )";

            if ($start_date && $end_date) {
                // Sanitize dates
                $start_date = sanitize_text_field($start_date);
                $end_date = sanitize_text_field($end_date);
                
                $query .= $wpdb->prepare(" AND items.order_id IN (
                    SELECT post_id FROM {$wpdb->postmeta} 
                    WHERE meta_key = '_paid_date'
                    AND meta_value BETWEEN %s AND %s
                )", $start_date, $end_date);
            }

            $total_sales = $wpdb->get_var($query);
            
            // Cache results
            set_transient($cache_key, $total_sales, HOUR_IN_SECONDS);
        }

        return $total_sales ? floatval($total_sales) : 0;

    } catch (Exception $e) {
        error_log('Failed to get total sales from recommendations: ' . $e->getMessage());
        return 0;
    }
}

// Function to get recent recommendations (replace with actual logic)
function awr_get_recent_recommendations($limit = 5) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'awr_recommendations';

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT product_id, user_id, date FROM {$table_name} ORDER BY date DESC LIMIT %d",
            $limit
        ),
        ARRAY_A
    );

    return $results;
}


// Add the dashboard to the admin menu
function awr_add_dashboard_page() {
    add_menu_page(
        __('AWR Dashboard', 'advanced-woo-recommendations'),
        __('AWR Dashboard', 'advanced-woo-recommendations'),
        'manage_options',
        'awr-dashboard',
        'awr_dashboard_page',
        'dashicons-chart-bar',
        80
    );
}
add_action('admin_menu', 'awr_add_dashboard_page');