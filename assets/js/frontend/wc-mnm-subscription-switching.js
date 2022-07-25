/* global wc_mnm_subscription_switching_params */
;( function( $ ) {

	/**
	 * Main container object.
	 */
	function WC_MNM_Subscription_Switching() {
    
		/**
		 * Object initialization.
		 */
		this.initialize = function() {

			/**
			 * Bind event handlers.
			 */
			this.bind_event_handlers();

		};

		/**
		 * Events.
		 */
		this.bind_event_handlers = function() {
			$( '.woocommerce-MyAccount-content' ).on( 'click', '.mnm_table_container .wcs-switch-link.ajax-switch', this.loadForm );
			$( '.shop_table' ).on( 'click', '.wc-mnm-cancel-edit', this.cancel );
			$( '.shop_table' ).on( 'submit', '.mnm_form ', this.updateSubscription );
		};

		/**
		 * Load the selected MNM product.
		 */
		this.loadForm = function(e) {

			e.preventDefault();

			let target_url      = $(this).attr( 'href' );
			let url             = new URL( target_url );

			let subscription_id = url.searchParams.get( 'switch-subscription' );
			let item_id         = url.searchParams.get( 'item' );

			let $containerRow  = $(this).closest( '.mnm_table_container' );
			let $all_rows       = $containerRow.nextAll( '.mnm_table_item' ).addBack();
			let columns         = $containerRow.find( 'td' ).length;

			// If currently processing... or clicking on same item, quit now.
			if ( $containerRow.is( '.processing' ) ) {
				return false;
			} else if ( ! $containerRow.is( '.processing' ) ) {
				$all_rows.addClass( 'processing' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
				} );
			}

			$.ajax( {
				url: wc_mnm_subscription_switching_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'mnm_get_container_order_item_edit_form' ),
				type: 'POST',
				data: { 
					item_id: item_id,
					order_id: subscription_id,
					security: wc_mnm_subscription_switching_params.edit_container_nonce // @todo get nonce from button? // @todo: test USER owns this sub?
				},
				success: function( response ) {

					if ( response && 'success' === response.result && response.fragments ) {
					
						$all_rows.fadeOut();

						// Insert display row:
						let $edit_row = $( `<tr data-subscription_id="${subscription_id}" data-item_id="${item_id}" class="wc-mnm-subscription-edit-row"><td class="" colspan="${columns}" ><div class="wc-mnm-edit-container"></div></td></tr>` ).insertBefore( $container_row );
							
						$.each( response.fragments, function( key, value ) {
							$( key ).replaceWith( value );
						});

						// Initilize MNM scripts.
						if ( response.fragments[ 'div.wc-mnm-edit-container' ] ) {
							// Re-attach the replaced result div.
							let $result = $edit_row.find( '.wc-mnm-edit-container' );
							$result.find( '.mnm_form' ).each( function() {
								$(this).wc_mnm_form();
							} );
						}

						$( document.body ).trigger( 'wc_mnm_edit_container_in_shop_subscription_fragments_refreshed', [ response.fragments ] );

					} else {
						location.href = target_url;
					}
					
				},
				complete: function() {
					$all_rows.removeClass( 'processing' ).unblock();
				},
				fail: function() {
					location.href = target_url;
				}
			} );

		};

		/**
		 * Cancel edit.
		 */
		this.cancel = function(e) {
			e.preventDefault();
			let $edit_row = $(this).closest( '.wc-mnm-subscription-edit-row' );
			let $containerRow  = $edit_row.next( '.mnm_table_container' );
			let $all_rows       = $containerRow.nextAll( '.mnm_table_item' ).addBack();

			$edit_row.fadeOut().remove();
			$all_rows.fadeIn();

			$( 'html, body' ).animate( {
				scrollTop: ( $containerRow.offset().top - 100 )
			}, 1000 );
		};


		/**
		 * Update the subscription
		 */
		this.updateSubscription = function(e) {

			e.preventDefault();

			let $editRow = $(this).closest( '.wc-mnm-subscription-edit-row' );
			let Form     =  $(this).wc_get_mnm_script();

			// If currently processing... or clicking on same item, quit now.
			if ( $editRow.is( '.processing' ) ) {
				return false;
			} else if ( ! $editRow.is( '.processing' ) ) {
				$editRow.addClass( 'processing' ).block( {
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				} );
			}

			$.ajax( {
				url: wc_mnm_subscription_switching_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'mnm_update_container_subscription' ),
				type: 'POST',
				data: {
					order_id       : $editRow.data( 'subscription_id' ),
					subscription_id: $editRow.data( 'subscription_id' ),
					item_id        : $editRow.data( 'item_id' ),
					security       : wc_mnm_subscription_switching_params.edit_container_nonce,
					config         : Form.api.get_container_config()
				},
				success: function( data ) {

					if ( data && 'success' === data.result && data.fragments ) {

						// Remove the edit form.
						$editRow.remove();
								
						$.each( data.fragments, function( key, value ) {
							$( key ).replaceWith( value );
						});

						$( document.body ).trigger( 'wc_mnm_subscription_updated_fragments_refreshed', [ data.fragments ] );

					} else {
						// Display error notices 
					}

				},
				complete: function() {
					$editRow.removeClass( 'processing' ).unblock();
				},
				fail: function() {
					// @todo - show alert if failed?
				}
			} );

		};
		  
		// Launch.
		this.initialize();
  
	} // End WC_MNM_Subscription_Switching.
  
	/*-----------------------------------------------------------------*/
	/*  Initialization.                                                */
	/*-----------------------------------------------------------------*/
  
	new WC_MNM_Subscription_Switching( $(this) );
	  
} ) ( jQuery );
  