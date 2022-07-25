<?php
/**
 * Plugin URI: http://www.github.com/kathyisawesome/wc-mnm-subscription-switching
 * Plugin Name: WooCommerce Mix and Match - Subscription Switching
 * Version: 1.0.0-beta-1
 * Description: Mix and Match subscription container contents my account area switching, no cart/checkout
 * Author: Kathy Darling
 * Author URI: http://kathyisawesome.com/
 * Developer: Kathy Darling
 * Developer URI: http://kathyisawesome.com/
 * Text Domain: wc-mnm-subscription-switching
 * Domain Path: /languages
 * 
 * GitHub Plugin URI: https://github.com/kathyisawesome/wc-mnm-subscription-switching
 * Release Asset: true
 *
 * Copyright: © 2020 Kathy Darling
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * The Main WC_MNM_Subscription_Switching class
 **/
if ( ! class_exists( 'WC_MNM_Subscription_Switching' ) ) :

	class WC_MNM_Subscription_Switching {

		/**
		 * constants
		 */
		const VERSION         = '1.0.0-beta-1';
		const REQ_MNM_VERSION = '2.1.0-beta-1';

		/**
		 * var string $notice
		 */
		private static $notice = '';

		/**
		 * WC_MNM_Subscription_Switching Constructor
		 *
		 * @access 	public
		 * @return 	WC_MNM_Subscription_Switching
		 */
		public static function init() {

			// MNM 2.1+ check.
			if ( ! function_exists( 'wc_mix_and_match' ) || version_compare( wc_mix_and_match()->version, self::REQ_MNM_VERSION ) < 0 ) {
				self::$notice = __( 'WooCommerce Mix and Match Subscription Switching requires at least WooCommerce Mix and Match Products version <strong>%1$s</strong>. %2$s', 'wc-mnm-subscription-switching' );
				if ( ! function_exists( 'wc_mix_and_match' ) ) {
					self::$notice = sprintf( self::$notice, self::REQ_MNM_VERSION, __( 'Please install and activate WooCommerce Mix and Match Products.', 'wc-mnm-subscription-switching' ) );
				} else {
					self::$notice = sprintf( self::$notice, self::REQ_MNM_VERSION, __( 'Please update WooCommerce Mix and Match Products.', 'wc-mnm-subscription-switching' ) );
				}

				add_action( 'admin_notices', [ __CLASS__, 'admin_notice' ] );
				return false;
			}

			// APFS check.
			if ( ! defined( 'WCS_ATT_VERSION' )  ) {
				self::$notice = __( 'WooCommerce Mix and Match Subscription Switching requires WooCommerce All Products for Subscriptions. Please install and activate WooCommerce All Products for Subscriptions', 'wc-mnm-subscription-switching' );
				add_action( 'admin_notices', [ __CLASS__, 'admin_notice' ] );
				return false;
			}		

			// Load translation files.
			add_action( 'init', [ __CLASS__, 'load_plugin_textdomain' ] );

			// Register Scripts.
			add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_scripts' ], 20 );

			// Display Scripts.
			add_action( 'woocommerce_account_view-subscription_endpoint', [ __CLASS__, 'load_scripts' ] );
			
			// Ajax handler used to fetch form content for editing container order items.
			add_action( 'wc_ajax_mnm_get_container_order_item_edit_form', [ __CLASS__, 'container_order_item_form' ] );
			// Ajax handler for editing containers in subscriptions.
			add_action( 'wc_ajax_mnm_update_container_subscription', [ __CLASS__ , 'update_container_subscription' ] );

			// Frontend display.
			if ( version_compare( WC_Subscriptions::$version, '4.5.0', '>=' ) ) {
				add_filter( 'woocommerce_subscriptions_switch_link_classes', [ __CLASS__, 'switch_link_classes' ], 10, 4 );
			} else {
				add_filter( 'woocommerce_subscriptions_switch_link', [ __CLASS__, 'switch_link' ], 99, 4 );
			}

			// Edit container form - stripped down add to cart form.
			add_action( 'wc_mnm_edit_container_in_shop_subscription', [ __CLASS__ , 'wc_mnm_template_edit_container' ], 10, 2 );
			
			add_action( 'wc_mnm_edit_container_in_shop_subscription', [ __CLASS__, 'attach_hooks' ], 0 );
			add_action( 'wc_mnm_before_edit_container_form', [ __CLASS__ , 'force_container_styles' ] );

			add_action( 'wc_mnm_add_to_cart_script_parameters', [ __CLASS__ , 'add_to_cart_script_parameters' ] );

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
		 *
		 * @return void
		 */
		public static function load_plugin_textdomain() {
			load_plugin_textdomain( 'wc-mnm-subscription-switching' , false , dirname( plugin_basename( __FILE__ ) ) .  '/languages/' );
		}


		/*-----------------------------------------------------------------------------------*/
		/* Scripts and Styles */
		/*-----------------------------------------------------------------------------------*/

		/**
		 * Register scripts
		 *
		 * @return void
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
					max-wdith: 5em;
				}
				.wc-mnm-edit-container-shop_subscription .wc-mnm-cancel-edit {
					margin-left: 1em;
					margin-right: 1em;
					cursor: pointer;
				}
			";
			wp_add_inline_style( 'wc-mnm-frontend', $custom_css );

			// Frontend scripts.
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			wp_register_script( 'wc-mnm-subscription-switching', plugins_url( '/assets/js/frontend/wc-mnm-subscription-switching' .  $suffix . '.js', __FILE__ ), array( 'wc-add-to-cart-mnm' ), self::VERSION, true );

			$params = array(
				'wc_ajax_url'              => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'edit_container_nonce'     => wp_create_nonce( 'wc_mnm_edit_container' ),
			);

			wp_localize_script( 'wc-mnm-subscription-switching', 'wc_mnm_subscription_switching_params', $params );

		}


		/**
		 * Load the script anywhere the MNM edit form is displayed
		 */
		public static function load_scripts() {
			wp_enqueue_script( 'jquery-blockui' );
			wp_enqueue_script( 'wc-add-to-cart-mnm' );
			wp_enqueue_script( 'wc-mnm-subscription-switching' );
		}


		/*-----------------------------------------------------------------------------------*/
		/* Ajax                                                                              */
		/*-----------------------------------------------------------------------------------*/

		
		/**
		 * Form content used to populate "Configure/Edit" container order items.
		 */
		public static function container_order_item_form() {

			// Populate $order, $product, and $order_item variables.
			extract( self::can_edit() );

			// Initialize form state based on the actual configuration of the container.
			$configuration = WC_Mix_and_Match_Order::get_current_container_configuration( $order_item, $order );

			if ( ! empty( $configuration ) ) {
				$_REQUEST = array_merge( $_REQUEST, WC_Mix_and_Match()->cart->rebuild_posted_container_form_data( $configuration, $product ) );
			}

			ob_start();
			echo '<div class="wc-mnm-edit-container wc-mnm-edit-container-' . $order->get_type() . '">'; // Restore wrapping class as fragments replaces it.
			do_action( 'wc_mnm_edit_container_in_' . $order->get_type(), $order_item, $order ); // @todo - should be order item?
			echo '</div>';

			$form = ob_get_clean();
			
			$response = array(
				'result' => 'success',
				// filter ex: wc_mnm_edit_container_in_shop_order_fragments
				'fragments' => apply_filters( 'wc_mnm_edit_container_in_' . $order->get_type() . '_fragments', array( 'div.wc-mnm-edit-container' => $form ), $order_item, $order ),
			);

			wp_send_json( $response );
		}


		/**
		 * Validates edited/configured containers and returns updated order items.
		 *
		 * @return mixed - If editable will return an array. Otherwise, will return json.
		 */
		public static function can_edit() {
			$response = array(
				'result' => 'failure',
			);

			if ( ! check_ajax_referer( 'wc_mnm_edit_container', 'security', false ) ) {
				$response[ 'reason' ] = esc_html__( 'Security failure', 'wc-mnm-subscription-switching' );
				wp_send_json( $response );
			}

			if ( empty( $_POST['order_id'] ) || empty( $_POST['item_id'] ) ) {
				$response[ 'reason' ] = esc_html__( 'Missing order ID or item ID', 'wc-mnm-subscription-switching' );
				wp_send_json( $response );
			}

			$order   = wc_get_order( wc_clean( $_POST['order_id'] ) );
			$item_id = absint( wc_clean( $_POST['item_id'] ) );

			if ( ! ( $order instanceof WC_Order ) ) {
				$response[ 'reason' ] = esc_html__( 'Not a valid order', 'wc-mnm-subscription-switching' );
				wp_send_json( $response );
			}

			if ( ! current_user_can( 'switch_shop_subscription', $order->get_id() ) ) {
				$response[ 'reason' ] = esc_html__( 'You do not have authority to edit this order', 'wc-mnm-subscription-switching' );
				wp_send_json( $response );
			}

			$order_item = $order->get_item( $item_id );

			if ( ! ( $order_item instanceof WC_Order_Item ) ) {
				$response[ 'reason' ] = esc_html__( 'Not a valid order item', 'wc-mnm-subscription-switching' );
				wp_send_json( $response );
			}

			$product = $order_item->get_product();

			if ( ! ( $product instanceof WC_Product_Mix_and_Match ) ) {
				$response[ 'reason' ] = esc_html__( 'Product is not mix and match type and so cannot be edited', 'wc-mnm-subscription-switching' );
				wp_send_json( $response );
			}

			if ( ! $product->has_child_items() ) {
				$response[ 'reason' ] = esc_html__( 'Container product does not have any available child items', 'wc-mnm-subscription-switching' );
				wp_send_json( $response );
			}

			return array (
				'product'    => $product,
				'order'      => $order,
				'order_item' => $order_item,
			);
		}

		/**
		 * Force tabular layout and hide child links.
		 */
		public static function force_container_styles() {
			// Force tabular override.
			add_filter( 'woocommerce_product_get_layout_override', '__return_true' );

			// Force tabular layout.
			add_filter( 'woocommerce_product_get_layout', function() { return 'tabular'; } );

			// Hide links.
			add_filter( 'woocommerce_product_is_visible', '__return_false' );
			
		}

		/**
		 * Updates the MNM subscription.
		 */
		public static function update_container_subscription() {

			// Populate $order, $product, and $order_item variables.
			extract( self::can_edit() );

			$response = array( 'result' => 'fail', 'reason' => '' );

			$subscription = wcs_get_subscription( $order );

			if ( ! $subscription ) {
				$response[ 'reason' ] = esc_html__( 'Not a valid subscription', 'wc-mnm-subscription-switching' );
			}

			if ( ! WCS_ATT_Product::supports_feature( $product, 'subscription_content_switching' ) ) {
				$response[ 'reason' ] = esc_html__( 'Does not support contents switching', 'wc-mnm-subscription-switching' );
			}

			if ( ! isset( $_POST[ 'config' ] ) ) {

				$response[ 'reason' ] = esc_html__( 'No configuration found', 'wc-mnm-subscription-switching' );

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
							$config_html .= sprintf( __( '%1$d &times; %2$s', 'wc-mnm-subscription-switching' ), 
								apply_filters( 'woocommerce_order_item_quantity', $child_item->get_quantity(), $subscription, $child_item ), // show per-container quantity instead?
								apply_filters( 'woocommerce_order_item_name', $child_item->get_name(), $child_item, false )
							);

							/*
							$config_html .= sprintf( '%1$d &times; %2$s', 'wc-mnm-subscription-switching', 
								apply_filters( 'woocommerce_order_item_quantity', $child_item->get_quantity(), $subscription, $child_item ), // show per-container quantity instead?
								apply_filters( 'woocommerce_order_item_name', $child_item->get_name(), $child_item, false )
							);
							*/
							$config_html .= '</li>';
						}

						$config_html .= '</ul>';
			
					}

					$subscription->add_order_note( sprintf( __( 'Customer modified selections for "%1$s" subscription via the My Account page.<br/><strong>New Configuration</strong>%2$s', 'wc-mnm-subscription-switching' ), $product->get_name(), $config_html ) );

					// Update totals.
					$subscription->calculate_totals();
					$subscription->save();

					// Get new order items fragment.
					ob_start();
					WCS_Template_Loader::get_subscription_totals_table_template( $subscription, true, $subscription->get_order_item_totals() );

					$subscription_items_html = ob_get_clean();

					$response = array(
						'result' => 'success',
						'fragments' => apply_filters( 'wc_mnm_subscription_edit_fragments', array( 'table.order_details' => $subscription_items_html ), $subscription ),
					);

				} else {
					$response[ 'reason' ] = is_wp_error( $new_container_item_id ) ? $new_container_item_id->get_error_message() : esc_html__( 'Items could not be added to subscription', 'wc-mnm-subscription-switching' );
				}

			}
			
			wp_send_json( $response );

		}


		/*-----------------------------------------------------------------------------------*/
		/* Frontend display                                                                  */
		/*-----------------------------------------------------------------------------------*/

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

			if ( $product->is_type( 'mix-and-match' ) ) {
				$apportion_recurring_price = get_option( WC_Subscriptions_Admin::$option_prefix . '_apportion_recurring_price', 'no' );

				$prorate_virtual = in_array( $apportion_recurring_price, array( 'virtual', 'virtual-upgrade' ) );
		
				if ( 'no' === $apportion_recurring_price || ( $prorate_virtual && ! $switch_item->is_virtual_product() ) ) {
					$classes[] = 'ajax-switch';
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

			if ( $product->is_type( 'mix-and-match' ) ) {
				$apportion_recurring_price = get_option( WC_Subscriptions_Admin::$option_prefix . '_apportion_recurring_price', 'no' );

				$prorate_virtual = in_array( $apportion_recurring_price, array( 'virtual', 'virtual-upgrade' ) );
		
				if ( 'no' === $apportion_recurring_price || ( $prorate_virtual && ! $switch_item->is_virtual_product() ) ) {
					$switch_link = str_replace( 'class="', 'class="ajax-switch ', $switch_link );
				}
			}

			return $switch_link;
		}


		/**
		 * Edit container template for Mix and Match products.
		 * 
		 * @param WC_Order_Item $order_item
		 * @param WC_Order $order
		 */
		public static function wc_mnm_template_edit_container( $order_item, $order ) {

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

			if ( ! $product || ! $product->is_type( 'mix-and-match' ) ) {
				return;
			}

			// Enqueue scripts and styles - then, initialize js variables.
			wp_enqueue_script( 'wc-add-to-cart-mnm' );
			wp_enqueue_style( 'wc-mnm-frontend' );

			// Load the edit container template.
			wc_get_template(
				'edit-order/edit-container.php',
				array(
					'order_item' => $order_item,
					'order'      => $order,
					'container'  => $order_item->get_product(),
				),
				'',
				self::plugin_path() . '/templates/'
			);

			// Restore product object.
			$product = $backup_product;

		}


		/**
		 * Edit container template for Mix and Match products.
		 * 
		 * @param WC_Order_Item $order_item
		 * @param WC_Order $order
		 */
		public static function wc_mnm_template_edit_container_button( $order_item, $order ) {
			// Load the edit container template.
			wc_get_template(
				'edit-order/update-container-button.php',
				array(
					'order_item' => $order_item,
					'order'      => $order,
					'container'  => $order_item->get_product(),
					'button_text' => apply_filters( 'wc_mnm_edit_container_button_text', __( 'Update container', 'wc-mnm-subscription-switching' ), $order_item, $order ),
				),
				'',
				self::plugin_path() . '/templates/'
			);
		}

		/**
		 * Customize edit container form for subscription context
		 */
		public static function attach_hooks() {

			// Force tabular display mode.
			add_filter( 'woocommerce_is_visibile', '__return_false' );
			add_filter( 'woocommerce_product_get_layout_override', '__return_true' );
			add_filter( 'woocommerce_product_get_layout', function() { return 'tabular'; } );

			// Add default MNM reset link.
			add_action( 'wc_mnm_before_edit_container_button_wrap', 'wc_mnm_template_reset_link' );

			// Add headings and button texts.
			add_filter( 'wc_mnm_edit_container_button_text', [ __CLASS__, 'update_container_text' ] );
			add_action( 'wc_mnm_before_edit_container_form', [ __CLASS__, 'edit_subscription_headling' ] );
			add_action( 'wc_mnm_edit_container_button', [ __CLASS__, 'wc_mnm_template_edit_container_button' ], 10, 2 );
			add_action( 'wc_mnm_edit_container_button', [ __CLASS__, 'cancel_edit_link' ], 20 );
			add_filter( 'wc_mnm_container_data_attributes', [ __CLASS__, 'data_attributes' ] );
		}

		/**
		 * Modify button text.
		 * 
		 * @param  string $text
		 * @return string
		 */
		public static function update_container_text( $text ) {
			return esc_html__( 'Update subscription', 'wc-mnm-subscription-switching' );
		}

		/**
		 * Headline prompt text.
		 * 
		 * @param  WC_Order_Item_Product $order_item
		 */
		public static function edit_subscription_headling( $order_item ) {
			echo '<h3>' . sprintf( esc_html__( 'Edit selections for "%s"', 'wc-mnm-subscription-switching' ), $order_item->get_name() ) . '</h3>';
		}


		/**
		 * Display "Cancel edit" link.
		 *
		 * @param  WC_Order_Item_Product $order_item
		 */
		public static function cancel_edit_link( $order_item ) {
			global $product;
			// translators: %1$s Screen reader text opening <span> %2$s Product title %3$s Closing </span>
			$cancel_text = sprintf( esc_html_x( 'Cancel edit %1$soptions for %2$s%3$s', 'edit subscription cancel link text', 'wc-mnm-subscription-switching' ),
				'<span class="screen-reader-text">',
				$order_item->get_name(),
				'</span>'
			);
			echo '<a class="wc-mnm-cancel-edit">' . $cancel_text . '</a>';
		}

		/*-----------------------------------------------------------------------------------*/
		/* Scripts                                                                           */
		/*-----------------------------------------------------------------------------------*/

		/**
		 * Script parameters.
		 *
		 * @param  array $params
		 */
		public static function add_to_cart_script_parameters( $params ) {
			return array_merge( $params, [
					// translators: %v is the current quantity message.
					'i18n_edit_valid_fixed_message'                  => _x( '%v Update to continue&hellip;', '[Frontend]', 'wc-mnm-subscription-switching' ),
					// translators: %v is the current quantity message.
					'i18n_edit_valid_min_message'                    => _x( '%v You can select more or update to continue&hellip;', '[Frontend]', 'wc-mnm-subscription-switching' ),
					// translators: %v is the current quantity message. %max is the container maximum.
					'i18n_edit_valid_max_message'                    => _x( '%v You can select up to %max or update to continue&hellip;', '[Frontend]', 'wc-mnm-subscription-switching' ),
					// translators: %v is the current quantity message. %min is the container minimum. %max is the container maximum.
					'i18n_edit_valid_range_message'                  => _x( '%v You may select between %min and %max items or update to continue&hellip;', '[Frontend]', 'wc-mnm-subscription-switching' ),
			] );

		}

		/**
		 * Form parameters - Switch validation message context.
		 *
		 * @param  array $params
		 */
		public static function data_attributes( $atts ) {
			$atts[ 'context' ] = 'edit';
			return $atts;
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
add_action( 'plugins_loaded', [ 'WC_MNM_Subscription_Switching', 'init' ], 20 );
