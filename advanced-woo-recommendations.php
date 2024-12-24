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

// Define constants for plugin version and directory
define('AWR_VERSION', '1.0.0');
define('AWR_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Autoload necessary classes (can be improved using Composer's autoloader)
spl_autoload_register(function ($class) {
    if (strpos($class, 'AdvancedWooRecommendations') === 0) {
        $file = AWR_PLUGIN_DIR . 'includes/' . strtolower(str_replace('\\', DIRECTORY_SEPARATOR, $class)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Main Plugin Class
class Plugin {

    // Initialize the plugin
    public function __construct() {
        // Check WooCommerce dependency
        if (!$this->check_woocommerce()) {
            add_action('admin_notices', [$this, 'woocommerce_notice']);
            return;
        }

        // Load necessary files
        $this->load_dependencies();

        // Register hooks
        $this->register_hooks();
    }

    // Check if WooCommerce is active
    public function check_woocommerce() {
        return class_exists('WooCommerce');
    }

    // WooCommerce dependency notice
    public function woocommerce_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Advanced Woo Recommendations requires WooCommerce to be installed and active.', 'advanced-woo-recommendations'); ?></p>
        </div>
        <?php
    }

    // Load plugin dependencies
    public function load_dependencies() {
        require_once AWR_PLUGIN_DIR . 'includes/admin.php';
        require_once AWR_PLUGIN_DIR . 'includes/api.php';
        require_once AWR_PLUGIN_DIR . 'includes/cart-recommendations.php';
        require_once AWR_PLUGIN_DIR . 'includes/email.php';
        require_once AWR_PLUGIN_DIR . 'includes/frontend.php';
        require_once AWR_PLUGIN_DIR . 'admin/dashboard.php';
        require_once AWR_PLUGIN_DIR . 'admin/settings.php';
    }

    // Register hooks and actions
    public function register_hooks() {
        // Admin settings page
        add_action('admin_menu', [__CLASS__, 'create_settings_page']);
        
        // Settings and scripts
        add_action('admin_init', __NAMESPACE__ . '\\Settings::register_settings');
        add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\Settings::enqueue_color_picker');
        
        // Plugin update
        add_action('init', [$this, 'plugin_updater']);
    }

    // Create settings page
    public static function create_settings_page() {
        add_menu_page(
            __('Advanced Woo Recommendations', 'advanced-woo-recommendations'),
            __('Woo Recommendations', 'advanced-woo-recommendations'),
            'manage_options',
            'awr-settings',
            __NAMESPACE__ . '\\Settings::render_settings_page',
            'dashicons-admin-generic',
            80
        );
    }

    // Plugin updater from GitHub
    public function plugin_updater() {
        $repo_owner = 'rizennews';
        $repo_name = 'advanced-woo-recommendations';
        $github_url = "https://github.com/{$repo_owner}/{$repo_name}";

        $config = array(
            'slug' => plugin_basename(__FILE__),
            'proper_folder_name' => 'advanced-woo-recommendations',
            'api_url' => 'https://api.github.com/repos/' . $repo_owner . '/' . $repo_name . '/releases/latest',
            'raw_url' => 'https://raw.githubusercontent.com/' . $repo_owner . '/' . $repo_name . '/master',
            'github_url' => $github_url,
            'zip_url' => 'https://github.com/' . $repo_owner . '/' . $repo_name . '/zipball/master',
            'sslverify' => true,
            'requires' => '5.6',
            'tested' => '8.0',
            'readme' => 'README.md',
            'access_token' => '',
        );

        if (class_exists('\Designolabs\PluginUpdater')) {
            new \Designolabs\PluginUpdater($config);
        }
    }
}

// Initialize the plugin
new Plugin();
