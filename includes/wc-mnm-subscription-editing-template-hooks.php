
<?php
/**
 * Template hooks
 *
 * @package  WooCommerce Mix and Match Subscription Edting\Functions
 */

defined( 'ABSPATH' ) || exit;

// Edit container form - stripped down add to cart form.
add_action( 'wc_mnm_edit_container_order_item_in_shop_subscription', 'wc_mnm_template_edit_variable_container_order_item', 10, 4 );

// Port add to cart elements.wc_mnm_template_edit_subscription_headling
add_action( 'wc_mnm_before_edit_container_order_item_form', 'wc_mnm_template_edit_subscription_heading', 10, 4 );
add_action( 'wc_mnm_edit_container_order_item_content', 'wc_mnm_template_edit_container_button', 40, 4 );
add_action( 'wc_mnm_edit_container_order_item_content', 'wc_mnm_template_edit_cancel_link', 50, 4 );

// Port variation add to cart elements.
add_action( 'wc_mnm_edit_container_order_item_single_variation', 'woocommerce_single_variation', 10 );
add_action( 'wc_mnm_edit_container_order_item_single_variation', 'wc_mnm_template_single_variation', 15 );
add_action( 'wc_mnm_edit_container_order_item_single_variation', 'wc_mnm_template_edit_container_button', 20, 4 );
add_action( 'wc_mnm_edit_container_order_item_single_variation', 'wc_mnm_template_edit_cancel_link', 30, 4 );

