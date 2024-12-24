<?php
// Hook into WooCommerce to show recommendations in the cart and checkout pages
add_action('woocommerce_cart_collaterals', 'awr_display_cart_recommendations');
add_action('woocommerce_after_checkout_form', 'awr_display_cart_recommendations');

// Function to display cart-based recommendations
function awr_display_cart_recommendations() {
    // Get current user/cart session ID
    $user_id = get_current_user_id();
    if (!$user_id) {
        $user_id = 'guest_' . session_id();
    }

    // Fetch cart items
    $cart_items = WC()->cart->get_cart();
    if (!$cart_items) return;

    // Fetch recommendations from Recombee based on cart items
    $recommendations = awr_get_recommendations($user_id, 6); // Limit to 6 recommendations

    if (!empty($recommendations)) : ?>
        <div class="awr-cart-recommendations">
            <h3>Recommended for You</h3>
            <div class="awr-recommendations-grid">
                <?php foreach ($recommendations as $item) :
                    $product = wc_get_product($item['id']);
                    if ($product) : ?>
                        <div class="awr-product">
                            <a href="<?php echo get_permalink($product->get_id()); ?>">
                                <?php echo $product->get_image('woocommerce_thumbnail'); ?>
                                <h2><?php echo $product->get_name(); ?></h2>
                                <p class="awr-price"><?php echo $product->get_price_html(); ?></p>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <style>
            .awr-cart-recommendations {
                margin-top: 20px;
            }
        </style>
    <?php endif;
}


function awr_dynamic_styles() {
    // Get the custom styles from settings
    $primary_color = get_option('awr_primary_color', '#0071a1');
    $secondary_color = get_option('awr_secondary_color', '#f1f1f1');
    $font_family = get_option('awr_font_family', 'Arial, sans-serif');

    // Output the custom styles dynamically in the head
    echo "
        <style>
        :root {
            --awr-primary-color: $primary_color;
            --awr-secondary-color: $secondary_color;
            --awr-font-family: $font_family;
        }
        </style>
    ";
}
add_action('wp_head', 'awr_dynamic_styles');


function awr_enqueue_google_fonts() {
    $font_family = get_option('awr_font_family', 'Arial, sans-serif');

    // Only load Google Fonts if a Google Font is selected
    $google_fonts_list = array(
        'Roboto, sans-serif',
        'Open Sans, sans-serif',
        'Lato, sans-serif',
        'Montserrat, sans-serif',
        'Oswald, sans-serif',
        'Poppins, sans-serif',
        'Raleway, sans-serif',
        'Playfair Display, serif',
    );

    if (in_array($font_family, $google_fonts_list)) {
        // Get the font name (e.g., 'Roboto') without the CSS font stack
        $font_name = explode(',', $font_family)[0];
        $font_name = str_replace(' ', '+', $font_name); // Format for Google Fonts URL

        // Enqueue Google Font
        wp_enqueue_style('awr-google-font', 'https://fonts.googleapis.com/css2?family=' . esc_attr($font_name) . '&display=swap');
    }
}
add_action('wp_enqueue_scripts', 'awr_enqueue_google_fonts');


function awr_dynamic_layout_styles() {
    // Get layout settings from admin
    $layout_style = get_option('awr_layout_style', 'grid');
    $columns = get_option('awr_product_columns', 4);
    $spacing = get_option('awr_product_spacing', 20);

    // Apply dynamic styles based on admin settings
    echo "
    <style>
        .awr-recommendations-grid {
            display: " . ($layout_style === 'carousel' ? 'block' : 'grid') . ";
            grid-template-columns: repeat($columns, 1fr);
            gap: " . esc_attr($spacing) . "px;
        }
    </style>
    ";
}
add_action('wp_head', 'awr_dynamic_layout_styles');

function awr_dynamic_color_styles() {
    // Get color settings from admin
    $bg_color = get_option('awr_bg_color', '#ffffff');
    $text_color = get_option('awr_text_color', '#000000');
    $button_color = get_option('awr_button_color', '#0071a1');
    $button_hover_color = get_option('awr_button_hover_color', '#005077');

    // Output dynamic CSS
    echo "
    <style>
        .awr-recommendations-grid {
            background-color: " . esc_attr($bg_color) . ";
            color: " . esc_attr($text_color) . ";
        }
        .awr-recommendations-grid .product-title {
            color: " . esc_attr($text_color) . ";
        }
        .awr-recommendations-grid .add-to-cart-btn {
            background-color: " . esc_attr($button_color) . ";
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: opacity 0.2s ease;
        }
        .awr-recommendations-grid .add-to-cart-btn:hover {
            background-color: " . esc_attr($button_hover_color) . ";
        }
    </style>
    ";
}
add_action('wp_head', 'awr_dynamic_color_styles');


function awr_product_recommendations_shortcode($atts) {
    // Get the layout style from plugin settings
    $layout_style = get_option('awr_layout_style', 'grid');  // Default to 'grid'

    // Set up the arguments for the shortcode (this can be customized further)
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => 12,  // Default number of products per page
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    // Query to get products for recommendations
    $query = new WP_Query($args);

    // Start the output
    ob_start();

    // Check if products were found
    if ($query->have_posts()) {
        echo '<div class="awr-recommendations-wrapper">';

        // Display recommendations based on layout style (grid or carousel)
        if ($layout_style == 'carousel') {
            echo '<div class="awr-recommendations-carousel">';
        } else {
            echo '<div class="awr-recommendations-grid">';
        }

        // Loop through the products
        while ($query->have_posts()) {
            $query->the_post();
            global $product;
            
            echo '<div class="awr-recommendation-item">';
            echo '<a href="' . get_permalink() . '">';
            echo woocommerce_get_product_thumbnail(); // Product image
            echo '<h3 class="product-title">' . get_the_title() . '</h3>';
            echo '</a>';
            echo '<span class="product-price">' . $product->get_price_html() . '</span>';
            echo '<a href="' . esc_url($product->add_to_cart_url()) . '" class="add-to-cart-btn">' . __('Add to Cart', 'advanced-woo-recommendations') . '</a>';
            echo '</div>';
        }

        // Close the layout wrapper
        echo '</div>';  // .awr-recommendations-wrapper
        echo '</div>';  // .awr-recommendations-carousel or .awr-recommendations-grid

        wp_reset_postdata();
    } else {
        echo '<p>' . __('No recommendations available at the moment.', 'advanced-woo-recommendations') . '</p>';
    }

    // Return the output to be displayed on the page
    return ob_get_clean();
}
add_shortcode('product_recommendations', 'awr_product_recommendations_shortcode');

$atts = shortcode_atts(
    array(
        'per_page' => 12, // Default number of products
    ),
    $atts
);

$args = array(
    'post_type'      => 'product',
    'posts_per_page' => $atts['per_page'],
    'orderby'        => 'date',
    'order'          => 'DESC',
);
