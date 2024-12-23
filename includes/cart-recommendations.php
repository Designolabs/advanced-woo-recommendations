<?php
namespace AdvancedWooRecommendations;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Get product recommendations based on cart contents
 *
 * @return void
 */
function awr_get_cart_recommendations(): void {
    // Verify nonce for security
    if (!wp_verify_nonce(sanitize_key($_REQUEST['nonce'] ?? ''), 'awr_recommendations')) {
        return;
    }

    // Get cart items
    $cart = WC()->cart;
    if (!$cart) {
        return;
    }

    $cart_items = $cart->get_cart();
    
    // Check if there are items in the cart
    if (empty($cart_items)) {
        printf(
            '<p>%s</p>',
            esc_html__('Your cart is empty. Add some products to see recommendations.', 'advanced-woo-recommendations')
        );
        return;
    }

    // Get product IDs from cart
    $product_ids = array_map(function($item) {
        return $item['product_id'];
    }, $cart_items);

    // Get recommendations from Recombee API
    $user_id = get_current_user_id() ?: 'guest_' . session_id();
    $recommendations = awr_get_recommendations($user_id, 6);

    // Filter out products already in cart
    $recommendations = array_filter($recommendations, function($item) use ($product_ids) {
        return !in_array($item['id'], $product_ids, true);
    });

    // Output recommendations
    if (!empty($recommendations)) {
        echo '<div class="awr-recommendations">';
        foreach ($recommendations as $item) {
            $product = wc_get_product($item['id']);
            if (!$product instanceof \WC_Product) {
                continue;
            }

            printf(
                '<div class="awr-recommended-product">
                    <a href="%1$s">
                        <img src="%2$s" alt="%3$s" />
                        <p>%4$s</p>
                        <p>%5$s</p>
                    </a>
                    <button 
                        class="awr-add-to-cart" 
                        data-product-id="%6$d"
                        data-nonce="%7$s"
                    >%8$s</button>
                </div>',
                esc_url(get_permalink($product->get_id())),
                esc_url($product->get_image()),
                esc_attr($product->get_name()),
                esc_html($product->get_name()),
                wp_kses_post($product->get_price_html()),
                absint($product->get_id()),
                wp_create_nonce('add_to_cart'),
                esc_html__('Add to Cart', 'advanced-woo-recommendations')
            );
        }
        echo '</div>';
    } else {
        printf(
            '<p>%s</p>',
            esc_html__('No recommendations available at this time.', 'advanced-woo-recommendations')
        );
    }
}

/**
 * Ajax handler for cart recommendations
 *
 * @return void
 */
function awr_fetch_cart_recommendations(): void {
    check_ajax_referer('awr_recommendations', 'nonce');

    ob_start();
    awr_get_cart_recommendations();
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}

add_action('wp_ajax_awr_fetch_cart_recommendations', __NAMESPACE__ . '\awr_fetch_cart_recommendations');
add_action('wp_ajax_nopriv_awr_fetch_cart_recommendations', __NAMESPACE__ . '\awr_fetch_cart_recommendations');