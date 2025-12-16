<?php
/**
 * Admin settings page template
 *
 * @package    WC_Category_Discount
 * @subpackage WC_Category_Discount/templates/admin
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap wc-category-discount-wrap">
    <h1><?php esc_html_e( 'Category Discounts', 'woo-category-discount' ); ?></h1>

    <?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Discount settings saved successfully!', 'woo-category-discount' ); ?></p>
        </div>
    <?php endif; ?>

    <p class="description">
        <?php esc_html_e( 'Set percentage discounts for each product category. The discount will be applied to all products in that category. If a product belongs to multiple categories, the highest discount will be applied.', 'woo-category-discount' ); ?>
    </p>

    <form method="post" action="options.php">
        <?php settings_fields( WC_Category_Discount_Helper::OPTION_NAME ); ?>

        <table class="wp-list-table widefat fixed striped wc-category-discount-table">
            <thead>
                <tr>
                    <th scope="col" class="column-category"><?php esc_html_e( 'Category', 'woo-category-discount' ); ?></th>
                    <th scope="col" class="column-discount"><?php esc_html_e( 'Discount (%)', 'woo-category-discount' ); ?></th>
                    <th scope="col" class="column-count"><?php esc_html_e( 'Products', 'woo-category-discount' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                    <?php foreach ( $categories as $category ) : ?>
                        <tr>
                            <td class="column-category">
                                <span class="category-name">
                                    <span class="dashicons dashicons-category"></span>
                                    <?php echo esc_html( $category->name ); ?>
                                    <?php if ( $category->parent ) : ?>
                                        <span class="category-parent">
                                            <?php
                                            $parent = get_term( $category->parent, 'product_cat' );
                                            if ( $parent && ! is_wp_error( $parent ) ) {
                                                /* translators: %s: Parent category name */
                                                printf( esc_html__( '(in %s)', 'woo-category-discount' ), esc_html( $parent->name ) );
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="column-discount">
                                <input
                                    type="number"
                                    name="<?php echo esc_attr( WC_Category_Discount_Helper::OPTION_NAME ); ?>[<?php echo esc_attr( $category->term_id ); ?>]"
                                    value="<?php echo esc_attr( isset( $discounts[ $category->term_id ] ) ? $discounts[ $category->term_id ] : 0 ); ?>"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                    class="small-text"
                                />
                                <span class="description">%</span>
                            </td>
                            <td class="column-count">
                                <span class="product-count">
                                    <?php
                                    printf(
                                        /* translators: %d: Number of products */
                                        esc_html( _n( '%d product', '%d products', $category->count, 'woo-category-discount' ) ),
                                        esc_html( $category->count )
                                    );
                                    ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3">
                            <?php esc_html_e( 'No product categories found. Please create some product categories first.', 'woo-category-discount' ); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php submit_button( __( 'Save Discounts', 'woo-category-discount' ) ); ?>
    </form>
</div>
