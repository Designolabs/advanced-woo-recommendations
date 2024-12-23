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



// Ensure WordPress is loaded
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Check WooCommerce dependency
function awr_check_woocommerce() {
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

// Create settings page
function awr_create_settings_page() {
    if (!awr_check_woocommerce()) {
        return;
    }
    
    add_menu_page(
        'Advanced Woo Recommendations',
        'Woo Recommendations',
        'manage_options',
        'awr-settings',
        'awr_render_settings_page',
        'dashicons-admin-generic',
        80
    );
}
add_action('admin_menu', 'awr_create_settings_page');

// Render settings page
function awr_render_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'advanced-woo-recommendations'));
    }

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php settings_errors(); ?>
        <form method="post" action="options.php">
            <?php
            wp_nonce_field('awr_save_settings', 'awr_settings_nonce');
            settings_fields('awr_settings_group');
            do_settings_sections('awr-settings-page');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function awr_register_settings() {
    // Register API key setting
    register_setting('awr_settings_group', 'awr_recombee_api_key', array(
        'sanitize_callback' => 'awr_validate_api_key',
        'default' => ''
    ));
    add_settings_section(
        'awr_main_settings',
        'API Settings',
        'awr_main_settings_callback',
        'awr-settings-page'
    );
    add_settings_field(
        'awr_recombee_api_key',
        'Recombee API Key',
        'awr_api_key_callback',
        'awr-settings-page',
        'awr_main_settings'
    );

    // Register appearance settings
    register_setting('awr_settings_group', 'awr_primary_color', array(
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#0071a1'
    ));
    register_setting('awr_settings_group', 'awr_secondary_color', array(
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#f1f1f1'
    ));
    register_setting('awr_settings_group', 'awr_font_family');
    add_settings_section(
        'awr_customization_section',
        __('Recommendations Customization', 'advanced-woo-recommendations'),
        'awr_customization_section_callback',
        'awr-settings-page'
    );
    add_settings_field(
        'awr_primary_color',
        __('Primary Color', 'advanced-woo-recommendations'),
        'awr_primary_color_callback',
        'awr-settings-page',
        'awr_customization_section'
    );
    add_settings_field(
        'awr_secondary_color',
        __('Secondary Color', 'advanced-woo-recommendations'),
        'awr_secondary_color_callback',
        'awr-settings-page',
        'awr_customization_section'
    );
    add_settings_field(
        'awr_font_family',
        __('Font Family', 'advanced-woo-recommendations'),
        'awr_font_family_callback',
        'awr-settings-page',
        'awr_customization_section'
    );

    // Register layout settings
    register_setting('awr_settings_group', 'awr_layout_style');
    register_setting('awr_settings_group', 'awr_product_columns', array(
        'sanitize_callback' => function($input) {
            return absint(min(max($input, 1), 6));
        },
        'default' => 4
    ));
    register_setting('awr_settings_group', 'awr_product_spacing', array(
        'sanitize_callback' => function($input) {
            return absint(min(max($input, 0), 50));
        },
        'default' => 20
    ));
    add_settings_section(
        'awr_layout_section',
        __('Recommendations Layout', 'advanced-woo-recommendations'),
        'awr_layout_section_callback',
        'awr-settings-page'
    );
    add_settings_field(
        'awr_layout_style',
        __('Layout Style', 'advanced-woo-recommendations'),
        'awr_layout_style_callback',
        'awr-settings-page',
        'awr_layout_section'
    );
    add_settings_field(
        'awr_product_columns',
        __('Number of Columns', 'advanced-woo-recommendations'),
        'awr_product_columns_callback',
        'awr-settings-page',
        'awr_layout_section'
    );
    add_settings_field(
        'awr_product_spacing',
        __('Product Spacing (px)', 'advanced-woo-recommendations'),
        'awr_product_spacing_callback',
        'awr-settings-page',
        'awr_layout_section'
    );

    // Register color settings
    register_setting('awr_settings_group', 'awr_bg_color', array(
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#ffffff'
    ));
    register_setting('awr_settings_group', 'awr_text_color', array(
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#000000'
    ));
    register_setting('awr_settings_group', 'awr_button_color', array(
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#0071a1'
    ));
    register_setting('awr_settings_group', 'awr_button_hover_color', array(
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#005077'
    ));
    add_settings_section(
        'awr_color_section',
        __('Recommendations Colors', 'advanced-woo-recommendations'),
        'awr_color_section_callback',
        'awr-settings-page'
    );
    add_settings_field(
        'awr_bg_color',
        __('Background Color', 'advanced-woo-recommendations'),
        'awr_bg_color_callback',
        'awr-settings-page',
        'awr_color_section'
    );
    add_settings_field(
        'awr_text_color',
        __('Text Color', 'advanced-woo-recommendations'),
        'awr_text_color_callback',
        'awr-settings-page',
        'awr_color_section'
    );
    add_settings_field(
        'awr_button_color',
        __('Button Color', 'advanced-woo-recommendations'),
        'awr_button_color_callback',
        'awr-settings-page',
        'awr_color_section'
    );
    add_settings_field(
        'awr_button_hover_color',
        __('Button Hover Color', 'advanced-woo-recommendations'),
        'awr_button_hover_color_callback',
        'awr-settings-page',
        'awr_color_section'
    );
}
add_action('admin_init', 'awr_register_settings');

// Settings section callbacks
function awr_main_settings_callback() {
    echo 'Enter your Recombee API key to enable personalized recommendations.';
}

function awr_customization_section_callback() {
    echo __('Customize the look and feel of your recommendations section to match your brand.', 'advanced-woo-recommendations');
}

function awr_layout_section_callback() {
    echo __('Customize the layout of your recommendations section.', 'advanced-woo-recommendations');
}

function awr_color_section_callback() {
    echo __('Customize the colors of your recommendations section to match your branding.', 'advanced-woo-recommendations');
}

// Field callbacks
function awr_api_key_callback() {
    $api_key = get_option('awr_recombee_api_key');
    echo '<input type="text" name="awr_recombee_api_key" value="' . esc_attr($api_key) . '" />';
}

function awr_primary_color_callback() {
    $primary_color = get_option('awr_primary_color', '#0071a1');
    echo '<input type="text" name="awr_primary_color" value="' . esc_attr($primary_color) . '" class="awr-color-picker" />';
}

function awr_secondary_color_callback() {
    $secondary_color = get_option('awr_secondary_color', '#f1f1f1');
    echo '<input type="text" name="awr_secondary_color" value="' . esc_attr($secondary_color) . '" class="awr-color-picker" />';
}

function awr_font_family_callback() {
    $selected_font = get_option('awr_font_family', 'Arial, sans-serif');
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
        $selected = selected($selected_font, $font_css, false);
        echo "<option value='" . esc_attr($font_css) . "' $selected>" . esc_html($font_name) . "</option>";
    }
    echo '</select>';
    echo '<p id="awr-font-preview" style="font-family:' . esc_attr($selected_font) . ';">This is a live preview of the selected font.</p>';
}

function awr_layout_style_callback() {
    $layout_style = get_option('awr_layout_style', 'grid');
    $options = array('grid' => 'Grid', 'carousel' => 'Carousel');
    echo '<select name="awr_layout_style">';
    foreach ($options as $value => $label) {
        $selected = selected($layout_style, $value, false);
        echo "<option value='" . esc_attr($value) . "' $selected>" . esc_html($label) . "</option>";
    }
    echo '</select>';
}

function awr_product_columns_callback() {
    $columns = get_option('awr_product_columns', 4);
    echo '<input type="number" name="awr_product_columns" value="' . esc_attr($columns) . '" min="1" max="6" />';
}

function awr_product_spacing_callback() {
    $spacing = get_option('awr_product_spacing', 20);
    echo '<input type="number" name="awr_product_spacing" value="' . esc_attr($spacing) . '" min="0" max="50" />';
}

function awr_bg_color_callback() {
    $color = get_option('awr_bg_color', '#ffffff');
    echo '<input type="text" name="awr_bg_color" value="' . esc_attr($color) . '" class="awr-color-field" data-default-color="#ffffff" />';
}

function awr_text_color_callback() {
    $color = get_option('awr_text_color', '#000000');
    echo '<input type="text" name="awr_text_color" value="' . esc_attr($color) . '" class="awr-color-field" data-default-color="#000000" />';
}

function awr_button_color_callback() {
    $color = get_option('awr_button_color', '#0071a1');
    echo '<input type="text" name="awr_button_color" value="' . esc_attr($color) . '" class="awr-color-field" data-default-color="#0071a1" />';
}

function awr_button_hover_color_callback() {
    $color = get_option('awr_button_hover_color', '#005077');
    echo '<input type="text" name="awr_button_hover_color" value="' . esc_attr($color) . '" class="awr-color-field" data-default-color="#005077" />';
}

// Enqueue color picker script
function awr_enqueue_color_picker($hook_suffix) {
    if ('toplevel_page_awr-settings' !== $hook_suffix) {
        return;
    }

    wp_enqueue_style('wp-color-picker');
    wp_enqueue_style(
        'awr-admin-styles',
        plugins_url('assets/css/admin.css', dirname(__FILE__)),
        array(),
        defined('AWR_VERSION') ? AWR_VERSION : '1.0.0'
    );

    wp_enqueue_script(
        'awr-admin-script',
        plugins_url('assets/js/admin.js', dirname(__FILE__)),
        array('wp-color-picker', 'jquery', 'wp-i18n'),
        defined('AWR_VERSION') ? AWR_VERSION : '1.0.0',
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
        'debug' => defined('WP_DEBUG') ? WP_DEBUG : false,
        'version' => defined('AWR_VERSION') ? AWR_VERSION : '1.0.0'
    ));
}
add_action('admin_enqueue_scripts', 'awr_enqueue_color_picker');

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

    $pattern = '/^[a-zA-Z0-9\-\_]{32,64}$/';
    if (!preg_match($pattern, $api_key)) {
        add_settings_error(
            'awr_recombee_api_key',
            'awr_api_key_format_error',
            __('Invalid API key format.', 'advanced-woo-recommendations'),
            'error'
        );
        return '';
    }

    return sanitize_text_field($api_key);
}
?>