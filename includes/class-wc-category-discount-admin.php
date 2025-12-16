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
        add_action( 'update_option_' . WC_Category_Discount_Helper::OPTION_NAME, array( $this, 'on_settings_update' ) );
        add_filter( 'plugin_action_links_' . WC_CATEGORY_DISCOUNT_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
    }

    /**
     * Add admin menu.
     *
     * @since 1.0.0
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Category Discounts', 'woo-category-discount' ),
            __( 'Category Discounts', 'woo-category-discount' ),
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
            foreach ( $input as $category_id => $discount ) {
                $category_id = absint( $category_id );
                $discount    = floatval( $discount );

                if ( $category_id > 0 && $discount >= 0 && $discount <= 100 ) {
                    $sanitized[ $category_id ] = $discount;
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
            '<a href="' . esc_url( admin_url( 'admin.php?page=wc-category-discounts' ) ) . '">' . esc_html__( 'Settings', 'woo-category-discount' ) . '</a>',
        );

        return array_merge( $plugin_links, $links );
    }
}
