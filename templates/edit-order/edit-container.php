<?php
/**
 * Mix and Match Product Edit form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/edit-order/edit-container.php.
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
do_action( 'wc_mnm_before_edit_container_form', $order_item, $order );
?>

<form class="mnm_form edit_container <?php echo esc_attr( 'layout_' . $product->get_layout() ); ?>" action="<?php echo esc_url( apply_filters( 'wc_mnm_edit_container_form_action', '' ) ); ?>" method="post" enctype="multipart/form-data" >

	<?php

	/**
	 * 'wc_mnm_content_loop' action.
	 *
	 * @param  WC_Mix_and_Match  $product
	 * @since  1.8.0
	 *
	 * @hooked wc_mnm_content_loop - 10
	 */
	do_action( 'wc_mnm_content_loop', $product );

	?>

    <div class="mnm_cart mnm_data cart" <?php echo $product->get_data_attributes( array( 'context' => 'edit' ) ); ?>>

    <?php

        if ( $product->is_purchasable() ) {
            /**
             * wc_mnm_before_edit_container_button_wrap hook.
             */
            do_action( 'wc_mnm_before_edit_container_button_wrap' );
        ?>

            <div class="mnm_wrap mnm_button_wrap">

                <p class="mnm_price" style="display:none;"></p>

                <div class="mnm_message woocommerce-message" style="display:none;">
                    <ul class="msg mnm_message_content">
                        <li><?php echo wc_mnm_get_quantity_message( $product ); ?></li>
                    </ul>
                </div>

                <?php

                // MnM Availability.
                ?>
                <div class="mnm_availability">

                    <?php

                    // Availability html.
                    echo wc_get_stock_html( $product );

                    ?>
                    
                </div>
                <?php
                /**
                 * wc_mnm_edit_container_button hook.
                 * @hooked wc_mnm_template_edit_container_button - 10
                 */
                do_action( 'wc_mnm_edit_container_button', $order_item, $order );

                ?>


            </div>
        

            <?php
            /**
             * wc_mnm_after_edit_container_button_wrap hook.
             */
            do_action( 'wc_mnm_after_edit_container_button_wrap', $order_item, $order );

            ?>

    <?php } else { ?>

        <p class="mnm_container_unavailable stock out-of-stock"><?php echo wp_kses_post( $purchasable_notice ); ?></p>

    <?php } ?>

    </div>

</form>

<?php
/**
 * wc_mnm_after_edit_container_form hook.
 */
do_action( 'wc_mnm_after_edit_container_form' );
?>
