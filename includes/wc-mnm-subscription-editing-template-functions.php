
<?php
/**
 * Template functions
 *
 * Functions for the templating system.
 *
 * @package  WooCommerce Mix and Match Subscription Editing\Functions
 * @version  1.0.0
 */

defined( 'ABSPATH' ) || exit;


if ( ! function_exists( 'wc_mnm_template_edit_variable_container_order_item' ) ) {

	/**
	 * Edit container template for Mix and Match products.
	 * 
	 * @param WC_Product_Mix_and_Match
	 * @param WC_Order_Item $order_item
	 * @param WC_Order $order
	 * @param  string $context The originating source loading this template
	 */
	function wc_mnm_template_edit_variable_container_order_item( $product, $order_item, $order, $context ) {

		global $product;

		if ( $order_item instanceof WC_Order_Item_Product ) {
			// Need to get the parent product object in this case.
			$product = apply_filters( 'woocommerce_order_item_product', wc_get_product( $order_item->get_product_id() ), $order_item );
		}

		if ( ! $product || ! $product->is_type( 'variable-mix-and-match' ) ) {
			return;
		}

		$classes = array(
			'variable_mnm_form',
			'cart',
			'cart_group',
			'edit_container',
		);

		/**
		 * Form classes.
		 *
		 * @param array - The classes that will print in the <form> tag.
		 * @param obj $product WC_Mix_And_Match of parent product
		 */
		$classes = apply_filters( 'wc_mnm_edit_form_classes', $classes, $product );

		// Get Available variations?
		$get_variations = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
			
		wc_get_template(
			'edit-order-item/edit-variable-container.php',
			array(
				'order_item'           => $order_item,
				'order'                => $order,
				'classes'              => $classes,
				'available_variations' => $get_variations ? $product->get_available_variations(): false,
				'attributes'           => $product->get_variation_attributes(),
				'selected_attributes'  => $product->get_default_attributes(),
				'classes'              => $classes,
				'context'              => $context,
			),
			'',
			WC_MNM_Subscription_Editing::plugin_path() . '/templates/'
		);

	}

}

if ( ! function_exists( 'wc_mnm_template_edit_container_button' ) ) {
	/**
	 * Edit container template for Mix and Match products.
	 * 
	 * @param WC_Mix_and_Match_Product $product
	 * @param WC_Order_Item $order_item
	 * @param WC_Order $order
	 * @param  string $context The originating source loading this template
	 */
	function wc_mnm_template_edit_container_button( $product, $order_item, $order, $context ) {

		if ( $context === 'myaccount' ) {

			// Load the edit container template.
			wc_get_template(
				'edit-order-item/update-container-button.php',
				array(
					'order_item' => $order_item,
					'order'      => $order,
					'container'  => $order_item->get_product(),
					'button_text' => apply_filters( 'wc_mnm_edit_container_button_text', __( 'Update subscription', 'wc-mnm-subscription-editing' ), $order_item, $order ),
				),
				'',
				WC_MNM_Subscription_Editing::plugin_path() . '/templates/'
			);
		}
	}
}

if ( ! function_exists( 'wc_mnm_template_edit_subscription_heading' ) ) {
	/**
	 * Headline prompt text.
	 * 
	 * @param WC_Mix_and_Match_Product $product
	 * @param WC_Order_Item $order_item
	 * @param WC_Order $order
	 * @param  string $context The originating source loading this template
	 */
	function wc_mnm_template_edit_subscription_heading( $product, $order_item, $order, $context) {

		if ( $context === 'myaccount' ) {
			echo '<h3>' . sprintf( esc_html__( 'Edit selections for "%s"', 'wc-mnm-subscription-editing' ), $product->get_name() ) . '</h3>';
		}
		
	}
}

if ( ! function_exists( 'wc_mnm_template_edit_cancel_link' ) ) {
	/**
	 * Display "Cancel edit" link.
	 *
	 * @param WC_Mix_and_Match_Product $product
	 * @param WC_Order_Item $order_item
	 * @param WC_Order $order
	 * @param  string $context The originating source loading this template
	 */
	function wc_mnm_template_edit_cancel_link( $product, $order_item, $order, $context ) {

		if ( $context === 'myaccount' ) {
			// translators: %1$s Screen reader text opening <span> %2$s Product title %3$s Closing </span>
			$cancel_text = sprintf( esc_html_x( 'Cancel edit %1$soptions for %2$s%3$s', 'edit subscription cancel link text', 'wc-mnm-subscription-editing' ),
				'<span class="screen-reader-text">',
				$order_item->get_name(),
				'</span>'
			);
			echo '<div class="wc-mnm-edit-subscription-actions woocommerce-cart-form">
					<div class="actions">
						<button class="button alt wc-mnm-cancel-edit"> ' . wp_kses_post( $cancel_text ) . '</button>
					</div>
				</div>';
		}
	}
}