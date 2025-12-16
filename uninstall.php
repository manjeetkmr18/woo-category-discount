<?php
/**
 * Uninstall script
 *
 * Fired when the plugin is uninstalled.
 *
 * @package WC_Category_Discount
 * @since   1.0.0
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options.
delete_option( 'wc_category_discounts' );
delete_option( 'wc_category_discount_version' );

// Clear any cached data.
if ( function_exists( 'wc_delete_product_transients' ) ) {
    wc_delete_product_transients();
}
