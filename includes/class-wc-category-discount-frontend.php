<?php
/**
 * Frontend functionality
 *
 * @package    WC_Category_Discount
 * @subpackage WC_Category_Discount/includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frontend class.
 *
 * @since 1.0.0
 */
class WC_Category_Discount_Frontend {

    /**
     * Flag to prevent recursive price calls.
     *
     * @since 1.0.0
     * @var   bool
     */
    private $is_getting_price = false;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Price filters for simple products.
        add_filter( 'woocommerce_product_get_price', array( $this, 'apply_discount' ), 99, 2 );
        add_filter( 'woocommerce_product_get_sale_price', array( $this, 'apply_discount' ), 99, 2 );

        // Price filters for variable products.
        add_filter( 'woocommerce_product_variation_get_price', array( $this, 'apply_discount' ), 99, 2 );
        add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'apply_discount' ), 99, 2 );

        // Price HTML filter.
        add_filter( 'woocommerce_get_price_html', array( $this, 'custom_price_html' ), 99, 2 );

        // Enqueue frontend styles.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
    }

    /**
     * Get original regular price without filters.
     *
     * @since  1.0.0
     * @param  WC_Product $product Product object.
     * @return string
     */
    private function get_original_price( $product ) {
        if ( $this->is_getting_price ) {
            return $product->get_regular_price();
        }

        $this->is_getting_price = true;

        // Remove our filters temporarily.
        remove_filter( 'woocommerce_product_get_price', array( $this, 'apply_discount' ), 99 );
        remove_filter( 'woocommerce_product_get_sale_price', array( $this, 'apply_discount' ), 99 );
        remove_filter( 'woocommerce_product_variation_get_price', array( $this, 'apply_discount' ), 99 );
        remove_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'apply_discount' ), 99 );

        $price = $product->get_regular_price();

        // Re-add our filters.
        add_filter( 'woocommerce_product_get_price', array( $this, 'apply_discount' ), 99, 2 );
        add_filter( 'woocommerce_product_get_sale_price', array( $this, 'apply_discount' ), 99, 2 );
        add_filter( 'woocommerce_product_variation_get_price', array( $this, 'apply_discount' ), 99, 2 );
        add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'apply_discount' ), 99, 2 );

        $this->is_getting_price = false;

        return $price;
    }

    /**
     * Apply category discount to price.
     *
     * @since  1.0.0
     * @param  string     $price   Product price.
     * @param  WC_Product $product Product object.
     * @return string
     */
    public function apply_discount( $price, $product ) {
        if ( $this->is_getting_price ) {
            return $price;
        }

        $discount = WC_Category_Discount_Helper::get_product_discount( $product );

        if ( $discount['value'] > 0 ) {
            $original_price = $this->get_original_price( $product );
            if ( $original_price ) {
                return WC_Category_Discount_Helper::calculate_discounted_price( $original_price, $discount );
            }
        }

        return $price;
    }

    /**
     * Custom price HTML to show strikethrough.
     *
     * @since  1.0.0
     * @param  string     $price_html Price HTML.
     * @param  WC_Product $product    Product object.
     * @return string
     */
    public function custom_price_html( $price_html, $product ) {
        $discount = WC_Category_Discount_Helper::get_product_discount( $product );

        if ( $discount['value'] <= 0 ) {
            return $price_html;
        }

        // Handle variable products.
        if ( $product->is_type( 'variable' ) ) {
            return $this->get_variable_price_html( $product, $discount );
        }

        // Handle simple products.
        return $this->get_simple_price_html( $product, $discount );
    }

    /**
     * Get price HTML for simple products.
     *
     * @since  1.0.0
     * @param  WC_Product $product  Product object.
     * @param  array      $discount Discount array with type and value.
     * @return string
     */
    private function get_simple_price_html( $product, $discount ) {
        $regular_price = $this->get_original_price( $product );

        if ( empty( $regular_price ) ) {
            return '';
        }

        $sale_price = WC_Category_Discount_Helper::calculate_discounted_price( $regular_price, $discount );

        $html  = '<del aria-hidden="true">' . wc_price( $regular_price ) . '</del> ';
        $html .= '<ins>' . wc_price( $sale_price ) . '</ins>';

        $html .= $this->get_discount_badge( $discount );

        return $html;
    }

    /**
     * Get price HTML for variable products.
     *
     * @since  1.0.0
     * @param  WC_Product_Variable $product  Product object.
     * @param  array               $discount Discount array with type and value.
     * @return string
     */
    private function get_variable_price_html( $product, $discount ) {
        // Temporarily remove our filters to get original prices.
        $this->is_getting_price = true;

        remove_filter( 'woocommerce_product_get_price', array( $this, 'apply_discount' ), 99 );
        remove_filter( 'woocommerce_product_variation_get_price', array( $this, 'apply_discount' ), 99 );

        $prices = $product->get_variation_prices( true );

        add_filter( 'woocommerce_product_get_price', array( $this, 'apply_discount' ), 99, 2 );
        add_filter( 'woocommerce_product_variation_get_price', array( $this, 'apply_discount' ), 99, 2 );

        $this->is_getting_price = false;

        if ( empty( $prices['regular_price'] ) ) {
            return '';
        }

        $min_regular = current( $prices['regular_price'] );
        $max_regular = end( $prices['regular_price'] );

        $min_sale = WC_Category_Discount_Helper::calculate_discounted_price( $min_regular, $discount );
        $max_sale = WC_Category_Discount_Helper::calculate_discounted_price( $max_regular, $discount );

        if ( $min_regular === $max_regular ) {
            $html  = '<del aria-hidden="true">' . wc_price( $min_regular ) . '</del> ';
            $html .= '<ins>' . wc_price( $min_sale ) . '</ins>';
        } else {
            $html  = '<del aria-hidden="true">' . wc_price( $min_regular ) . ' &ndash; ' . wc_price( $max_regular ) . '</del> ';
            $html .= '<ins>' . wc_price( $min_sale ) . ' &ndash; ' . wc_price( $max_sale ) . '</ins>';
        }

        $html .= $this->get_discount_badge( $discount );

        return $html;
    }

    /**
     * Get discount badge HTML.
     *
     * @since  1.0.0
     * @param  array $discount Discount array with type and value.
     * @return string
     */
    private function get_discount_badge( $discount ) {
        $discount_text = $discount['type'] === 'percentage' 
            ? sprintf( '-%s%%', number_format( $discount['value'], 0 ) )
            : sprintf( '-%s', wc_price( $discount['value'] ) );
        
        return sprintf(
            '<span class="wc-category-discount-badge">%s</span>',
            $discount_text
        );
    }

    /**
     * Enqueue frontend styles.
     *
     * @since 1.0.0
     */
    public function enqueue_styles() {
        if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() ) {
            return;
        }

        wp_enqueue_style(
            'wc-category-discount-frontend',
            WC_CATEGORY_DISCOUNT_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WC_CATEGORY_DISCOUNT_VERSION
        );
    }
}
