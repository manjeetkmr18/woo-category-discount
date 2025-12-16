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
     * @return array
     */
    public static function get_category_discount( $category_id ) {
        $discounts = self::get_discounts();
        if ( isset( $discounts[ $category_id ] ) ) {
            // Handle old format (just percentage value)
            if ( is_numeric( $discounts[ $category_id ] ) ) {
                return array(
                    'type' => 'percentage',
                    'value' => floatval( $discounts[ $category_id ] ),
                    'apply_to_children' => false
                );
            }
            // New format
            return wp_parse_args( $discounts[ $category_id ], array(
                'type' => 'percentage',
                'value' => 0,
                'apply_to_children' => false
            ) );
        }
        return array( 'type' => 'percentage', 'value' => 0, 'apply_to_children' => false );
    }

    /**
     * Get the discount amount for a product based on its categories.
     *
     * @since  1.0.0
     * @param  WC_Product $product Product object.
     * @return array
     */
    public static function get_product_discount( $product ) {
        if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
            return array( 'type' => 'percentage', 'value' => 0 );
        }

        $discounts = self::get_discounts();

        if ( empty( $discounts ) ) {
            return array( 'type' => 'percentage', 'value' => 0 );
        }

        // Get product ID (for variations, get parent ID).
        $product_id = $product->get_id();
        if ( $product->is_type( 'variation' ) ) {
            $product_id = $product->get_parent_id();
        }

        $category_ids = wc_get_product_term_ids( $product_id, 'product_cat' );

        if ( empty( $category_ids ) ) {
            return array( 'type' => 'percentage', 'value' => 0 );
        }

        // Get all applicable discounts including parent categories
        $applicable_discounts = array();
        
        foreach ( $category_ids as $cat_id ) {
            // Add direct category discount
            $discount = self::get_category_discount( $cat_id );
            if ( $discount['value'] > 0 ) {
                $applicable_discounts[] = $discount;
            }
            
            // Check parent categories for hierarchical discounts
            $parent_discounts = self::get_parent_category_discounts( $cat_id );
            $applicable_discounts = array_merge( $applicable_discounts, $parent_discounts );
        }

        if ( empty( $applicable_discounts ) ) {
            return array( 'type' => 'percentage', 'value' => 0 );
        }

        // Return the highest applicable discount
        return self::get_highest_discount( $applicable_discounts );
    }

    /**
     * Get discounts from parent categories that apply to children.
     *
     * @since  1.0.0
     * @param  int $category_id Category ID.
     * @return array
     */
    public static function get_parent_category_discounts( $category_id ) {
        $discounts = array();
        $parent_id = wp_get_term_taxonomy_parent_id( $category_id, 'product_cat' );
        
        while ( $parent_id ) {
            $parent_discount = self::get_category_discount( $parent_id );
            if ( $parent_discount['value'] > 0 && $parent_discount['apply_to_children'] ) {
                $discounts[] = $parent_discount;
            }
            $parent_id = wp_get_term_taxonomy_parent_id( $parent_id, 'product_cat' );
        }
        
        return $discounts;
    }

    /**
     * Get the highest discount from an array of discounts.
     *
     * @since  1.0.0
     * @param  array $discounts Array of discount arrays.
     * @return array
     */
    public static function get_highest_discount( $discounts ) {
        $highest_percentage = 0;
        $highest_fixed = 0;
        $result = array( 'type' => 'percentage', 'value' => 0 );

        foreach ( $discounts as $discount ) {
            if ( $discount['type'] === 'percentage' && $discount['value'] > $highest_percentage ) {
                $highest_percentage = $discount['value'];
                $result = $discount;
            } elseif ( $discount['type'] === 'fixed' && $discount['value'] > $highest_fixed ) {
                $highest_fixed = $discount['value'];
                $result = $discount;
            }
        }

        // Prefer fixed amount if it's greater than percentage equivalent
        if ( $highest_fixed > 0 && $highest_percentage > 0 ) {
            // This is a simple heuristic - in practice you might want more sophisticated logic
            $result = array( 'type' => 'fixed', 'value' => $highest_fixed );
        }

        return $result;
    }

    /**
     * Calculate discounted price.
     *
     * @since  1.0.0
     * @param  float $price    Original price.
     * @param  array $discount Discount array with type and value.
     * @return float
     */
    public static function calculate_discounted_price( $price, $discount ) {
        if ( empty( $price ) || ! is_numeric( $price ) || $discount['value'] <= 0 ) {
            return $price;
        }

        if ( $discount['type'] === 'percentage' ) {
            $discounted = $price - ( $price * ( $discount['value'] / 100 ) );
        } else {
            $discounted = $price - $discount['value'];
        }

        // Ensure price doesn't go below 0
        $discounted = max( 0, $discounted );
        
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
        $discount = self::get_product_discount( $product );
        return $discount['value'] > 0;
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
