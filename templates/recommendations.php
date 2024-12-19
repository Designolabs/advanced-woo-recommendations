<?php
// Get current user ID (WooCommerce user or guest)
$user_id = get_current_user_id();
if (!$user_id) {
    $user_id = 'guest_' . session_id(); // For guests, use a session-based ID
}

// Fetch recommendations from Recombee API
$recommendations = awr_get_recommendations($user_id);

if (!empty($recommendations)) : ?>
    <div class="awr-recommendations-grid">
        <?php foreach ($recommendations as $item) :
            // Get WooCommerce product by ID
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
    <style>
        .awr-recommendations-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .awr-product {
            text-align: center;
        }
    </style>
<?php else : ?>
    <p>No recommendations available at this time.</p>
<?php endif; ?>

<div id="awr-recommendations-root">
    <!-- React app will be rendered here -->
</div>
