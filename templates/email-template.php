<?php
namespace AdvancedWooRecommendations;
if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php esc_html_e('Product Recommendations', 'advanced-woo-recommendations'); ?></title>
    <style>
        /* Reset styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #2d3748;
            background-color: #f7fafc;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .email-wrapper {
            width: 100%;
            max-width: 680px;
            margin: 0 auto;
            padding: 20px;
        }

        .recommendation-container {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 40px;
            margin: 20px 0;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        h2 {
            color: #1a202c;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
            position: relative;
            display: inline-block;
        }

        h2:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3182ce, #63b3ed);
            border-radius: 2px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 40px;
        }

        .product {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }

        .product:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.08);
        }

        .product-image {
            position: relative;
            padding-top: 75%;
            overflow: hidden;
        }

        .product-image img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px 12px 0 0;
        }

        .product-content {
            padding: 20px;
        }

        .product-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            font-size: 20px;
            font-weight: 700;
            color: #3182ce;
        }

        .footer-text {
            text-align: center;
            color: #718096;
            font-size: 16px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #1a202c;
                color: #e2e8f0;
            }
            
            .recommendation-container {
                background-color: #2d3748;
            }

            .product {
                background: #2d3748;
            }

            .product-title {
                color: #e2e8f0;
            }

            h2 {
                color: #e2e8f0;
            }
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .products-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .recommendation-container {
                padding: 30px 20px;
            }

            h2 {
                font-size: 24px;
            }
        }

        @media screen and (max-width: 480px) {
            .email-wrapper {
                padding: 10px;
            }

            .product-content {
                padding: 15px;
            }

            .product-title {
                font-size: 16px;
            }

            .product-price {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="recommendation-container">
            <div class="header">
                <h2><?php esc_html_e('Recommended Products for You', 'advanced-woo-recommendations'); ?></h2>
            </div>

            <div class="products-grid">
                <?php foreach ($recommendations as $product) : ?>
                    <div class="product">
                        <div class="product-image">
                            <img src="<?php echo esc_url($product['image']); ?>" alt="<?php echo esc_attr($product['title']); ?>">
                        </div>
                        <div class="product-content">
                            <p class="product-title"><?php echo esc_html($product['title']); ?></p>
                            <p class="product-price"><?php echo wc_price($product['price']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="footer-text"><?php esc_html_e('Thank you for shopping with us!', 'advanced-woo-recommendations'); ?></p>
        </div>
    </div>
</body>
</html>