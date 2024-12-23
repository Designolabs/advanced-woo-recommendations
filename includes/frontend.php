<?php
// Display recommendations in the cart and checkout pages
add_action('woocommerce_cart_collaterals', 'awr_display_cart_recommendations');
add_action('woocommerce_after_checkout_form', 'awr_display_cart_recommendations');

function awr_display_cart_recommendations() {
    // Sanitize user ID
    $user_id = get_current_user_id() ?: sanitize_key('guest_' . WC()->session->get_session_id());

    // Early return if the cart is empty
    if (empty(WC()->cart->get_cart())) return;

    // Cache recommendations for 1 hour
    $cache_key = 'awr_recommendations_' . $user_id;
    $recommendations = get_transient($cache_key);
    
    if ($recommendations === false) {
        $recommendations = awr_get_recommendations($user_id, 6);
        set_transient($cache_key, $recommendations, HOUR_IN_SECONDS);
    }

    if (!empty($recommendations)) :
        ?>
        <div class="awr-cart-recommendations">
            <h3><?php _e('Recommended for You', 'advanced-woo-recommendations'); ?></h3>
            <div class="awr-recommendations-grid">
                <?php foreach ($recommendations as $item) :
                    $product = wc_get_product($item['id']);
                    if ($product) : ?>
                        <div class="awr-product">
                            <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>">
                                <?php echo $product->get_image('woocommerce_thumbnail'); ?>
                                <h2><?php echo esc_html($product->get_name()); ?></h2>
                                <p class="awr-price"><?php echo $product->get_price_html(); ?></p>
                            </a>
                        </div>
                    <?php endif;
                endforeach; ?>
            </div>
        </div>
        <?php
    endif;
}

// Dynamic styles and fonts
add_action('wp_head', function () {
    $primary_color = get_option('awr_primary_color', '#0071a1');
    $secondary_color = get_option('awr_secondary_color', '#f1f1f1');
    $font_family = get_option('awr_font_family', 'Arial, sans-serif');
    $layout_style = get_option('awr_layout_style', 'grid');
    $columns = get_option('awr_product_columns', 4);
    $spacing = get_option('awr_product_spacing', 20);
    $bg_color = get_option('awr_bg_color', '#ffffff');
    $text_color = get_option('awr_text_color', '#000000');
    $button_color = get_option('awr_button_color', '#0071a1');
    $button_hover_color = get_option('awr_button_hover_color', '#005077');
    ?>
    <style>
        :root {
            --awr-primary-color: <?php echo esc_attr($primary_color); ?>;
            --awr-secondary-color: <?php echo esc_attr($secondary_color); ?>;
            --awr-font-family: <?php echo esc_attr($font_family); ?>;
        }
        .awr-recommendations-grid {
            display: <?php echo ($layout_style === 'carousel') ? 'block' : 'grid'; ?>;
            grid-template-columns: repeat(<?php echo absint($columns); ?>, 1fr);
            gap: <?php echo absint($spacing); ?>px;
            background-color: <?php echo esc_attr($bg_color); ?>;
            color: <?php echo esc_attr($text_color); ?>;
        }
        .awr-recommendations-grid .add-to-cart-btn {
            background-color: <?php echo esc_attr($button_color); ?>;
            color: #fff;
        }
        .awr-recommendations-grid .add-to-cart-btn:hover {
            background-color: <?php echo esc_attr($button_hover_color); ?>;
        }
    </style>
    <?php
});

add_action('wp_enqueue_scripts', function () {
    $font_family = get_option('awr_font_family', 'Arial, sans-serif');
    $google_fonts_list = [
        'Roboto, sans-serif',
        'Open Sans, sans-serif',
        // Add more fonts if needed
    ];
    if (in_array($font_family, $google_fonts_list)) {
        $font_name = explode(',', $font_family)[0];
        $font_name = str_replace(' ', '+', $font_name);
        wp_enqueue_style('awr-google-font', 'https://fonts.googleapis.com/css2?family=' . esc_attr($font_name) . '&display=swap');
    }
});

// Shortcode for recommendations
add_shortcode('product_recommendations', function ($atts) {
    $atts = shortcode_atts(['per_page' => 12, 'orderby' => 'date', 'order' => 'DESC'], $atts, 'product_recommendations');
    $per_page = absint($atts['per_page']);
    $orderby = sanitize_key($atts['orderby']);
    $order = in_array(strtoupper($atts['order']), ['ASC', 'DESC']) ? strtoupper($atts['order']) : 'DESC';

    $query = new WP_Query([
        'post_type' => 'product',
        'posts_per_page' => $per_page,
        'orderby' => $orderby,
        'order' => $order,
    ]);

    ob_start();
    if ($query->have_posts()) : ?>
        <div class="awr-recommendations-grid">
            <?php while ($query->have_posts()) : $query->the_post();
                global $product; ?>
                <div class="awr-recommendation-item">
                    <a href="<?php echo esc_url(get_permalink()); ?>">
                        <?php echo woocommerce_get_product_thumbnail(); ?>
                        <h3><?php echo esc_html(get_the_title()); ?></h3>
                        <span><?php echo $product->get_price_html(); ?></span>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else : ?>
        <p><?php _e('No recommendations available.', 'advanced-woo-recommendations'); ?></p>
    <?php endif;
    wp_reset_postdata();
    return ob_get_clean();
});
