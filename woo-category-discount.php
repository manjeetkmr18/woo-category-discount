<?php
/**
 * Plugin Name:       WooCommerce Category Discount
 * Description:       Apply percentage-based discounts to WooCommerce products by category with automatic strikethrough pricing display.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Manjeet Kumar
 * Author URI:        https://webxdevelopments.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       webxdev-category-discount
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 *
 * WC requires at least: 5.0
 * WC tested up to:      8.4
 *
 * @package WC_Category_Discount
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin version.
 *
 * @since 1.0.0
 */
define( 'WC_CATEGORY_DISCOUNT_VERSION', '1.0.0' );

/**
 * Plugin directory path.
 *
 * @since 1.0.0
 */
define( 'WC_CATEGORY_DISCOUNT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 *
 * @since 1.0.0
 */
define( 'WC_CATEGORY_DISCOUNT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 *
 * @since 1.0.0
 */
define( 'WC_CATEGORY_DISCOUNT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 */
function wc_category_discount_init() {
    // Check if WooCommerce is active.
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'wc_category_discount_woocommerce_notice' );
        return;
    }

    // Load the main plugin class.
    require_once WC_CATEGORY_DISCOUNT_PLUGIN_DIR . 'includes/class-wc-category-discount.php';

    // Initialize the plugin.
    WC_Category_Discount::get_instance();
}
add_action( 'plugins_loaded', 'wc_category_discount_init' );

/**
 * Display admin notice if WooCommerce is not active.
 *
 * @since 1.0.0
 */
function wc_category_discount_woocommerce_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <?php
            printf(
                /* translators: %1$s: Plugin name, %2$s: WooCommerce */
                esc_html__( '%1$s requires %2$s to be installed and active.', 'webxdev-category-discount' ),
                '<strong>' . esc_html__( 'WooCommerce Category Discount', 'webxdev-category-discount' ) . '</strong>',
                '<strong>' . esc_html__( 'WooCommerce', 'webxdev-category-discount' ) . '</strong>'
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * Plugin activation hook.
 *
 * @since 1.0.0
 */
function wc_category_discount_activate() {
    // Create default options.
    if ( ! get_option( 'wc_category_discounts' ) ) {
        add_option( 'wc_category_discounts', array() );
    }

    // Store plugin version.
    add_option( 'wc_category_discount_version', WC_CATEGORY_DISCOUNT_VERSION );

    // Clear rewrite rules.
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wc_category_discount_activate' );

/**
 * Plugin deactivation hook.
 *
 * @since 1.0.0
 */
function wc_category_discount_deactivate() {
    // Clear any cached product data.
    if ( function_exists( 'wc_delete_product_transients' ) ) {
        wc_delete_product_transients();
    }

    // Clear rewrite rules.
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wc_category_discount_deactivate' );

/**
 * Declare HPOS compatibility.
 *
 * @since 1.0.0
 */
add_action(
    'before_woocommerce_init',
    function () {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    }
);