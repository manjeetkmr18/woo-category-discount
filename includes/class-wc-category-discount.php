<?php
/**
 * Main plugin class
 *
 * @package    WC_Category_Discount
 * @subpackage WC_Category_Discount/includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
class WC_Category_Discount {

    /**
     * Plugin instance.
     *
     * @since  1.0.0
     * @var    WC_Category_Discount
     */
    private static $instance = null;

    /**
     * Admin instance.
     *
     * @since  1.0.0
     * @var    WC_Category_Discount_Admin
     */
    public $admin;

    /**
     * Frontend instance.
     *
     * @since  1.0.0
     * @var    WC_Category_Discount_Frontend
     */
    public $frontend;

    /**
     * Get plugin instance.
     *
     * @since  1.0.0
     * @return WC_Category_Discount
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required dependencies.
     *
     * @since  1.0.0
     * @access private
     */
    private function load_dependencies() {
        require_once WC_CATEGORY_DISCOUNT_PLUGIN_DIR . 'includes/class-wc-category-discount-helper.php';
        require_once WC_CATEGORY_DISCOUNT_PLUGIN_DIR . 'includes/class-wc-category-discount-admin.php';
        require_once WC_CATEGORY_DISCOUNT_PLUGIN_DIR . 'includes/class-wc-category-discount-frontend.php';
    }

    /**
     * Initialize hooks.
     *
     * @since  1.0.0
     * @access private
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'admin_init', array( $this, 'check_woocommerce' ) );

        if ( is_admin() ) {
            $this->admin = new WC_Category_Discount_Admin();
        }

        $this->frontend = new WC_Category_Discount_Frontend();
    }

    /**
     * Load plugin textdomain.
     *
     * @since 1.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'woo-category-discount',
            false,
            dirname( WC_CATEGORY_DISCOUNT_PLUGIN_BASENAME ) . '/languages/'
        );
    }

    /**
     * Check if WooCommerce is active.
     *
     * @since 1.0.0
     */
    public function check_woocommerce() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
        }
    }

    /**
     * WooCommerce missing notice.
     *
     * @since 1.0.0
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <?php
                printf(
                    /* translators: %s: WooCommerce plugin name */
                    esc_html__( '%1$s requires %2$s to be installed and active.', 'woo-category-discount' ),
                    '<strong>' . esc_html__( 'WooCommerce Category Discount', 'woo-category-discount' ) . '</strong>',
                    '<strong>' . esc_html__( 'WooCommerce', 'woo-category-discount' ) . '</strong>'
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Plugin activation.
     *
     * @since 1.0.0
     */
    public static function activate() {
        if ( ! get_option( 'wc_category_discounts' ) ) {
            add_option( 'wc_category_discounts', array() );
        }
        add_option( 'wc_category_discount_version', WC_CATEGORY_DISCOUNT_VERSION );
    }

    /**
     * Plugin deactivation.
     *
     * @since 1.0.0
     */
    public static function deactivate() {
        // Clear any cached data.
        if ( function_exists( 'wc_delete_product_transients' ) ) {
            wc_delete_product_transients();
        }
    }
}
