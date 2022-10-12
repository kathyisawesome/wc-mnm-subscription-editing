
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

if ( ! function_exists( 'wc_mnm_template_edit_container' ) ) {

	/**
	 * Edit container template for Mix and Match products.
	 * 
	 * @param WC_Order_Item $order_item
	 * @param WC_Order $order
	 */
	function wc_mnm_template_edit_container( $order_item, $order ) {
		if ( $order_item->get_variation_id() ) {
			do_action( 'wc_mnm_edit_variable_container', $order_item, $order );
		} else {
			do_action( 'wc_mnm_edit_simple_container', $order_item, $order );
		}
	}

}

if ( ! function_exists( 'wc_mnm_template_edit_simple_container' ) ) {

	/**
	 * Edit container template for Mix and Match products.
	 * 
	 * @param WC_Order_Item $order_item
	 * @param WC_Order $order
	 */
	function wc_mnm_template_edit_simple_container( $order_item, $order ) {

		global $product;
		$backup_product = $product;

		$container = false;

		if ( $order_item instanceof WC_Order_Item_Product ) {
			$container = $order_item->get_product();
		}

		// Swap the global product for this specific container.
		if ( $container ) {
			$product = $container;
		}

		if ( ! $product || ! wc_mnm_is_product_container_type( $product ) ) {
			return;
		}

		$classes = array( 
			'mnm_form',
			'cart',
			'cart_group',
			'edit_container',
			'layout_' . $product->get_layout(),
		);

		/**
		 * Form classes.
		 *
		 * @param array - The classes that will print in the <form> tag.
		 * @param obj $product WC_Mix_And_Match of parent product
		 */
		$classes = apply_filters( 'wc_mnm_edit_form_classes', $classes, $product );

		// Enqueue scripts and styles - then, initialize js variables.
		wp_enqueue_script( 'wc-add-to-cart-mnm' );
		wp_enqueue_style( 'wc-mnm-frontend' );
			
		wc_get_template(
			'edit-order/edit-simple-mix-and-match.php',
			array(
				'order_item' => $order_item,
				'order'      => $order,
				'container'  => $product,
				'classes'    => $classes,
			),
			'',
			WC_MNM_Subscription_Editing::plugin_path() . '/templates/'
		);

		// Restore product object.
		$product = $backup_product;

	}

}

if ( ! function_exists( 'wc_mnm_template_edit_container_button' ) ) {
	/**
	 * Edit container template for Mix and Match products.
	 * 
	 * @param WC_Mix_and_Match_Product $product
	 * @param WC_Order_Item $order_item
	 * @param WC_Order $order
	 */
	function wc_mnm_template_edit_container_button( $product, $order_item, $order ) {
		// Load the edit container template.
		wc_get_template(
			'edit-order/update-container-button.php',
			array(
				'order_item' => $order_item,
				'order'      => $order,
				'container'  => $order_item->get_product(),
				'button_text' => apply_filters( 'wc_mnm_edit_container_button_text', __( 'Update container', 'wc-mnm-subscription-editing' ), $order_item, $order ),
			),
			'',
			WC_MNM_Subscription_Editing::plugin_path() . '/templates/'
		);
	}
}

if ( ! function_exists( 'wc_mnm_template_edit_container_button' ) ) {
	/**
	 * Headline prompt text.
	 * 
	 * @param  WC_Order_Item_Product $order_item
	 */
	function wc_mnm_template_edit_subscription_headling( $order_item ) {
		echo '<h3>' . sprintf( esc_html__( 'Edit selections for "%s"', 'wc-mnm-subscription-editing' ), $order_item->get_name() ) . '</h3>';
	}
}

if ( ! function_exists( 'wc_mnm_template_edit_cancel_link' ) ) {
	/**
	 * Display "Cancel edit" link.
	 *
	 * @param  WC_Order_Item_Product $order_item
	 */
	function wc_mnm_template_edit_cancel_link( $order_item ) {
		global $product;
		// translators: %1$s Screen reader text opening <span> %2$s Product title %3$s Closing </span>
		$cancel_text = sprintf( esc_html_x( 'Cancel edit %1$soptions for %2$s%3$s', 'edit subscription cancel link text', 'wc-mnm-subscription-editing' ),
			'<span class="screen-reader-text">',
			$order_item->get_name(),
			'</span>'
		);
		echo '<a class="wc-mnm-cancel-edit">' . $cancel_text . '</a>';
	}
}