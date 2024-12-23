<?php
namespace AdvancedWooRecommendations;

/**
 * Plugin Name: Advanced Woo Recommendations
 * Plugin URI: https://designolabs.com
 * Description: A highly advanced WooCommerce recommendation engine for personalized product suggestions powered by Recombee and Gemini AI.
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

// Define plugin version and other constants
if (!defined('AWR_VERSION')) {
    define('AWR_VERSION', '1.0.0');
}

if (!defined('AWR_PLUGIN_PATH')) {
    define('AWR_PLUGIN_PATH', \plugin_dir_path(__FILE__));
}

if (!defined('AWR_PLUGIN_URL')) {
    define('AWR_PLUGIN_URL', \plugin_dir_url(__FILE__));
}

// Ensure WordPress is loaded
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include essential files
require_once AWR_PLUGIN_PATH . 'includes/admin.php';
require_once AWR_PLUGIN_PATH . 'includes/api.php';
require_once AWR_PLUGIN_PATH . 'includes/cart-recommendations.php';
require_once AWR_PLUGIN_PATH . 'includes/email.php';
require_once AWR_PLUGIN_PATH . 'includes/frontend.php';
require_once AWR_PLUGIN_PATH . 'includes/shortcodes.php';
require_once AWR_PLUGIN_PATH . 'includes/widgets.php';
require_once AWR_PLUGIN_PATH . 'includes/database.php';

// Include GitHub Updater
if (!class_exists('\\AWR_GitHub_Updater')) {
    require_once AWR_PLUGIN_PATH . 'includes/github-updater.php';
}

// Initialize GitHub Updater
function awr_init_github_updater() {
    if (class_exists('\\AWR_GitHub_Updater')) {
        new \AWR_GitHub_Updater(__FILE__, 'designolabs', 'advanced-woo-recommendations');
    }
}
\add_action('init', __NAMESPACE__ . '\\awr_init_github_updater');

// Function to check for plugin updates
function awr_check_for_updates() {
    if (!\current_user_can('update_plugins')) {
        return;
    }

    // Get the current version
    $current_version = AWR_VERSION;
    
    // GitHub API URL for the latest release
    $github_api_url = 'https://api.github.com/repos/designolabs/advanced-woo-recommendations/releases/latest';
    
    // Get the response from GitHub
    $response = \wp_remote_get($github_api_url, array(
        'headers' => array(
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . \get_bloginfo('version')
        )
    ));

    if (\is_wp_error($response)) {
        return;
    }

    $body = \wp_remote_retrieve_body($response);
    $release = json_decode($body);

    if (!empty($release) && isset($release->tag_name)) {
        $latest_version = ltrim($release->tag_name, 'v');
        
        // Compare versions
        if (version_compare($current_version, $latest_version, '<')) {
            // Add update notification
            \add_action('admin_notices', function() use ($latest_version) {
                ?>
                <div class="notice notice-info is-dismissible">
                    <p><?php printf(
                        \__('A new version (%s) of Advanced Woo Recommendations is available! Please update now.', 'advanced-woo-recommendations'),
                        \esc_html($latest_version)
                    ); ?></p>
                </div>
                <?php
            });
        }
    }
}
\add_action('admin_init', __NAMESPACE__ . '\\awr_check_for_updates');

// Enable auto updates
function awr_enable_auto_updates($update, $item) {
    $plugin_slug = 'advanced-woo-recommendations/advanced-woo-recommendations.php';
    
    if (isset($item->slug) && $item->slug === $plugin_slug) {
        return true;
    }
    
    return $update;
}
\add_filter('auto_update_plugin', __NAMESPACE__ . '\\awr_enable_auto_updates', 10, 2);

// Function to handle plugin update process
function awr_handle_plugin_update($upgrader_object, $options) {
    if ($options['action'] === 'update' && $options['type'] === 'plugin') {
        // Clear plugin cache after update
        \wp_cache_flush();
        
        // Run any necessary database updates
        awr_maybe_update_db_version();
        
        // Log update
        error_log('Advanced Woo Recommendations plugin updated successfully to version ' . AWR_VERSION);
    }
}
\add_action('upgrader_process_complete', __NAMESPACE__ . '\\awr_handle_plugin_update', 10, 2);

// Function to check and update database version if needed
function awr_maybe_update_db_version() {
    $current_db_version = \get_option('awr_db_version', '1.0.0');
    
    if (version_compare($current_db_version, AWR_VERSION, '<')) {
        // Perform database updates here
        
        // Update the database version
        \update_option('awr_db_version', AWR_VERSION);
    }
}

// Load Admin-Specific Files Conditionally
if (\is_admin()) {
    require_once AWR_PLUGIN_PATH . 'admin/settings.php';
    require_once AWR_PLUGIN_PATH . 'admin/dashboard.php';
    
    if (!defined('AWR_PLUGIN_PATH')) {
        define('AWR_PLUGIN_PATH', plugin_dir_path(__FILE__));
    }
    
    if (!defined('AWR_PLUGIN_URL')) {
        define('AWR_PLUGIN_URL', plugin_dir_url(__FILE__));
    }
    
    // Ensure WordPress is loaded
    if (!defined('ABSPATH')) {
        exit; // Exit if accessed directly
    }
    
    // Include essential files
    require_once AWR_PLUGIN_PATH . 'includes/admin.php';
    require_once AWR_PLUGIN_PATH . 'includes/api.php';
    require_once AWR_PLUGIN_PATH . 'includes/cart-recommendations.php';
    require_once AWR_PLUGIN_PATH . 'includes/email.php';
    require_once AWR_PLUGIN_PATH . 'includes/frontend.php';
    require_once AWR_PLUGIN_PATH . 'includes/shortcodes.php';
    require_once AWR_PLUGIN_PATH . 'includes/widgets.php';
    require_once AWR_PLUGIN_PATH . 'includes/database.php';
    
}

// Function to handle plugin activation
function awr_plugin_activation() {
    global $wpdb;
    
    // Create database tables
    $charset_collate = $wpdb->get_charset_collate();
    
    // Recommendations tracking table
    $table_name = $wpdb->prefix . 'awr_recommendations_tracking';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        product_id bigint(20) NOT NULL,
        recommendation_type varchar(50) NOT NULL,
        clicked tinyint(1) DEFAULT 0,
        converted tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY product_id (product_id)
    ) $charset_collate;";

    // User preferences table
    $table_preferences = $wpdb->prefix . 'awr_user_preferences';
    $sql .= "CREATE TABLE IF NOT EXISTS $table_preferences (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        preference_key varchar(100) NOT NULL,
        preference_value text NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY user_preference (user_id, preference_key)
    ) $charset_collate;";

    // Product relationships table
    $table_relationships = $wpdb->prefix . 'awr_product_relationships';
    $sql .= "CREATE TABLE IF NOT EXISTS $table_relationships (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        product_id bigint(20) NOT NULL,
        related_product_id bigint(20) NOT NULL,
        relationship_type varchar(50) NOT NULL,
        strength float DEFAULT 0,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY product_relationship (product_id, related_product_id, relationship_type),
        KEY product_id (product_id),
        KEY related_product_id (related_product_id)
    ) $charset_collate;";

    require_once(\ABSPATH . 'wp-admin/includes/upgrade.php');
    \dbDelta($sql);

    // Set default options with error checking
    $default_options = array(
        'awr_recombee_api_key' => '',
        'awr_gemini_api_key' => '',
        'awr_primary_color' => '#0071a1',
        'awr_secondary_color' => '#f1f1f1',
        'awr_font_family' => 'Arial, sans-serif',
        'awr_layout_style' => 'grid',
        'awr_product_columns' => '4',
        'awr_product_spacing' => '20',
        'awr_bg_color' => '#ffffff',
        'awr_text_color' => '#000000',
        'awr_button_color' => '#0071a1',
        'awr_button_hover_color' => '#005077',
        'awr_recommendations_per_page' => '12',
        'awr_enable_cart_recommendations' => '1',
        'awr_enable_email_recommendations' => '1',
        'awr_enable_product_page_recommendations' => '1',
        'awr_recommendation_algorithm' => 'hybrid',
        'awr_cache_duration' => '3600',
        'awr_min_confidence_score' => '0.5'
    );

    foreach ($default_options as $option_name => $default_value) {
        if (\get_option($option_name) === false) {
            \add_option($option_name, $default_value);
        }
    }

    // Flush rewrite rules
    \flush_rewrite_rules();
    
    // Clear any existing caches
    \wp_cache_flush();
    
    // Log activation
    error_log('Advanced Woo Recommendations plugin activated successfully');
}
\register_activation_hook(__FILE__, __NAMESPACE__ . '\\awr_plugin_activation');

// Function to handle plugin deactivation
function awr_plugin_deactivation() {
    // Clear scheduled hooks
    \wp_clear_scheduled_hook('awr_daily_maintenance');
    \wp_clear_scheduled_hook('awr_weekly_cleanup');
    
    // Clear plugin caches
    \wp_cache_delete('awr_recommendations_cache');
    
    // Log deactivation
    error_log('Advanced Woo Recommendations plugin deactivated');
}
\register_deactivation_hook(__FILE__, __NAMESPACE__ . '\\awr_plugin_deactivation');

// Function to handle plugin uninstall
function awr_plugin_uninstall() {
    global $wpdb;
    
    // Drop plugin tables if they exist
    $tables = array(
        $wpdb->prefix . 'awr_recommendations_tracking',
        $wpdb->prefix . 'awr_user_preferences',
        $wpdb->prefix . 'awr_product_relationships'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    // Delete plugin options
    $options = array(
        'awr_recombee_api_key',
        'awr_gemini_api_key',
        'awr_primary_color',
        'awr_secondary_color',
        'awr_font_family',
        'awr_layout_style',
        'awr_product_columns',
        'awr_product_spacing',
        'awr_bg_color',
        'awr_text_color',
        'awr_button_color',
        'awr_button_hover_color',
        'awr_recommendations_per_page',
        'awr_enable_cart_recommendations',
        'awr_enable_email_recommendations',
        'awr_enable_product_page_recommendations',
        'awr_recommendation_algorithm',
        'awr_cache_duration',
        'awr_min_confidence_score'
    );
    
    foreach ($options as $option) {
        \delete_option($option);
    }
}
\register_uninstall_hook(__FILE__, __NAMESPACE__ . '\\awr_plugin_uninstall');

// Load translations
function awr_load_plugin_textdomain() {
    \load_plugin_textdomain('advanced-woo-recommendations', false, basename(dirname(__FILE__)) . '/languages/');
}
\add_action('plugins_loaded', __NAMESPACE__ . '\\awr_load_plugin_textdomain');

// Function to get the Gemini API key
function awr_get_gemini_api_key() {
    return \get_option('awr_gemini_api_key', '');
}

// Function to check if the Gemini API is enabled
function awr_is_gemini_api_enabled() {
    $gemini_api_key = awr_get_gemini_api_key();
    return !empty($gemini_api_key);
}

// Function to get the Recombee API key
function awr_get_recombee_api_key() {
    return \get_option('awr_recombee_api_key', '');
}

// Function to check if Recombee integration is enabled
function awr_is_recombee_integration_enabled() {
    $recombee_api_key = awr_get_recombee_api_key();
    return !empty($recombee_api_key);
}

// Check WooCommerce dependency
function awr_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        \add_action('admin_notices', function () {
            ?>
            <div class="notice notice-error">
                <p><?php \_e('Advanced Woo Recommendations requires WooCommerce to be installed and active.', 'advanced-woo-recommendations'); ?></p>
            </div>
            <?php
        });
        return false;
    }
    return true;
}

// Create settings page
function awr_create_settings_page() {
    if (!awr_check_woocommerce()) {
        return;
    }

    \add_menu_page(
        'Advanced Woo Recommendations',
        'Woo Recommendations',
        'manage_options',
        'awr-settings',
        __NAMESPACE__ . '\\awr_render_settings_page',
        'dashicons-admin-generic',
        80
    );
}
\add_action('admin_menu', __NAMESPACE__ . '\\awr_create_settings_page');

// Render settings page
function awr_render_settings_page() {
    if (!\current_user_can('manage_options')) {
        \wp_die(\__('You do not have sufficient permissions to access this page.', 'advanced-woo-recommendations'));
    }

    ?>
    <div class="wrap">
        <h1><?php echo \esc_html(\get_admin_page_title()); ?></h1>
        <?php \settings_errors(); ?>
        <form method="post" action="options.php">
            <?php
            \settings_fields('awr_settings_group');
            \do_settings_sections('awr-settings-page');
            \submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function awr_register_settings() {
    // Register API key settings
    \register_setting('awr_settings_group', 'awr_recombee_api_key', array(
        'sanitize_callback' => __NAMESPACE__ . '\\awr_validate_api_key',
        'default' => ''
    ));
    
    \register_setting('awr_settings_group', 'awr_gemini_api_key', array(
        'sanitize_callback' => __NAMESPACE__ . '\\awr_validate_gemini_api_key',
        'default' => ''
    ));
    
    \add_settings_section(
        'awr_main_settings',
        'API Settings',
        __NAMESPACE__ . '\\awr_main_settings_callback',
        'awr-settings-page'
    );
    
    \add_settings_field(
        'awr_recombee_api_key',
        'Recombee API Key',
        __NAMESPACE__ . '\\awr_api_key_callback',
        'awr-settings-page',
        'awr_main_settings'
    );

    \add_settings_field(
        'awr_gemini_api_key',
        'Gemini API Key',
        __NAMESPACE__ . '\\awr_gemini_api_key_callback',
        'awr-settings-page',
        'awr_main_settings'
    );

    // Register appearance settings
    \register_setting('awr_settings_group', 'awr_primary_color', array(
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#0071a1'
    ));
    
    \register_setting('awr_settings_group', 'awr_secondary_color', array(
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#f1f1f1'
    ));
    
    \register_setting('awr_settings_group', 'awr_font_family');
    
    \add_settings_section(
        'awr_customization_section',
        \__('Recommendations Customization', 'advanced-woo-recommendations'),
        __NAMESPACE__ . '\\awr_customization_section_callback',
        'awr-settings-page'
    );
    
    \add_settings_field(
        'awr_primary_color',
        \__('Primary Color', 'advanced-woo-recommendations'),
        __NAMESPACE__ . '\\awr_primary_color_callback',
        'awr-settings-page',
        'awr_customization_section'
    );
    
    \add_settings_field(
        'awr_secondary_color',
        \__('Secondary Color', 'advanced-woo-recommendations'),
        __NAMESPACE__ . '\\awr_secondary_color_callback',
        'awr-settings-page',
        'awr_customization_section'
    );
    
    \add_settings_field(
        'awr_font_family',
        \__('Font Family', 'advanced-woo-recommendations'),
        __NAMESPACE__ . '\\awr_font_family_callback',
        'awr-settings-page',
        'awr_customization_section'
    );

    // Register layout settings
    \register_setting('awr_settings_group', 'awr_layout_style');
    
    \register_setting('awr_settings_group', 'awr_product_columns', array(
        'sanitize_callback' => function ($input) {
            return \absint(min(max($input, 1), 6));
        },
        'default' => 4
    ));
    
    \register_setting('awr_settings_group', 'awr_product_spacing', array(
        'sanitize_callback' => function ($input) {
            return \absint(min(max($input, 0), 50));
        },
        'default' => 20
    ));
    
    \add_settings_section(
        'awr_layout_section',
        \__('Recommendations Layout', 'advanced-woo-recommendations'),
        __NAMESPACE__ . '\\awr_layout_section_callback',
        'awr-settings-page'
    );
    
    \add_settings_field(
        'awr_layout_style',
        \__('Layout Style', 'advanced-woo-recommendations'),
        __NAMESPACE__ . '\\awr_layout_style_callback',
        'awr-settings-page',
        'awr_layout_section'
    );
    
    \add_settings_field(
        'awr_product_columns',
        \__('Number of Columns', 'advanced-woo-recommendations'),
        __NAMESPACE__ . '\\awr_product_columns_callback',
        'awr-settings-page',
        'awr_layout_section'
    );
    
    \add_settings_field(
        'awr_product_spacing',
        \__('Product Spacing (px)', 'advanced-woo-recommendations'),
        __NAMESPACE__ . '\\awr_product_spacing_callback',
        'awr-settings-page',
        'awr_layout_section'
    );

    // Register color settings
    \register_setting('awr_settings_group', 'awr_bg_color', array(
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#ffffff'
    ));
    
    \register_setting('awr_settings_group', 'awr_text_color', array(
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#000000'
    ));
    
    \register_setting('awr_settings_group', 'awr_button_color', array(
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#0071a1'
    ));
    
    \register_setting('awr_settings_group', 'awr_button_hover_color', array(
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#005077'
    ));
    
    \add_settings_section(
        'awr_color_section',
        \__('Recommendations Colors', 'advanced-woo-recommendations'),
        __NAMESPACE__ . '\\awr_color_section_callback',
        'awr-settings-page'
    );
    
    \add_settings_field(
        'awr_bg_color',
        \__('Background Color', 'advanced-woo-recommendations'),
        __NAMESPACE__ . '\\awr_bg_color_callback',
        'awr-settings-page',
        'awr_color_section'
    );
    
    \add_settings_field(
        'awr_text_color',
        \__('Text Color', 'advanced-woo-recommendations'),
        __NAMESPACE__ . '\\awr_text_color_callback',
        'awr-settings-page',
        'awr_color_section'
    );
    
    \add_settings_field(
        'awr_button_color',
        \__('Button Color', 'advanced-woo-recommendations'),
        __NAMESPACE__ . '\\awr_button_color_callback',
        'awr-settings-page',
        'awr_color_section'
    );
    
    \add_settings_field(
        'awr_button_hover_color',
        \__('Button Hover Color', 'advanced-woo-recommendations'),
        __NAMESPACE__ . '\\awr_button_hover_color_callback',
        'awr-settings-page',
        'awr_color_section'
    );
}
\add_action('admin_init', __NAMESPACE__ . '\\awr_register_settings');

// Settings section callbacks
function awr_main_settings_callback() {
    echo 'Enter your API keys to enable personalized recommendations powered by Recombee and Gemini AI.';
}

function awr_customization_section_callback() {
    echo \__('Customize the look and feel of your recommendations section to match your brand.', 'advanced-woo-recommendations');
}

function awr_layout_section_callback() {
    echo \__('Customize the layout of your recommendations section.', 'advanced-woo-recommendations');
}

function awr_color_section_callback() {
    echo \__('Customize the colors of your recommendations section to match your branding.', 'advanced-woo-recommendations');
}

// Field callbacks
function awr_api_key_callback() {
    $api_key = \get_option('awr_recombee_api_key');
    echo '<input type="text" name="awr_recombee_api_key" value="' . \esc_attr($api_key) . '" class="regular-text" />';
    echo '<p class="description">' . \__('Enter your Recombee API key here. You can find this in your Recombee dashboard.', 'advanced-woo-recommendations') . '</p>';
}

function awr_gemini_api_key_callback() {
    $api_key = \get_option('awr_gemini_api_key');
    echo '<input type="text" name="awr_gemini_api_key" value="' . \esc_attr($api_key) . '" class="regular-text" />';
    echo '<p class="description">' . \__('Enter your Gemini API key here. You can find this in your Google AI Studio dashboard.', 'advanced-woo-recommendations') . '</p>';
}

function awr_primary_color_callback() {
    $primary_color = \get_option('awr_primary_color', '#0071a1');
    echo '<input type="text" name="awr_primary_color" value="' . \esc_attr($primary_color) . '" class="awr-color-picker" />';
}

function awr_secondary_color_callback() {
    $secondary_color = \get_option('awr_secondary_color', '#f1f1f1');
    echo '<input type="text" name="awr_secondary_color" value="' . \esc_attr($secondary_color) . '" class="awr-color-picker" />';
}

function awr_font_family_callback() {
    $selected_font = \get_option('awr_font_family', 'Arial, sans-serif');
    $google_fonts = array(
        'Arial, sans-serif' => 'Arial',
        'Roboto, sans-serif' => 'Roboto',
        'Open Sans, sans-serif' => 'Open Sans',
        'Lato, sans-serif' => 'Lato',
        'Montserrat, sans-serif' => 'Montserrat',
        'Oswald, sans-serif' => 'Oswald',
        'Poppins, sans-serif' => 'Poppins',
        'Raleway, sans-serif' => 'Raleway',
        'Playfair Display, serif' => 'Playfair Display',
    );
    
    echo '<select name="awr_font_family" id="awr_font_family">';
    foreach ($google_fonts as $font_css => $font_name) {
        printf(
            '<option value="%s" %s>%s</option>',
            \esc_attr($font_css),
            \selected($selected_font, $font_css, false),
            \esc_html($font_name)
        );
    }
    echo '</select>';
    echo '<p id="awr-font-preview" style="font-family:' . \esc_attr($selected_font) . ';">' . 
         \__('This is a live preview of the selected font.', 'advanced-woo-recommendations') . '</p>';
}

function awr_layout_style_callback() {
    $layout_style = \get_option('awr_layout_style', 'grid');
    $options = array(
        'grid' => \__('Grid', 'advanced-woo-recommendations'),
        'carousel' => \__('Carousel', 'advanced-woo-recommendations'),
        'list' => \__('List', 'advanced-woo-recommendations')
    );
    
    echo '<select name="awr_layout_style">';
    foreach ($options as $value => $label) {
        printf(
            '<option value="%s" %s>%s</option>',
            \esc_attr($value),
            \selected($layout_style, $value, false),
            \esc_html($label)
        );
    }
    echo '</select>';
}

function awr_product_columns_callback() {
    $columns = \get_option('awr_product_columns', 4);
    echo '<input type="number" name="awr_product_columns" value="' . \esc_attr($columns) . '" min="1" max="6" class="small-text" />';
    echo '<p class="description">' . \__('Number of products to display per row (1-6)', 'advanced-woo-recommendations') . '</p>';
}

function awr_product_spacing_callback() {
    $spacing = \get_option('awr_product_spacing', 20);
    echo '<input type="number" name="awr_product_spacing" value="' . \esc_attr($spacing) . '" min="0" max="50" class="small-text" />';
    echo '<p class="description">' . \__('Space between products in pixels (0-50)', 'advanced-woo-recommendations') . '</p>';
}

function awr_bg_color_callback() {
    $color = \get_option('awr_bg_color', '#ffffff');
    echo '<input type="text" name="awr_bg_color" value="' . \esc_attr($color) . '" class="awr-color-field" data-default-color="#ffffff" />';
}

function awr_text_color_callback() {
    $color = \get_option('awr_text_color', '#000000');
    echo '<input type="text" name="awr_text_color" value="' . \esc_attr($color) . '" class="awr-color-field" data-default-color="#000000" />';
}

function awr_button_color_callback() {
    $color = \get_option('awr_button_color', '#0071a1');
    echo '<input type="text" name="awr_button_color" value="' . \esc_attr($color) . '" class="awr-color-field" data-default-color="#0071a1" />';
}

function awr_button_hover_color_callback() {
    $color = \get_option('awr_button_hover_color', '#005077');
    echo '<input type="text" name="awr_button_hover_color" value="' . \esc_attr($color) . '" class="awr-color-field" data-default-color="#005077" />';
}

// Enqueue color picker script
function awr_enqueue_color_picker($hook_suffix) {
    if ('toplevel_page_awr-settings' !== $hook_suffix) {
        return;
    }

    \wp_enqueue_style('wp-color-picker');
    \wp_enqueue_style(
        'awr-admin-styles',
        \plugins_url('assets/css/admin.css', __FILE__),
        array(),
        AWR_VERSION
    );

    \wp_enqueue_script(
        'awr-admin-script',
        \plugins_url('assets/js/admin.js', __FILE__),
        array('wp-color-picker', 'jquery', 'wp-i18n'),
        AWR_VERSION,
        true
    );

    wp_localize_script('awr-admin-script', 'awrAdmin', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('awr_admin_nonce'),
        'apiEndpoint' => esc_url_raw(rest_url('awr/v1')),
        'strings' => array(
            'saveError' => __('Error saving settings. Please try again.', 'advanced-woo-recommendations'),
            'saveSuccess' => __('Settings saved successfully.', 'advanced-woo-recommendations'),
            'invalidAPI' => __('Invalid API key format.', 'advanced-woo-recommendations'),
            'networkError' => __('Network error. Please check your connection.', 'advanced-woo-recommendations')
        ),
        'debug' => defined('WP_DEBUG') && WP_DEBUG,
        'version' => AWR_VERSION
    ));
}
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\awr_enqueue_color_picker');

// Enhanced API key validation and sanitization
function awr_validate_api_key($api_key) {
    if (empty($api_key)) {
        add_settings_error(
            'awr_recombee_api_key',
            'awr_api_key_error',
            __('API key cannot be empty.', 'advanced-woo-recommendations'),
            'error'
        );
        return '';
    }

    // Basic format validation
    if (!preg_match('/^[a-zA-Z0-9\-\_]{20,}$/', $api_key)) {
        add_settings_error(
            'awr_recombee_api_key',
            'awr_api_key_error',
            __('Invalid API key format. Please enter a valid Recombee API key.', 'advanced-woo-recommendations'),
            'error'
        );
        return '';
    }

    try {
        // Test API connection
        $test_result = awr_test_api_connection($api_key);
        if (is_wp_error($test_result)) {
            throw new \Exception($test_result->get_error_message());
        }
    } catch (\Exception $e) {
        add_settings_error(
            'awr_recombee_api_key',
            'awr_api_key_error',
            sprintf(
                __('Failed to connect to Recombee API: %s', 'advanced-woo-recommendations'),
                $e->getMessage()
            ),
            'error'
        );
        return '';
    }

    return sanitize_text_field($api_key);
}

// Gemini API key validation and sanitization
function awr_validate_gemini_api_key($api_key) {
    if (empty($api_key)) {
        add_settings_error(
            'awr_gemini_api_key',
            'awr_gemini_api_key_error',
            __('Gemini API key cannot be empty.', 'advanced-woo-recommendations'),
            'error'
        );
        return '';
    }

    // Basic format validation for Gemini API key
    if (!preg_match('/^[A-Za-z0-9\-]{39}$/', $api_key)) {
        add_settings_error(
            'awr_gemini_api_key',
            'awr_gemini_api_key_error',
            __('Invalid API key format. Please enter a valid Gemini API key.', 'advanced-woo-recommendations'),
            'error'
        );
        return '';
    }

    try {
        // Test Gemini API connection
        $test_result = awr_test_gemini_api_connection($api_key);
        if (is_wp_error($test_result)) {
            throw new \Exception($test_result->get_error_message());
        }
    } catch (\Exception $e) {
        add_settings_error(
            'awr_gemini_api_key',
            'awr_gemini_api_key_error',
            sprintf(
                __('Failed to connect to Gemini API: %s', 'advanced-woo-recommendations'),
                $e->getMessage()
            ),
            'error'
        );
        return '';
    }

    return sanitize_text_field($api_key);
}