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
        <?php esc_html_e( 'Set percentage or fixed amount discounts for each product category. You can apply discounts to parent categories that will also affect child categories. If a product belongs to multiple categories, the highest discount will be applied.', 'woo-category-discount' ); ?>
    </p>

    <!-- Search and Add Discount Section -->
    <div class="wc-category-discount-search-section">
        <div class="search-container">
            <h2><?php esc_html_e( 'Add New Discount', 'woo-category-discount' ); ?></h2>
            <div class="search-form">
                <label for="category-search"><?php esc_html_e( 'Search Categories:', 'woo-category-discount' ); ?></label>
                <input type="text" id="category-search" placeholder="<?php esc_attr_e( 'Type to search categories...', 'woo-category-discount' ); ?>" />
                <div class="search-results" style="display: none;"></div>
            </div>
        </div>
    </div>

    <form method="post" action="options.php" id="discount-form">
        <?php settings_fields( WC_Category_Discount_Helper::OPTION_NAME ); ?>

        <table class="wp-list-table widefat fixed striped wc-category-discount-table">
            <thead>
                <tr>
                    <th scope="col" class="column-category"><?php esc_html_e( 'Category', 'woo-category-discount' ); ?></th>
                    <th scope="col" class="column-discount-type"><?php esc_html_e( 'Discount Type', 'woo-category-discount' ); ?></th>
                    <th scope="col" class="column-discount-value"><?php esc_html_e( 'Discount Value', 'woo-category-discount' ); ?></th>
                    <th scope="col" class="column-apply-children"><?php esc_html_e( 'Apply to Children', 'woo-category-discount' ); ?></th>
                    <th scope="col" class="column-count"><?php esc_html_e( 'Products', 'woo-category-discount' ); ?></th>
                    <th scope="col" class="column-actions"><?php esc_html_e( 'Actions', 'woo-category-discount' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                    <?php foreach ( $categories as $category ) :
                        $discount_data = WC_Category_Discount_Helper::get_category_discount( $category->term_id );
                        $has_discount = $discount_data['value'] > 0;
                        $row_class = $has_discount ? 'has-discount' : '';
                    ?>
                        <tr class="category-row <?php echo esc_attr( $row_class ); ?>" data-category-id="<?php echo esc_attr( $category->term_id ); ?>">
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
                            <td class="column-discount-type">
                                <select name="<?php echo esc_attr( WC_Category_Discount_Helper::OPTION_NAME ); ?>[<?php echo esc_attr( $category->term_id ); ?>][type]" class="discount-type">
                                    <option value="percentage" <?php selected( $discount_data['type'], 'percentage' ); ?>><?php esc_html_e( 'Percentage (%)', 'woo-category-discount' ); ?></option>
                                    <option value="fixed" <?php selected( $discount_data['type'], 'fixed' ); ?>><?php esc_html_e( 'Fixed Amount', 'woo-category-discount' ); ?></option>
                                </select>
                            </td>
                            <td class="column-discount-value">
                                <input
                                    type="number"
                                    name="<?php echo esc_attr( WC_Category_Discount_Helper::OPTION_NAME ); ?>[<?php echo esc_attr( $category->term_id ); ?>][value]"
                                    value="<?php echo esc_attr( $discount_data['value'] ); ?>"
                                    min="0"
                                    max="<?php echo $discount_data['type'] === 'percentage' ? '100' : '9999'; ?>"
                                    step="0.1"
                                    class="discount-value"
                                    placeholder="0"
                                />
                                <span class="discount-symbol">
                                    <span class="percentage-symbol"><?php echo $discount_data['type'] === 'percentage' ? '%' : get_woocommerce_currency_symbol(); ?></span>
                                </span>
                            </td>
                            <td class="column-apply-children">
                                <input
                                    type="checkbox"
                                    name="<?php echo esc_attr( WC_Category_Discount_Helper::OPTION_NAME ); ?>[<?php echo esc_attr( $category->term_id ); ?>][apply_to_children]"
                                    value="1"
                                    <?php checked( $discount_data['apply_to_children'] ); ?>
                                    class="apply-to-children"
                                />
                                <span class="description"><?php esc_html_e( 'Apply to subcategories', 'woo-category-discount' ); ?></span>
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
                            <td class="column-actions">
                                <?php if ( $has_discount ) : ?>
                                    <button type="button" class="button apply-to-children-btn" data-category-id="<?php echo esc_attr( $category->term_id ); ?>">
                                        <?php esc_html_e( 'Apply to Children', 'woo-category-discount' ); ?>
                                    </button>
                                    <button type="button" class="button remove-discount" data-category-id="<?php echo esc_attr( $category->term_id ); ?>">
                                        <?php esc_html_e( 'Remove', 'woo-category-discount' ); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6">
                            <?php esc_html_e( 'No product categories found. Please create some product categories first.', 'woo-category-discount' ); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php submit_button( __( 'Save Discounts', 'woo-category-discount' ) ); ?>
    </form>

    <!-- Bulk Actions -->
    <div class="wc-category-discount-bulk-actions">
        <h3><?php esc_html_e( 'Bulk Actions', 'woo-category-discount' ); ?></h3>
        <p class="description">
            <?php esc_html_e( 'Apply the same discount to multiple categories at once.', 'woo-category-discount' ); ?>
        </p>
        <div class="bulk-form">
            <label for="bulk-discount-type"><?php esc_html_e( 'Discount Type:', 'woo-category-discount' ); ?></label>
            <select id="bulk-discount-type">
                <option value="percentage"><?php esc_html_e( 'Percentage (%)', 'woo-category-discount' ); ?></option>
                <option value="fixed"><?php esc_html_e( 'Fixed Amount', 'woo-category-discount' ); ?></option>
            </select>
            
            <label for="bulk-discount-value"><?php esc_html_e( 'Value:', 'woo-category-discount' ); ?></label>
            <input type="number" id="bulk-discount-value" min="0" step="0.1" placeholder="0" />
            
            <label>
                <input type="checkbox" id="bulk-apply-children" />
                <?php esc_html_e( 'Apply to subcategories', 'woo-category-discount' ); ?>
            </label>
            
            <button type="button" id="apply-bulk-discount" class="button">
                <?php esc_html_e( 'Apply to Selected Categories', 'woo-category-discount' ); ?>
            </button>
        </div>
    </div>
</div>
