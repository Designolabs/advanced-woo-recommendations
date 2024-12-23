<?php
namespace AdvancedWooRecommendations;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Send personalized product recommendation email to a user
 *
 * @param int $user_id WordPress user ID
 * @return bool Whether the email was sent successfully
 */
function awr_send_recommendation_email(int $user_id): bool {
    // Validate user exists
    $user = get_userdata($user_id);
    if (!$user || !$user->exists()) {
        return false;
    }

    // Get recommendations with error handling
    try {
        $recommendations = awr_get_recommendations($user_id, 6);
    } catch (\Exception $e) {
        error_log(sprintf('Failed to get recommendations for user %d: %s', $user_id, $e->getMessage()));
        return false;
    }

    if (empty($recommendations)) {
        return false;
    }

    // Load email template
    ob_start();
    include AWR_PLUGIN_PATH . 'templates/email-template.php';
    $email_template = ob_get_clean();

    // Build products HTML
    $products_html = '';
    foreach ($recommendations as $item) {
        if (!isset($item['id']) || !is_numeric($item['id'])) {
            continue;
        }

        $product = wc_get_product($item['id']);
        if (!$product || !$product->is_visible()) {
            continue;
        }

        $products_html .= sprintf(
            '<div class="product">
                <div class="product-image">
                    <a href="%1$s">%2$s</a>
                </div>
                <h3><a href="%1$s">%3$s</a></h3>
                <p class="price">%4$s</p>
                <a href="%1$s" class="button">%5$s</a>
            </div>',
            esc_url(get_permalink($product->get_id())),
            $product->get_image('woocommerce_thumbnail'),
            esc_html($product->get_name()),
            wp_kses_post($product->get_price_html()),
            esc_html__('View Product', 'advanced-woo-recommendations')
        );
    }

    // Replace template variables
    $email_content = str_replace(
        ['{customer_name}', '{products_grid}'],
        [esc_html($user->display_name), $products_html],
        $email_template
    );

    $subject = apply_filters(
        'awr_recommendation_email_subject',
        sprintf(
            /* translators: %s: Site name */
            __('Personalized Recommendations from %s', 'advanced-woo-recommendations'),
            get_bloginfo('name')
        )
    );

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        sprintf('From: %s <%s>', get_bloginfo('name'), get_option('admin_email'))
    ];

    return wp_mail($user->user_email, $subject, $email_content, $headers);
}

/**
 * Send recommendation email after order completion
 *
 * @param int $order_id WooCommerce order ID
 * @return void
 */
function awr_send_recommendation_email_on_order(int $order_id): void {
    // Get order with validation
    $order = wc_get_order($order_id);
    if (!$order instanceof \WC_Order) {
        return;
    }

    $user_id = $order->get_user_id();
    if (!$user_id) {
        return;
    }

    // Add delay to allow for order processing
    $delay = (int) apply_filters('awr_recommendation_email_delay', 24 * HOUR_IN_SECONDS);
    
    if ($delay > 0) {
        wp_schedule_single_event(time() + $delay, 'awr_send_delayed_recommendation_email', [$user_id]);
    } else {
        awr_send_recommendation_email($user_id);
    }
}
add_action('woocommerce_order_status_completed', __NAMESPACE__ . '\awr_send_recommendation_email_on_order', 10, 1);
