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

// Define plugin constants
define('AWR_PLUGIN_VERSION', '1.0.0');
define('AWR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AWR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AWR_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('AWR_TEXT_DOMAIN', 'advanced-woo-recommendations');

// Load necessary files and initialize the plugin
require_once AWR_PLUGIN_DIR . 'includes/admin.php';
require_once AWR_PLUGIN_DIR . 'includes/api.php';
require_once AWR_PLUGIN_DIR . 'includes/email.php';
require_once AWR_PLUGIN_DIR . 'includes/frontend.php';

// Activation hook (creates a database table and sets up default options)
function awr_activate() {
    global $wpdb;
    
    // Set the table name with the WordPress prefix
    $table_name = $wpdb->prefix . 'awr_recommendations';
    
    // SQL to create a new table
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        product_id bigint(20) NOT NULL,
        recommendation_type varchar(255) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    // Require the WordPress dbDelta function to handle table creation
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    
    // Insert default plugin options/settings into the database if not already set
    if ( ! get_option( 'awr_plugin_options' ) ) {
        $default_options = array(
            'recommendation_algorithm' => 'collaborative_filtering',
            'email_integration' => 'enabled',
            'version' => AWR_PLUGIN_VERSION,
        );
        update_option( 'awr_plugin_options', $default_options );
    }
}
register_activation_hook(__FILE__, 'awr_activate');

// Deactivation hook (cleanup options or transients)
function awr_deactivate() {
    // Clean up options or transient data if necessary

    // Delete the plugin options if you want to reset on deactivation
    delete_option( 'awr_plugin_options' );

    // Clear scheduled cron events if any were added
    wp_clear_scheduled_hook('awr_cron_hook');
}
register_deactivation_hook(__FILE__, 'awr_deactivate');

// Enqueue styles and scripts for the plugin
function awr_enqueue_assets() {
    wp_enqueue_style( 'awr-styles', plugin_dir_url( __FILE__ ) . 'assets/css/style.css' );
    wp_enqueue_script( 'awr-scripts', plugin_dir_url( __FILE__ ) . 'assets/js/script.js', array('jquery'), null, true );
}
add_action( 'wp_enqueue_scripts', 'awr_enqueue_assets' );

// Shortcode for displaying recommendations
function awr_display_recommendations() {
    global $wpdb;

    // Fetch personalized recommendations
    $table_name = $wpdb->prefix . 'awr_recommendations';
    $user_id = get_current_user_id(); // Get the current user ID

    $recommendations = $wpdb->get_results( $wpdb->prepare( 
        "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT 12", 
        $user_id 
    ));

    // Output the recommendations (for now, a basic list of product IDs)
    if ( ! empty( $recommendations ) ) {
        $output = '<ul>';
        foreach ( $recommendations as $rec ) {
            $product = wc_get_product( $rec->product_id ); // Get WooCommerce product by ID
            if ( $product ) {
                $output .= '<li>' . $product->get_name() . '</li>'; // Display product name
            }
        }
        $output .= '</ul>';
    } else {
        $output = '<p>No recommendations found.</p>';
    }

    return $output;
}
add_shortcode( 'product_recommendations', 'awr_display_recommendations' );

// Load text domain for translations
function awr_load_textdomain() {
    load_plugin_textdomain( AWR_TEXT_DOMAIN, false, dirname( AWR_PLUGIN_BASENAME ) . '/languages' );
}
add_action( 'plugins_loaded', 'awr_load_textdomain' );
