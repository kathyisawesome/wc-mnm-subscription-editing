<?php
/**
 * Plugin URI: http://www.github.com/kathyisawesome/wc-mnm-subscription-editing
 * Plugin Name: WooCommerce Mix and Match - Subscription Editing
 * Version: 1.0.0-beta.8
 * Description: Mix and Match subscription container contents editing in the my account area, no cart/checkout
 * Author: Kathy Darling
 * Author URI: http://kathyisawesome.com/
 * Developer: Kathy Darling
 * Developer URI: http://kathyisawesome.com/
 * Text Domain: wc-mnm-subscription-editing
 * Domain Path: /languages
 * 
 * GitHub Plugin URI: https://github.com/kathyisawesome/wc-mnm-subscription-editing
 * Release Asset: true
 *
 * Copyright: © 2022 Kathy Darling
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * The Main WC_MNM_Subscription_Editing class
 **/
if ( ! class_exists( 'WC_MNM_Subscription_Editing' ) ) :

	class WC_MNM_Subscription_Editing {

		/**
		 * constants
		 */
		const VERSION = '1.0.0-beta.8';
		const REQ_MNM_VERSION = '2.2.0';

		/**
		 * var string $notice
		 */
		private static $notice = '';

		/**
		 * var bool $is_enqueued
		 */
		private static $is_enqueued = false;

		/**
		 * WC_MNM_Subscription_Editing Constructor
		 *
		 * @access 	public
		 * @return 	WC_MNM_Subscription_Editing
		 */
		public static function init() {

			// MNM 2.1+ check.
			if ( ! function_exists( 'wc_mix_and_match' ) || version_compare( wc_mix_and_match()->version, self::REQ_MNM_VERSION ) < 0 ) {
				self::$notice = __( 'WooCommerce Mix and Match Subscription Editing requires at least WooCommerce Mix and Match Products version <strong>%1$s</strong>. %2$s', 'wc-mnm-subscription-editing' );
				if ( ! function_exists( 'wc_mix_and_match' ) ) {
					self::$notice = sprintf( self::$notice, self::REQ_MNM_VERSION, __( 'Please install and activate WooCommerce Mix and Match Products.', 'wc-mnm-subscription-editing' ) );
				} else {
					self::$notice = sprintf( self::$notice, self::REQ_MNM_VERSION, __( 'Please update WooCommerce Mix and Match Products.', 'wc-mnm-subscription-editing' ) );
				}

				add_action( 'admin_notices', [ __CLASS__, 'admin_notice' ] );
				return false;
			}

			// Sub check.
			if ( ! class_exists( 'WC_Subscriptions_Plugin' )  ) {
				self::$notice = __( 'WooCommerce Mix and Match Subscription Editing requires WooCommerce Subscriptions. Please install and activate WooCommerce Subscriptions', 'wc-mnm-subscription-editing' );
				add_action( 'admin_notices', [ __CLASS__, 'admin_notice' ] );
				return false;
			}	

			// APFS check.
			if ( ! defined( 'WCS_ATT_VERSION' )  ) {
				self::$notice = __( 'WooCommerce Mix and Match Subscription Editing requires WooCommerce All Products for Subscriptions. Please install and activate WooCommerce All Products for Subscriptions', 'wc-mnm-subscription-editing' );
				add_action( 'admin_notices', [ __CLASS__, 'admin_notice' ] );
				return false;
			}		

			// Load translation files.
			add_action( 'init', [ __CLASS__, 'load_plugin_textdomain' ] );

			// Register Scripts.
			add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_scripts' ], 20 );

			// Load template actions/functions later.
			add_action( 'after_setup_theme', [ __CLASS__, 'template_includes' ] );

			// Display Scripts.
			add_action( 'woocommerce_account_view-subscription_endpoint', [ __CLASS__, 'attach_listener_for_scripts' ], 1 );

			// Adjust core ajax.
			add_action( 'wc_mnm_editing_container_in_order', [ __CLASS__, 'add_order_note' ], 10, 4 );
			add_filter( 'wc_mnm_updated_container_in_shop_subscription_fragments', [ __CLASS__, 'updated_subscription_fragments' ], 10, 4 );

			// Frontend display.
			if ( version_compare( WC_Subscriptions::$version, '4.5.0', '>=' ) ) {
				add_filter( 'woocommerce_subscriptions_switch_link_classes', [ __CLASS__, 'switch_link_classes' ], 10, 4 );
			} else {
				add_filter( 'woocommerce_subscriptions_switch_link', [ __CLASS__, 'switch_link' ], 99, 4 );
			}
			
			// Modify the edit form.
			add_action( 'wc_mnm_edit_container_order_item_in_shop_subscription', array( __CLASS__, 'attach_hooks' ), 0, 4 );

			// Variable Mix and Match performance boosts.
			add_filter( 'wc_mnm_eager_load_variations', [ __CLASS__, 'eager_load_variations' ] );

			// Front-end customer facing callbacks for editing.
			add_action( 'wc_ajax_mnm_get_edit_container_order_item_form', [ 'WC_MNM_Ajax', 'edit_container_order_item_form' ] );
			add_action( 'wc_ajax_mnm_update_container_order_item', [ 'WC_MNM_Ajax' , 'update_container_order_item' ] );

		}

		/*-----------------------------------------------------------------------------------*/
		/* Notices */
		/*-----------------------------------------------------------------------------------*/


		/**
		 * Users must update Mix and Match
		 */
		public static function admin_notice() {
			echo '<div class="notice notice-error">';
				echo wpautop( self::$notice );
			echo '</div>';
		}

		/*-----------------------------------------------------------------------------------*/
		/* Localization */
		/*-----------------------------------------------------------------------------------*/


		/**
		 * Make the plugin translation ready
		 */
		public static function load_plugin_textdomain() {
			load_plugin_textdomain( 'wc-mnm-subscription-editing' , false , dirname( plugin_basename( __FILE__ ) ) .  '/languages/' );
		}


		/*-----------------------------------------------------------------------------------*/
		/* Scripts and Styles */
		/*-----------------------------------------------------------------------------------*/

		/**
		 * Register scripts
		 */
		public static function register_scripts() {

			// Inline styles.
			$custom_css = "
				.wc-mnm-edit-container-shop_subscription .mnm_form .woocommerce-loop-product__title {
					font-size: initial;
					margin: initial;
				}
				.wc-mnm-edit-container-shop_subscription .mnm_table .product-thumbnail {
					width: 15%;
				}
				.wc-mnm-edit-container-shop_subscription .mnm_form p {
					font-size: initial;
				}
				.wc-mnm-edit-container-shop_subscription .mnm_form .qty:not(.mnm-checkbox) {
					max-width: 5em;
				}
				.wc-mnm-edit-container-shop_subscription .wc-mnm-cancel-edit {
					margin-left: 1em;
					margin-right: 1em;
					cursor: pointer;
				}
				.wc-mnm-edit-container-shop_subscription .wc-mnm-edit-subscription-actions { display: inline-block; }

				.woocommerce-MyAccount-content.wc-mnm-subscription-editing .order_details tfoot {
					opacity: 30%;
				}
				.woocommerce-MyAccount-content.wc-mnm-subscription-editing .layout_grid .product-thumbnail { max-width: 100%; }
			";
			wp_add_inline_style( 'wc-mnm-frontend', $custom_css );

			// Frontend scripts.
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			$script_path = '/assets/js/frontend/wc-mnm-subscription-editing' .  $suffix . '.js';

			wp_register_script( 'wc-mnm-subscription-editing', plugins_url( $script_path, __FILE__ ), array( 'wc-add-to-cart-mnm' ), wc_mix_and_match()->get_file_version( self::plugin_path() . $script_path, self::VERSION ), true );

			$params = array(
				'wc_ajax_url'               => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'edit_container_nonce'      => wp_create_nonce( 'wc_mnm_edit_container' ),
				'i18n_edit_failure_message' => _x( 'Server error. Your subscription cannot be edited at this time.', '[Frontend]', 'wc-mnm-subscription-editing' ),
			);

			wp_localize_script( 'wc-mnm-subscription-editing', 'wc_mnm_subscription_editing_params', $params );

		}

		/**
		 * Load the scripts on the my subscription page.
		 */
		public static function attach_listener_for_scripts() {
			add_action( 'woocommerce_order_item_meta_end', [ __CLASS__, 'maybe_load_scripts' ], 10, 3 );
		}

		/**
		 * Load the scripts on the my subscription page, only when a mix and match product is present.
		 * 
		 * @param int $item_id The subscription line item ID.
		 * @param WC_Order_Item|array $item The subscription line item.
		 * @param WC_Subscription $subscription The subscription.
		 */
		public static function maybe_load_scripts( $item_id, $order_item, $subscription ) {

			if ( ! self::$is_enqueued && wc_mnm_is_product_container_type( $order_item->get_product() ) ) {

				WC_MNM_Ajax::load_edit_scripts();
	
				if ( class_exists( 'WC_MNM_Variable' ) ) {
					WC_MNM_Variable::get_instance()->load_scripts();
				}

				wp_enqueue_script( 'wc-mnm-subscription-editing' );

				self::$is_enqueued = true;

				do_action( 'wc_mnm_subscription_editing_enqueue_scripts' );
			}

		}


		/*-----------------------------------------------------------------------------------*/
		/* Ajax                                                                              */
		/*-----------------------------------------------------------------------------------*/

	
		/**
		 * Adds order note.
		 * 
		 * @param  WC_Order_Item_Product  $new_order_item
		 * @param  WC_Order_Item_Product  $order_item - the old order item.
		 * @param  $subscription      WC_Subscription
		 * @param  string $source The originating source loading this template
		 */
		public static function add_order_note( $container_item, $old_container_item, $subscription, $source ) {

			if ( $subscription instanceof WC_Subscription && 'myaccount' === $source ) {

				if ( $container_item->get_variation_id() !== $old_container_item->get_variation_id() ) {
					$subscription->add_order_note( sprintf( esc_html__( 'Customer switched variation subscription from "%1$s" to "%2$s" via the My Account page.', 'wc-mnm-subscription-editing' ), $old_container_item->get_name(), $container_item->get_name() ) );
				} else {
					$subscription->add_order_note( sprintf( esc_html__( 'Customer modified selections for "%1$s" subscription via the My Account page.', 'wc-mnm-subscription-editing' ), $container_item->get_name() ) );
				}
				
			}

		}


		/**
		 * Adds to fragments.
		 * 
		 * @param  array $fragments     
		 * @param  $container_item WC_Order_Item
		 * @param  $subscription      WC_Subscription
		 * @param  string $context The originating source loading this template
		 * @return array
		 */
		public static function updated_subscription_fragments( $fragments, $container_item, $subscription, $context ) {

			if ( $subscription instanceof WC_Subscription && 'myaccount' === $context ) {
				// Get new order items fragment.
				ob_start();
				WCS_Template_Loader::get_subscription_totals_table_template( $subscription, true, $subscription->get_order_item_totals() );
				$fragments[ 'table.order_details' ] = ob_get_clean();
			}

			return $fragments;

		}

		/**
		 * Updates the MNM subscription.
		 */
		public static function update_container_subscription() {

			$result = self::can_edit_container();

			if ( is_wp_error( $result ) ) {
				// translators: %s is the validation error message.
				$error = sprintf( esc_html__( 'Cannot edit this mix and match subscription. Reason: %s.', 'wc-mnm-subscription-editing' ), $result->get_error_message() );
				wp_send_json_error( $error );
			}

			// Populate $order, $product, and $order_item variables.
			extract( $result );

			$subscription = wcs_get_subscription( $order );

			if ( ! $subscription ) {
				$error = esc_html__( 'Not a valid subscription', 'wc-mnm-subscription-editing' );
				wp_send_json_error( $error );
			}

			if ( ! WCS_ATT_Product::supports_feature( $product, 'subscription_content_switching' ) ) {
				$error = esc_html__( 'Does not support contents switching', 'wc-mnm-subscription-editing' );
				wp_send_json_error( $error );
			}

			if ( ! isset( $_POST[ 'config' ] ) ) {
				$error = esc_html__( 'No configuration found', 'wc-mnm-subscription-editing' );
				wp_send_json_error( $error );

			} else {

				// Add the config to the $_POSTed data.
				$_POST[ wc_mnm_get_child_input_name( $product->get_id() ) ] = $_POST['config'];

				// Get the fully-realized cart config array.
				$new_config = wc_mix_and_match()->cart->get_posted_container_configuration( $product );

				// Rebuild the $cart_item_data array.
				$cart_item_data = [];

				// Recreate Subs switch cart data.... Cannot reuse WC_Subscriptions_Switcher::set_switch_details_in_cart() since it relies on $_GET
				$next_payment_timestamp = $subscription->get_time( 'next_payment' );

				// If there are no more payments due on the subscription, because we're in the last billing period, we need to use the subscription's expiration date, not next payment date
				if ( false == $next_payment_timestamp ) {
					$next_payment_timestamp = $subscription->get_time( 'end' );
				}

				$cart_item_data['subscription_switch'] = [
					'subscription_id'        => $subscription->get_id(),
					'item_id'                => $order_item->get_id(),
					'next_payment_timestamp' => $next_payment_timestamp,
					'upgraded_or_downgraded' => '',
				];

				// Recreate APFS switch data.
				$cart_item_data = WCS_ATT_Order::restore_cart_item_from_order_item( $cart_item_data, $order_item, $subscription );

				// If new container is added successfully to order...
				$new_container_item_id = wc_mix_and_match()->order->add_container_to_order( $product, $subscription, $order_item->get_quantity(), array( 'configuration' => $new_config ) );

				$new_container_item = WC_Order_Factory::get_order_item( $new_container_item_id );
			
				if ( ! is_wp_error( $new_container_item_id ) && $new_container_item ) {
					
					// Delete old container and its child items.
					$existing_child_items = wc_mnm_get_child_order_items( $order_item );
					$subscription->remove_item( $order_item->get_id() );

					foreach( $existing_child_items as $child_item ) {
						$subscription->remove_item( $child_item->get_id() );
					}

					// Add order note.
					$config_html = '';

					$new_child_items = wc_mnm_get_child_order_items( $new_container_item, $subscription );

					if ( ! empty( $new_child_items ) ) {
			
						$config_html .= '<ul>';
			
						foreach( $new_child_items as $child_item ) {


							// translators: %1$d is the configured child product quantity and %1$s is the configured child product title
							$config_html .= '<li>';
							$config_html .= sprintf( __( '%1$d &times; %2$s', 'wc-mnm-subscription-editing' ), 
								apply_filters( 'woocommerce_order_item_quantity', $child_item->get_quantity(), $subscription, $child_item ), // show per-container quantity instead?
								apply_filters( 'woocommerce_order_item_name', $child_item->get_name(), $child_item, false )
							);

							$config_html .= '</li>';
						}

						$config_html .= '</ul>';
			
					}

					$subscription->add_order_note( sprintf( __( 'Customer modified selections for "%1$s" subscription via the My Account page.<br/><strong>New Configuration</strong>%2$s', 'wc-mnm-subscription-editing' ), $product->get_name(), $config_html ) );

					// Update totals.
					$subscription->calculate_totals();
					$subscription->save();

					// Get new order items fragment.
					ob_start();
					WCS_Template_Loader::get_subscription_totals_table_template( $subscription, true, $subscription->get_order_item_totals() );

					$subscription_items_html = ob_get_clean();

					$fragments = apply_filters( 'wc_mnm_subscription_edit_fragments', array( 'table.order_details' => $subscription_items_html ), $subscription );
					wp_send_json_success( $fragments );

				} else {
					$error = is_wp_error( $new_container_item_id ) ? $new_container_item_id->get_error_message() : esc_html__( 'Items could not be added to subscription', 'wc-mnm-subscription-editing' );
					wp_send_json_error( $error );
				}

			}
			
			wp_die();

		}


		/*-----------------------------------------------------------------------------------*/
		/* Frontend display                                                                  */
		/*-----------------------------------------------------------------------------------*/

		/**
		 * Template files.
		 */
		public static function template_includes() {
			include_once 'includes/wc-mnm-subscription-editing-template-functions.php';
			include_once 'includes/wc-mnm-subscription-editing-template-hooks.php';
		}

		/**
		 * Modify upgrade/downgrade button classes as of Subs 4.5.0.
		 * 
		 * @param array $classes The switch link classes.
		 * @param int $switch_id The order item ID of a subscription line item
		 * @param array $switch_item An order line item
		 * @param object $subscription A WC_Subscription object
		 * @return array
		 */
		public static function switch_link_classes( $classes, $switch_id, $switch_item, $subscription ) {

			$product = $switch_item->get_product();

			if ( wc_mnm_is_product_container_type( $product ) ) {
				$apportion_recurring_price = get_option( WC_Subscriptions_Admin::$option_prefix . '_apportion_recurring_price', 'no' );

				$prorate_virtual = in_array( $apportion_recurring_price, array( 'virtual', 'virtual-upgrade' ) );
		
				if ( 'no' === $apportion_recurring_price || ( $prorate_virtual && ! $switch_item->is_virtual_product() ) ) {
					$classes[] = 'ajax-edit';
				}
			}

			return $classes;
		}

		/**
		 * Modify upgrade/downgrade button.
		 * 
		 * @param string $switch_link The switch link html.
		 * @param int $switch_id The order item ID of a subscription line item
		 * @param array $switch_item An order line item
		 * @param object $subscription A WC_Subscription object
		 * @return string
		 */
		public static function switch_link( $switch_link, $switch_id, $switch_item, $subscription ) {

			$product = $switch_item->get_product();

			if ( wc_mnm_is_product_container_type( $product ) ) {
				$apportion_recurring_price = get_option( WC_Subscriptions_Admin::$option_prefix . '_apportion_recurring_price', 'no' );

				$prorate_virtual = in_array( $apportion_recurring_price, array( 'virtual', 'virtual-upgrade' ) );
		
				if ( 'no' === $apportion_recurring_price || ( $prorate_virtual && ! $switch_item->is_virtual_product() ) ) {
					$switch_link = str_replace( 'class="', 'class="ajax-edit ', $switch_link );
				}
			}

			return $switch_link;
		}


		/**
		 * Customize edit container form for subscription context
		 * 
		 * @param  $product  WC_Product_Mix_and_Match
		 * @param  $order_item WC_Order_Item
		 * @param  $order      WC_Order
		 * @param  string $source The originating source loading this template
		 */
		public static function attach_hooks( $product, $order_item, $order, $source ) {

			if ( 'myaccount' === $source ) {

				// Change button texts and validation context.
				add_filter( 'wc_mnm_edit_container_button_text', [ __CLASS__, 'update_container_text' ] );
			
			}
			
		}

		/**
		 * Modify button text.
		 * 
		 * @param  string $text
		 * @return string
		 */
		public static function update_container_text( $text = '' ) {
			return esc_html__( 'Update subscription', 'wc-mnm-subscription-editing' );
		}
	
		/*-----------------------------------------------------------------------------------*/
		/* Variable Mix and Match                                                            */
		/*-----------------------------------------------------------------------------------*/

		/**
		 * Disable eager loading of mix and match HTML for variations in editing context.
		 * 
		 * @param bool $eager_load
		 */
		public static function eager_load_variations( $eager_load ) {
			return doing_action( 'wc_ajax_mnm_get_edit_container_order_item_form' ) ? false : $eager_load;
		}		
				

		/*-----------------------------------------------------------------------------------*/
		/* Helpers                                                                           */
		/*-----------------------------------------------------------------------------------*/

		/**
		 * Get the plugin path.
		 *
		 * @return string
		 */
		public static function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}


	} //end class: do not remove or there will be no more guacamole for you

endif; // end class_exists check

// Launch the whole plugin.
add_action( 'plugins_loaded', [ 'WC_MNM_Subscription_Editing', 'init' ], 20 );
