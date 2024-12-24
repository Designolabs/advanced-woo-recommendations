<?php
namespace AdvancedWooRecommendations;

/**
 * Plugin Name: Advanced Woo Recommendations
 * Plugin URI: https://designolabs.com
 * Description: A highly advanced WooCommerce recommendation engine for personalized product suggestions.
 * Version: 1.0
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
    exit; // Exit if accessed directly
}

// Define plugin paths
define('AWR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AWR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once AWR_PLUGIN_DIR . 'includes/admin.php';
require_once AWR_PLUGIN_DIR . 'includes/api.php';
require_once AWR_PLUGIN_DIR . 'includes/frontend.php';

// Enqueue scripts and styles
function awr_enqueue_assets() {
    wp_enqueue_style('awr-styles', AWR_PLUGIN_URL . 'assets/css/style.css');
    wp_enqueue_script('awr-scripts', AWR_PLUGIN_URL . 'assets/js/script.js', array('jquery'), '1.0', true);
}
add_action('wp_enqueue_scripts', 'awr_enqueue_assets');

// Shortcode for product recommendations
function awr_display_recommendations() {
    ob_start();
    include AWR_PLUGIN_DIR . 'templates/recommendations.php';
    return ob_get_clean();
}
add_shortcode('product_recommendations', 'awr_display_recommendations');


function awr_enqueue_react_assets() {
    // Enqueue React and React DOM from WordPress core
    wp_enqueue_script('react', 'https://unpkg.com/react@17/umd/react.production.min.js', array(), '17.0.0', true);
    wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@17/umd/react-dom.production.min.js', array('react'), '17.0.0', true);

    // Enqueue our custom React component
    wp_enqueue_script(
        'awr-react-app',
        AWR_PLUGIN_URL . 'assets/js/react-app.js',
        array('react', 'react-dom', 'wp-element'),
        '1.0',
        true
    );

    // Localize script to pass necessary data (e.g., API endpoints)
    wp_localize_script('awr-react-app', 'awr_data', array(
        'apiEndpoint' => rest_url('awr/v1/recommendations'),
        'userId' => get_current_user_id() ? get_current_user_id() : 'guest_' . session_id(),
    ));
}
add_action('wp_enqueue_scripts', 'awr_enqueue_react_assets');

function awr_enqueue_admin_scripts() {
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('awr-admin-script', AWR_PLUGIN_URL . 'assets/js/admin.js', array('wp-color-picker'), false, true);
}
add_action('admin_enqueue_scripts', 'awr_enqueue_admin_scripts');


function awr_enqueue_color_picker($hook_suffix) {
    // Check if we're on the plugin settings page
    if ($hook_suffix === 'settings_page_awr-settings-page') {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('awr-admin-scripts', AWR_PLUGIN_URL . 'assets/js/admin-script.js', array('wp-color-picker'), false, true);
    }
}
add_action('admin_enqueue_scripts', 'awr_enqueue_color_picker');

