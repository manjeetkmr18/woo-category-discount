<?php
/**
 * Admin functionality
 *
 * @package    WC_Category_Discount
 * @subpackage WC_Category_Discount/includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin class.
 *
 * @since 1.0.0
 */
class WC_Category_Discount_Admin {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'update_option_' . WC_Category_Discount_Helper::OPTION_NAME, array( $this, 'on_settings_update' ) );
        add_filter( 'plugin_action_links_' . WC_CATEGORY_DISCOUNT_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_wc_category_discount_search', array( $this, 'ajax_search_categories' ) );
        add_action( 'wp_ajax_wc_category_discount_apply_to_children', array( $this, 'ajax_apply_to_children' ) );
    }

    /**
     * Add admin menu.
     *
     * @since 1.0.0
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Category Discounts', 'webxdev-category-discount' ),
            __( 'Category Discounts', 'webxdev-category-discount' ),
            'manage_woocommerce',
            'wc-category-discounts',
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Register settings.
     *
     * @since 1.0.0
     */
    public function register_settings() {
        register_setting(
            WC_Category_Discount_Helper::OPTION_NAME,
            WC_Category_Discount_Helper::OPTION_NAME,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
            )
        );
    }

    /**
     * Sanitize settings.
     *
     * @since  1.0.0
     * @param  array $input Input data.
     * @return array
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();

        if ( is_array( $input ) ) {
            foreach ( $input as $category_id => $discount_data ) {
                $category_id = absint( $category_id );
                
                if ( $category_id > 0 ) {
                    // Handle old format (just numeric value)
                    if ( is_numeric( $discount_data ) ) {
                        $discount_value = floatval( $discount_data );
                        if ( $discount_value >= 0 && $discount_value <= 100 ) {
                            $sanitized[ $category_id ] = array(
                                'type' => 'percentage',
                                'value' => $discount_value,
                                'apply_to_children' => false
                            );
                        }
                    }
                    // Handle new format
                    elseif ( is_array( $discount_data ) ) {
                        $type = sanitize_text_field( $discount_data['type'] ?? 'percentage' );
                        $value = floatval( $discount_data['value'] ?? 0 );
                        $apply_to_children = ! empty( $discount_data['apply_to_children'] );
                        
                        // Validate based on type
                        if ( $type === 'percentage' && $value >= 0 && $value <= 100 ) {
                            $sanitized[ $category_id ] = array(
                                'type' => 'percentage',
                                'value' => $value,
                                'apply_to_children' => $apply_to_children
                            );
                        } elseif ( $type === 'fixed' && $value >= 0 ) {
                            $sanitized[ $category_id ] = array(
                                'type' => 'fixed',
                                'value' => $value,
                                'apply_to_children' => $apply_to_children
                            );
                        }
                    }
                }
            }
        }

        return $sanitized;
    }

    /**
     * Enqueue admin styles.
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook.
     */
    public function enqueue_styles( $hook ) {
        if ( 'woocommerce_page_wc-category-discounts' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'wc-category-discount-admin',
            WC_CATEGORY_DISCOUNT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WC_CATEGORY_DISCOUNT_VERSION
        );
    }

    /**
     * Enqueue admin scripts.
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook.
     */
    public function enqueue_scripts( $hook ) {
        if ( 'woocommerce_page_wc-category-discounts' !== $hook ) {
            return;
        }

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-autocomplete' );
        
        wp_enqueue_script(
            'wc-category-discount-admin',
            WC_CATEGORY_DISCOUNT_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery', 'jquery-ui-autocomplete' ),
            WC_CATEGORY_DISCOUNT_VERSION,
            true
        );

        wp_localize_script(
            'wc-category-discount-admin',
            'wcCategoryDiscount',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'wc_category_discount_nonce' ),
                'currencySymbol' => get_woocommerce_currency_symbol(),
            )
        );
    }

    /**
     * Render admin page.
     *
     * @since 1.0.0
     */
    public function render_admin_page() {
        $discounts  = WC_Category_Discount_Helper::get_discounts();
        $categories = get_terms(
            array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'orderby'    => 'name',
            )
        );

        include WC_CATEGORY_DISCOUNT_PLUGIN_DIR . 'templates/admin/settings-page.php';
    }

    /**
     * Handle settings update.
     *
     * @since 1.0.0
     */
    public function on_settings_update() {
        WC_Category_Discount_Helper::clear_cache();
    }

    /**
     * Add plugin action links.
     *
     * @since  1.0.0
     * @param  array $links Existing links.
     * @return array
     */
    public function add_action_links( $links ) {
        $plugin_links = array(
            '<a href="' . esc_url( admin_url( 'admin.php?page=wc-category-discounts' ) ) . '">' . esc_html__( 'Settings', 'webxdev-category-discount' ) . '</a>',
        );

        return array_merge( $plugin_links, $links );
    }

    /**
     * AJAX handler for category search.
     *
     * @since 1.0.0
     */
    public function ajax_search_categories() {
        check_ajax_referer( 'wc_category_discount_nonce', 'nonce' );

        $search_term = sanitize_text_field( $_POST['search'] ?? '' );
        
        $categories = get_terms(
            array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'name__like' => $search_term,
                'orderby'    => 'name',
                'number'     => 20,
            )
        );

        $results = array();
        if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
            foreach ( $categories as $category ) {
                $parent_name = '';
                if ( $category->parent ) {
                    $parent = get_term( $category->parent, 'product_cat' );
                    if ( $parent && ! is_wp_error( $parent ) ) {
                        $parent_name = $parent->name;
                    }
                }
                
                $results[] = array(
                    'id' => $category->term_id,
                    'name' => $category->name,
                    'parent' => $parent_name,
                    'count' => $category->count
                );
            }
        }

        wp_send_json_success( $results );
    }

    /**
     * AJAX handler for applying discount to child categories.
     *
     * @since 1.0.0
     */
    public function ajax_apply_to_children() {
        check_ajax_referer( 'wc_category_discount_nonce', 'nonce' );

        $category_id = absint( $_POST['category_id'] ?? 0 );
        $discount_type = sanitize_text_field( $_POST['discount_type'] ?? 'percentage' );
        $discount_value = floatval( $_POST['discount_value'] ?? 0 );

        if ( ! $category_id || $discount_value <= 0 ) {
            wp_send_json_error( __( 'Invalid category or discount value.', 'webxdev-category-discount' ) );
        }

        // Get all child categories
        $children = get_terms(
            array(
                'taxonomy' => 'product_cat',
                'parent'   => $category_id,
                'hide_empty' => false,
            )
        );

        $discounts = WC_Category_Discount_Helper::get_discounts();
        $updated_count = 0;

        if ( ! empty( $children ) && ! is_wp_error( $children ) ) {
            foreach ( $children as $child ) {
                $discounts[ $child->term_id ] = array(
                    'type' => $discount_type,
                    'value' => $discount_value,
                    'apply_to_children' => false
                );
                $updated_count++;
                
                // Recursively apply to grandchildren
                $this->apply_discount_to_descendants( $child->term_id, $discount_type, $discount_value, $discounts );
            }
        }

        update_option( WC_Category_Discount_Helper::OPTION_NAME, $discounts );

        wp_send_json_success( 
            sprintf(
                /* translators: %d: Number of categories updated */
                _n( '%d category updated.', '%d categories updated.', $updated_count, 'webxdev-category-discount' ),
                $updated_count
            )
        );
    }

    /**
     * Recursively apply discount to all descendant categories.
     *
     * @since 1.0.0
     * @param int    $parent_id      Parent category ID.
     * @param string $discount_type  Discount type.
     * @param float  $discount_value Discount value.
     * @param array  &$discounts     Discounts array (passed by reference).
     */
    private function apply_discount_to_descendants( $parent_id, $discount_type, $discount_value, &$discounts ) {
        $children = get_terms(
            array(
                'taxonomy' => 'product_cat',
                'parent'   => $parent_id,
                'hide_empty' => false,
            )
        );

        if ( ! empty( $children ) && ! is_wp_error( $children ) ) {
            foreach ( $children as $child ) {
                $discounts[ $child->term_id ] = array(
                    'type' => $discount_type,
                    'value' => $discount_value,
                    'apply_to_children' => false
                );
                
                // Continue recursively
                $this->apply_discount_to_descendants( $child->term_id, $discount_type, $discount_value, $discounts );
            }
        }
    }
}
