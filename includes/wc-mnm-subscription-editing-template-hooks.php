
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

// Add elements to edit form.
add_action( 'wc_mnm_edit_container_order_item_single_variation', 'wc_mnm_template_edit_container_button', 30, 4 );
add_action( 'wc_mnm_edit_container_order_item_single_variation', 'wc_mnm_template_edit_cancel_link', 40, 4 );

