
<?php
/**
 * Template hooks
 *
 * @package  WooCommerce Mix and Match Subscription Edting\Functions
 */

defined( 'ABSPATH' ) || exit;

// Edit container form - stripped down add to cart form.
add_action( 'wc_mnm_edit_container_in_shop_subscription', 'wc_mnm_template_edit_container', 10, 2 );

add_action( 'wc_mnm_edit_simple_container', 'wc_mnm_template_edit_simple_container', 10, 2 );
add_action( 'wc_mnm_edit_variable_container', 'wc_mnm_template_edit_variable_container', 10, 2 );

// Port add to cart elements.
add_action( 'wc_mnm_before_edit_container_button_wrap', 'wc_mnm_template_reset_link' );
add_action( 'wc_mnm_edit_container_content', 'wc_mnm_content_loop', 10 );
add_action( 'wc_mnm_edit_container_content', 'wc_mnm_template_reset_link', 20 );
add_action( 'wc_mnm_edit_container_content', 'wc_mnm_template_container_status', 30 );
add_action( 'wc_mnm_edit_container_content', 'wc_mnm_template_edit_container_button', 40, 3 );
add_action( 'wc_mnm_edit_container_content', 'wc_mnm_template_edit_cancel_link', 50 );

// Port variation add to cart elements.
add_action( 'wc_mnm_edit_single_variation', 'woocommerce_single_variation', 10 );
add_action( 'wc_mnm_edit_single_variation', 'wc_mnm_template_single_variation', 15 );
add_action( 'wc_mnm_edit_single_variation', 'wc_mnm_template_edit_container_button', 20, 3 );
add_action( 'wc_mnm_edit_single_variation', 'wc_mnm_template_edit_cancel_link', 30 );
