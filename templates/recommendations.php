<?php
namespace AdvancedWooRecommendations;

// Verify nonce for security
if (!wp_verify_nonce(sanitize_key($_REQUEST['nonce'] ?? ''), 'awr_recommendations')) {
    return;
}

// Get current user ID with fallback to guest session
$user_id = get_current_user_id();
if (!$user_id) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $user_id = sanitize_key('guest_' . session_id());
}

// Get cached recommendations
$cache_key = sprintf('awr_recommendations_%s', $user_id);
$recommendations = get_transient($cache_key);

// Fetch fresh recommendations if cache is empty
if (false === $recommendations) {
    $recommendations = awr_get_recommendations($user_id, 6);
    set_transient($cache_key, $recommendations, HOUR_IN_SECONDS);
}

if (!empty($recommendations)) : ?>
    <div class="awr-recommendations-grid" data-user-id="<?php echo esc_attr($user_id); ?>">
        <?php foreach ($recommendations as $item) :
            if (!isset($item['id']) || !is_numeric($item['id'])) {
                continue;
            }
            
            $product = wc_get_product($item['id']);
            if (!$product) {
                continue;
            }
            ?>
            <div class="awr-product">
                <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>">
                    <?php echo wp_kses_post($product->get_image('woocommerce_thumbnail')); ?>
                    <h2><?php echo esc_html($product->get_name()); ?></h2>
                    <p class="awr-price"><?php echo wp_kses_post($product->get_price_html()); ?></p>
                </a>
                <button 
                    class="awr-add-to-cart-btn"
                    data-product-id="<?php echo esc_attr($product->get_id()); ?>"
                    data-nonce="<?php echo wp_create_nonce('add_to_cart'); ?>"
                >
                    <?php esc_html_e('Add to Cart', 'advanced-woo-recommendations'); ?>
                </button>
                <?php if ($product->is_on_sale()) : ?>
                    <span class="awr-sale-badge">
                        <?php esc_html_e('Sale!', 'advanced-woo-recommendations'); ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <style>
        .awr-recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        .awr-product {
            text-align: center;
            background: #fff;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
            position: relative;
        }
        .awr-product:hover {
            transform: translateY(-5px);
        }
        .awr-add-to-cart-btn {
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: var(--awr-primary-color, #0071a1);
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .awr-add-to-cart-btn:hover {
            background: var(--awr-button-hover-color, #005077);
        }
        .awr-sale-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #e53e3e;
            color: #fff;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }
    </style>
<?php else : ?>
    <p class="awr-no-recommendations">
        <?php esc_html_e('No recommendations available at this time. Please check back later.', 'advanced-woo-recommendations'); ?>
    </p>
<?php endif; ?>

<div id="awr-recommendations-root" data-user-id="<?php echo esc_attr($user_id); ?>">
    <!-- React app will be rendered here -->
</div>
