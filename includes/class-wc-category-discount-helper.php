<?php
/**
 * Helper class for discount calculations
 *
 * @package    WC_Category_Discount
 * @subpackage WC_Category_Discount/includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper class for common discount operations.
 *
 * @since 1.0.0
 */
class WC_Category_Discount_Helper {

    /**
     * Option name for storing discounts.
     *
     * @since 1.0.0
     * @var   string
     */
    const OPTION_NAME = 'wc_category_discounts';

    /**
     * Get all category discounts.
     *
     * @since  1.0.0
     * @return array
     */
    public static function get_discounts() {
        return get_option( self::OPTION_NAME, array() );
    }

    /**
     * Get discount for a specific category.
     *
     * @since  1.0.0
     * @param  int $category_id Category ID.
     * @return float
     */
    public static function get_category_discount( $category_id ) {
        $discounts = self::get_discounts();
        return isset( $discounts[ $category_id ] ) ? floatval( $discounts[ $category_id ] ) : 0;
    }

    /**
     * Get the discount percentage for a product based on its categories.
     *
     * @since  1.0.0
     * @param  WC_Product $product Product object.
     * @return float
     */
    public static function get_product_discount( $product ) {
        if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
            return 0;
        }

        $discounts = self::get_discounts();

        if ( empty( $discounts ) ) {
            return 0;
        }

        // Get product ID (for variations, get parent ID).
        $product_id = $product->get_id();
        if ( $product->is_type( 'variation' ) ) {
            $product_id = $product->get_parent_id();
        }

        $category_ids = wc_get_product_term_ids( $product_id, 'product_cat' );

        if ( empty( $category_ids ) ) {
            return 0;
        }

        // Get the highest discount from all categories.
        $max_discount = 0;
        foreach ( $category_ids as $cat_id ) {
            if ( isset( $discounts[ $cat_id ] ) && floatval( $discounts[ $cat_id ] ) > $max_discount ) {
                $max_discount = floatval( $discounts[ $cat_id ] );
            }
        }

        return $max_discount;
    }

    /**
     * Calculate discounted price.
     *
     * @since  1.0.0
     * @param  float $price    Original price.
     * @param  float $discount Discount percentage.
     * @return float
     */
    public static function calculate_discounted_price( $price, $discount ) {
        if ( empty( $price ) || ! is_numeric( $price ) || $discount <= 0 ) {
            return $price;
        }

        $discounted = $price - ( $price * ( $discount / 100 ) );
        return round( $discounted, wc_get_price_decimals() );
    }

    /**
     * Check if product has category discount.
     *
     * @since  1.0.0
     * @param  WC_Product $product Product object.
     * @return bool
     */
    public static function has_discount( $product ) {
        return self::get_product_discount( $product ) > 0;
    }

    /**
     * Clear product caches.
     *
     * @since 1.0.0
     */
    public static function clear_cache() {
        if ( function_exists( 'wc_delete_product_transients' ) ) {
            wc_delete_product_transients();
        }
    }
}
