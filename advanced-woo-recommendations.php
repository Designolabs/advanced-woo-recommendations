<?php
namespace AdvancedWooRecommendations;

/**
 * Plugin Name: Advanced Woo Recommendations
 * Plugin URI: https://designolabs.com
 * Description: A highly advanced WooCommerce recommendation engine for personalized product suggestions.
 * Version: 1.0.0
 * Author: Designolabs Studio
 * Author URI: https://designolabs.com
 * License: GPL-2.0+
 * Text Domain: advanced-woo-recommendations
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.6
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'awr_load_textdomain');
function awr_load_textdomain() {
    load_plugin_textdomain('advanced-woo-recommendations', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

add_shortcode('product_recommendations', 'awr_product_recommendations_shortcode');
function awr_product_recommendations_shortcode($atts) {
    // Check the current language
    if (function_exists('pll_current_language')) {
        $current_language = pll_current_language();
        
        // Use the language-specific logic if needed
        if ($current_language == 'es') {
            return __('Productos Recomendados', 'advanced-woo-recommendations');
        } else {
            return __('Recommended Products', 'advanced-woo-recommendations');
        }
    }
}

$recommended_text = sprintf(__('You may like these products, %s', 'advanced-woo-recommendations'), $user_name);
echo $recommended_text;

function awr_product_recommendations_shortcode($atts) {
    return __('Recommended Products', 'advanced-woo-recommendations');
}
add_shortcode('product_recommendations', 'awr_product_recommendations_shortcode');


// Import WordPress globals
use function wp_enqueue_style as wp_enqueue_style;
use function wp_enqueue_script as wp_enqueue_script;
use function add_action as wp_add_action;
use function add_shortcode as wp_add_shortcode;
use function plugin_dir_path;
use function plugin_dir_url;
use function wp_localize_script;
use function esc_url;
use function rest_url;
use function get_current_user_id;

// Define plugin constants
define(__NAMESPACE__ . '\AWR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define(__NAMESPACE__ . '\AWR_PLUGIN_URL', plugin_dir_url(__FILE__));
if (!defined(__NAMESPACE__ . '\AWR_VERSION')) {
    define(__NAMESPACE__ . '\AWR_VERSION', '1.0.0');
}

/**
 * Class Plugin
 * Main plugin class to handle initialization
 */
final class Plugin {
    /**
     * @var Plugin Single instance of the plugin
     */
    private static $instance = null;

    /**
     * Get plugin instance
     * @return Plugin
     */
    public static function get_instance(): Plugin {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Initialize plugin
     */
    private function init(): void {
        // Check dependencies
        if (!$this->check_dependencies()) {
            return;
        }

        // Load required files
        $this->load_files();

        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Check plugin dependencies
     */
    private function check_dependencies(): bool {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-error">
                    <p><?php _e('Advanced Woo Recommendations requires WooCommerce to be installed and active.', 'advanced-woo-recommendations'); ?></p>
                </div>
                <?php
            });
            return false;
        }
        return true;
    }

    /**
     * Load required files
     */
    private function load_files(): void {
        $includes = [
            'includes/admin.php',
            'includes/api.php',
            'includes/frontend.php',
            'includes/email.php'
        ];

        foreach ($includes as $file) {
            $filepath = AWR_PLUGIN_DIR . $file;
            if (file_exists($filepath)) {
                require_once $filepath;
            } else {
                error_log(sprintf(
                    '[Advanced Woo Recommendations] File not found: %s',
                    $filepath
                ));
            }
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        wp_add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        wp_add_action('wp_enqueue_scripts', [$this, 'enqueue_react_assets']);
        wp_add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        wp_add_shortcode('product_recommendations', [$this, 'display_recommendations']);
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets(): void {
        wp_enqueue_style(
            'awr-styles', 
            AWR_PLUGIN_URL . 'assets/css/style.css',
            [],
            AWR_VERSION
        );
        
        wp_enqueue_script(
            'awr-scripts',
            AWR_PLUGIN_URL . 'assets/js/script.js',
            ['jquery'],
            AWR_VERSION,
            true
        );
    }

    /**
     * Enqueue React assets
     */
    public function enqueue_react_assets(): void {
        wp_enqueue_script('react', 'https://unpkg.com/react@17/umd/react.production.min.js', [], '17.0.0', true);
        wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@17/umd/react-dom.production.min.js', ['react'], '17.0.0', true);

        wp_enqueue_script(
            'awr-react-app',
            AWR_PLUGIN_URL . 'assets/js/react-app.js',
            ['react', 'react-dom', 'wp-element'],
            AWR_VERSION,
            true
        );

        wp_localize_script('awr-react-app', 'awr_data', [
            'apiEndpoint' => esc_url(rest_url('awr/v1/recommendations')),
            'userId' => get_current_user_id() ?: 'guest_' . session_id(),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    /**
     * Display recommendations shortcode
     */
    public function display_recommendations(): string {
        ob_start();
        include AWR_PLUGIN_DIR . 'templates/recommendations.php';
        return ob_get_clean();
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook_suffix): void {
        if ('settings_page_awr-settings-page' !== $hook_suffix) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script(
            'awr-admin-scripts',
            AWR_PLUGIN_URL . 'assets/js/admin-script.js',
            ['wp-color-picker'],
            AWR_VERSION,
            true
        );
    }
}

// Initialize plugin
add_action('plugins_loaded', function() {
    Plugin::get_instance();
});