<?php
namespace AdvancedWooRecommendations;

// Ensure WordPress is loaded
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Remove namespace from WordPress/WooCommerce functions to fix undefined function errors
use function add_action;
use function wp_nonce_field;
use function get_current_user_id;
use function sanitize_key;
use function get_transient;
use function set_transient;
use function esc_html_e;
use function wc_get_product;
use function esc_url;
use function get_permalink;
use function esc_html;
use function wp_kses_post;
use function get_option;
use function absint;
use function wp_enqueue_style;
use function wp_add_inline_style;
use function shortcode_atts;
use function wp_reset_postdata;
use function esc_html__;
use function woocommerce_get_product_thumbnail;
use function get_the_title;
use function add_shortcode;

// Hook into WooCommerce to show recommendations in the cart and checkout pages
add_action('woocommerce_cart_collaterals', __NAMESPACE__ . '\display_cart_recommendations');
add_action('woocommerce_after_checkout_form', __NAMESPACE__ . '\display_cart_recommendations');

/**
 * Display product recommendations in cart and checkout pages
 * 
 * @return void
 */
function display_cart_recommendations(): void {
    // Add nonce verification for security
    wp_nonce_field('awr_recommendations', 'awr_recommendations_nonce');
    
    // Get user ID with fallback to guest session
    $user_id = get_current_user_id();
    if (!$user_id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $user_id = sanitize_key('guest_' . session_id());
    }

    // Early return if cart is empty
    if (empty(WC()->cart->get_cart())) {
        return;
    }

    // Get cached recommendations with expiration
    $cache_key = sprintf('awr_recommendations_%s', $user_id);
    $recommendations = get_transient($cache_key);
    
    // Fetch fresh recommendations if cache is empty or expired
    if (false === $recommendations) {
        $recommendations = awr_get_recommendations($user_id, 6);
        // Cache for 1 hour by default, filterable
        $cache_duration = apply_filters('awr_recommendations_cache_duration', HOUR_IN_SECONDS);
        set_transient($cache_key, $recommendations, $cache_duration);
    }

    if (!empty($recommendations)) {
        render_recommendations_grid($recommendations);
    }
}

/**
 * Render the recommendations grid
 * 
 * @param array $recommendations Array of product recommendations
 * @return void
 */
function render_recommendations_grid(array $recommendations): void {
    // Allow filtering number of columns
    $columns = apply_filters('awr_recommendations_columns', 3);
    
    ?>
    <div class="awr-cart-recommendations">
        <h3><?php esc_html_e('Recommended for You', 'advanced-woo-recommendations'); ?></h3>
        <div class="awr-recommendations-grid" data-columns="<?php echo esc_attr($columns); ?>">
            <?php 
            foreach ($recommendations as $item): 
                if (!isset($item['id']) || !is_numeric($item['id'])) {
                    continue;
                }
                
                $product = wc_get_product($item['id']);
                if (!$product || !$product->is_visible()) {
                    continue;
                }
                
                render_product_card($product);
            endforeach; 
            ?>
        </div>
    </div>
    <?php
    add_inline_style();
}

/**
 * Render individual product card
 * 
 * @param \WC_Product $product WooCommerce product object
 * @return void
 */
function render_product_card(\WC_Product $product): void {
    $product_id = $product->get_id();
    $product_url = get_permalink($product_id);
    $add_to_cart_url = $product->add_to_cart_url();
    $price_html = $product->get_price_html();
    
    ?>
    <div class="awr-product" data-product-id="<?php echo esc_attr($product_id); ?>">
        <a href="<?php echo esc_url($product_url); ?>" class="product-link">
            <?php 
            echo $product->get_image('woocommerce_thumbnail', [
                'class' => 'product-thumbnail',
                'loading' => 'lazy'
            ]); 
            ?>
            <h2 class="product-title"><?php echo esc_html($product->get_name()); ?></h2>
            <p class="awr-price"><?php echo wp_kses_post($price_html); ?></p>
        </a>
        <?php if ($product->is_in_stock()): ?>
            <button type="button" 
                    class="add-to-cart-btn"
                    data-product-id="<?php echo esc_attr($product_id); ?>"
                    data-url="<?php echo esc_url($add_to_cart_url); ?>">
                <?php esc_html_e('Add to Cart', 'advanced-woo-recommendations'); ?>
            </button>
        <?php else: ?>
            <p class="out-of-stock">
                <?php esc_html_e('Out of Stock', 'advanced-woo-recommendations'); ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Add inline styles for recommendations
 */
function add_inline_style(): void {
    $primary_color = get_option('awr_primary_color', '#0071a1');
    $text_color = get_option('awr_text_color', '#333333');
    ?>
    <style>
        .awr-cart-recommendations {
            margin: 2rem 0;
            padding: 1rem;
        }
        .awr-recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 1rem;
        }
        .awr-product {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
            padding: 1rem;
        }
        .awr-product:hover {
            transform: translateY(-4px);
        }
        .product-link {
            text-decoration: none;
            color: <?php echo esc_attr($text_color); ?>;
        }
        .add-to-cart-btn {
            width: 100%;
            padding: 0.75rem;
            background: <?php echo esc_attr($primary_color); ?>;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: opacity 0.2s ease;
        }
        .add-to-cart-btn:hover {
            opacity: 0.9;
        }
        .out-of-stock {
            color: #e53e3e;
            text-align: center;
            margin: 0.5rem 0;
        }
    </style>
    <?php
}

/**
 * Output dynamic styles in page head
 */
function dynamic_styles(): void {
    $styles = get_dynamic_styles();
    
    // Allow filtering of styles
    $styles = apply_filters('awr_dynamic_styles', $styles);
    
    printf(
        '<style>:root {%s}</style>',
        implode(';', array_map(function($key, $value) {
            return sprintf('--%s:%s', esc_attr($key), esc_attr($value));
        }, array_keys($styles), $styles))
    );
}
add_action('wp_head', __NAMESPACE__ . '\dynamic_styles');

/**
 * Get dynamic style variables
 * 
 * @return array
 */
function get_dynamic_styles(): array {
    return [
        'awr-primary-color' => get_option('awr_primary_color', '#0071a1'),
        'awr-secondary-color' => get_option('awr_secondary_color', '#f1f1f1'),
        'awr-text-color' => get_option('awr_text_color', '#333333'),
        'awr-font-family' => get_option('awr_font_family', 'system-ui, -apple-system, sans-serif'),
        'awr-border-radius' => get_option('awr_border_radius', '8px'),
        'awr-box-shadow' => get_option('awr_box_shadow', '0 2px 4px rgba(0,0,0,0.1)')
    ];
}

/**
 * Enqueue Google Fonts if selected
 */
function enqueue_google_fonts(): void {
    $font_family = get_option('awr_font_family', '');
    
    // Skip if no custom font selected
    if (empty($font_family) || strpos($font_family, ',') === false) {
        return;
    }

    $google_fonts = apply_filters('awr_google_fonts', [
        'Roboto',
        'Open Sans',
        'Lato',
        'Montserrat', 
        'Oswald',
        'Poppins',
        'Raleway',
        'Playfair Display'
    ]);

    $font_name = explode(',', $font_family)[0];
    if (!in_array($font_name, $google_fonts, true)) {
        return;
    }

    $font_url = sprintf(
        'https://fonts.googleapis.com/css2?family=%s:wght@400;500;600;700&display=swap',
        str_replace(' ', '+', $font_name)
    );
    
    wp_enqueue_style(
        'awr-google-font', 
        esc_url($font_url),
        [],
        AWR_VERSION
    );
}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_google_fonts');

/**
 * Output dynamic layout styles
 */
function dynamic_layout_styles(): string {
    $layout = get_option('awr_layout_style', 'grid');
    $columns = absint(get_option('awr_product_columns', 4));
    $spacing = absint(get_option('awr_product_spacing', 20));
    $max_width = absint(get_option('awr_max_width', 1200));

    return sprintf(
        '.awr-recommendations-grid{
            max-width: %3$dpx;
            margin: 0 auto;
            display: %1$s;
            grid-template-columns: repeat(%2$d, 1fr);
            gap: %4$dpx;
        }
        @media (max-width: 768px) {
            .awr-recommendations-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 480px) {
            .awr-recommendations-grid {
                grid-template-columns: 1fr;
            }
        }',
        $layout === 'carousel' ? 'flex' : 'grid',
        $columns,
        $max_width,
        $spacing
    );
}

/**
 * Output dynamic color styles
 */
function dynamic_color_styles(): string {
    $colors = [
        'bg' => get_option('awr_bg_color', '#ffffff'),
        'text' => get_option('awr_text_color', '#333333'),
        'button' => get_option('awr_button_color', '#0071a1'),
        'button_hover' => get_option('awr_button_hover_color', '#005077'),
        'price' => get_option('awr_price_color', '#e53e3e'),
        'sale' => get_option('awr_sale_badge_color', '#e53e3e')
    ];

    return sprintf(
        '.awr-recommendations-grid{background:%1$s;color:%2$s}
        .product-title{color:%2$s}
        .awr-price{color:%5$s}
        .add-to-cart-btn{background:%3$s}
        .add-to-cart-btn:hover{background:%4$s}
        .sale-badge{background:%6$s}',
        esc_attr($colors['bg']),
        esc_attr($colors['text']),
        esc_attr($colors['button']),
        esc_attr($colors['button_hover']),
        esc_attr($colors['price']),
        esc_attr($colors['sale'])
    );
}

/**
 * Product recommendations shortcode handler
 * 
 * @param array $atts Shortcode attributes
 * @return string Rendered HTML
 */
function product_recommendations_shortcode(array $atts = []): string {
    $atts = shortcode_atts([
        'per_page' => 12,
        'orderby' => 'date',
        'order' => 'DESC',
        'category' => '',
        'tag' => '',
        'featured' => false
    ], $atts, 'product_recommendations');

    $query_args = [
        'post_type' => 'product',
        'posts_per_page' => absint($atts['per_page']),
        'orderby' => sanitize_key($atts['orderby']),
        'order' => in_array(strtoupper($atts['order']), ['ASC', 'DESC'], true) ? strtoupper($atts['order']) : 'DESC',
        'post_status' => 'publish',
        'tax_query' => []
    ];

    // Add category filter
    if (!empty($atts['category'])) {
        $query_args['tax_query'][] = [
            'taxonomy' => 'product_cat',
            'field' => 'slug',
            'terms' => array_map('sanitize_title', explode(',', $atts['category']))
        ];
    }

    // Add tag filter
    if (!empty($atts['tag'])) {
        $query_args['tax_query'][] = [
            'taxonomy' => 'product_tag',
            'field' => 'slug',
            'terms' => array_map('sanitize_title', explode(',', $atts['tag']))
        ];
    }

    // Add featured products filter
    if (filter_var($atts['featured'], FILTER_VALIDATE_BOOLEAN)) {
        $query_args['tax_query'][] = [
            'taxonomy' => 'product_visibility',
            'field' => 'name',
            'terms' => 'featured'
        ];
    }

    $query = new \WP_Query($query_args);
    ob_start();

    if ($query->have_posts()) {
        $layout = get_option('awr_layout_style', 'grid');
        printf(
            '<div class="awr-recommendations-wrapper"><div class="awr-recommendations-%s">',
            esc_attr($layout === 'carousel' ? 'carousel' : 'grid')
        );

        while ($query->have_posts()) {
            $query->the_post();
            render_recommendation_item();
        }

        echo '</div></div>';
        wp_reset_postdata();
    } else {
        printf(
            '<p class="awr-no-recommendations">%s</p>',
            esc_html__('No recommendations available at the moment.', 'advanced-woo-recommendations')
        );
    }

    return ob_get_clean();
}
add_shortcode('product_recommendations', __NAMESPACE__ . '\product_recommendations_shortcode');

/**
 * Render individual recommendation item
 */
function render_recommendation_item(): void {
    global $product;
    if (!$product || !($product instanceof \WC_Product)) {
        return;
    }

    $product_id = $product->get_id();
    ?>
    <div class="awr-recommendation-item" data-product-id="<?php echo esc_attr($product_id); ?>">
        <a href="<?php echo esc_url(get_permalink()); ?>" class="product-link">
            <?php 
            echo woocommerce_get_product_thumbnail('woocommerce_thumbnail', [
                'class' => 'product-image',
                'loading' => 'lazy'
            ]); 
            ?>
            <h3 class="product-title"><?php echo esc_html(get_the_title()); ?></h3>
        </a>
        <div class="product-details">
            <span class="product-price"><?php echo wp_kses_post($product->get_price_html()); ?></span>
            <?php if ($product->is_on_sale()): ?>
                <span class="sale-badge">
                    <?php esc_html_e('Sale!', 'advanced-woo-recommendations'); ?>
                </span>
            <?php endif; ?>
        </div>
        <?php if ($product->is_in_stock()): ?>
            <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" 
               class="add-to-cart-btn"
               data-product-id="<?php echo esc_attr($product_id); ?>"
               data-quantity="1"
               data-nonce="<?php echo wp_create_nonce('add-to-cart-' . $product_id); ?>">
                <?php esc_html_e('Add to Cart', 'advanced-woo-recommendations'); ?>
            </a>
        <?php else: ?>
            <p class="out-of-stock">
                <?php esc_html_e('Out of Stock', 'advanced-woo-recommendations'); ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Enqueue dynamic styles
 */
function get_dynamic_styles_output(): void {
    wp_enqueue_style(
        'awr-recommendations-style',
        plugins_url('assets/css/recommendations.css', AWR_PLUGIN_FILE),
        [],
        AWR_VERSION
    );
    
    $styles = dynamic_layout_styles() . dynamic_color_styles();
    $styles = apply_filters('awr_inline_styles', $styles);
    
    wp_add_inline_style('awr-recommendations-style', $styles);
}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\get_dynamic_styles_output', 20);