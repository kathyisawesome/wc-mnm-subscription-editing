<?php
/**
 * Mix and Match Product Edit Container
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/edit-order/edit-simple-mix-and-match.php.
 *
 * HOWEVER, on occasion WooCommerce Mix and Match will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce Mix and Match/Templates
 * @since   1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

/**
 * wc_mnm_before_edit_container_form hook.
 */
do_action( 'wc_mnm_before_edit_container_form', $product, $order_item, $order );
?>

<form class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" action="<?php echo esc_url( apply_filters( 'wc_mnm_edit_container_form_action', '' ) ); ?>" method="post" enctype="multipart/form-data">

	<?php

	/**
	 * 'wc_mnm_edit_container_content' action.
	 *
	 * @param  WC_Mix_and_Match  $product
     * @param  WC_Order_Item_Product $order_item
     * @param  WC_Order|WC_Subscription $order
	 *
	 * @hooked wc_mnm_content_loop - 10
	 * @hooked wc_mnm_template_reset_link         - 20
	 * @hooked wc_mnm_template_container_status   - 30
	 * @hooked WC_MNM_Subscription_Editing::wc_mnm_template_edit_container_button - 40
	 */
	do_action( 'wc_mnm_edit_container_content', $product, $order_item, $order );

	?>

</form>

<?php
/**
 * wc_mnm_after_edit_container_form hook.
 */
do_action( 'wc_mnm_after_edit_container_form', $product, $order_item, $order );
?>
