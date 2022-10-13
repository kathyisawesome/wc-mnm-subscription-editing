/* global wc_mnm_subscription_editing_params */
;( function( $ ) {

	/**
	 * Main container object.
	 */
	function WC_MNM_Subscription_Editing() {
    
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
			$( '.shop_table' ).on( 'click.wc-mnm-subscription-editing', '.mnm_table_container .wcs-switch-link.ajax-edit', this.loadForm );
			$( '.shop_table' ).on( 'click.wc-mnm-subscription-editing', '.wc-mnm-cancel-edit', this.cancel );
			$( '.shop_table' ).on( 'submit.wc-mnm-subscription-editing', '.mnm_form, .variable_mnm_form', this.updateSubscription );

			$( document.body ).on( 'wc_mnm_subscription_updated_fragments_refreshed', this.scroll );
			$( document.body ).on( 'wc_mnm_edit_container_in_shop_subscription_cancel', this.scroll );
			
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
				url: wc_mnm_subscription_editing_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'mnm_get_container_order_item_edit_form' ),
				type: 'POST',
				data: {
					wc_mnm_get_container_edit_form: true,
					item_id: item_id,
					order_id: subscription_id,
					security: wc_mnm_subscription_editing_params.edit_container_nonce
				},
				success: function( response ) {

					if ( response.success && response.data ) {
					
						$all_rows.fadeOut();

						// Insert display row:
						let $editRow = $( `<tr data-subscription_id="${subscription_id}" data-item_id="${item_id}" class="wc-mnm-subscription-edit-row"><td class="" colspan="${columns}" ><div class="wc-mnm-edit-container"></div></td></tr>` ).insertBefore( $containerRow );
							
						$.each( response.data, function( key, value ) {
							$( key ).replaceWith( value );
						});

						// Initilize MNM scripts.
						if ( response.data[ 'div.wc-mnm-edit-container' ] ) {
							// Re-attach the replaced result div.
							let $result = $editRow.find( '.wc-mnm-edit-container' );
							$result.find( '.mnm_form' ).each( function() {
								$(this).wc_mnm_form();
							} );
						}

						$( document.body ).trigger( 'wc_mnm_edit_container_in_shop_subscription_fragments_refreshed', [ response.data ] );

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
			let $editRow = $(this).closest( '.wc-mnm-subscription-edit-row' );
			let $containerRow  = $editRow.next( '.mnm_table_container' );
			let $all_rows       = $containerRow.nextAll( '.mnm_table_item' ).addBack();

			$editRow.fadeOut().remove();
			$all_rows.fadeIn();

			$( document.body ).trigger( 'wc_mnm_edit_container_in_shop_subscription_cancel' );

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
				url: wc_mnm_subscription_editing_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'mnm_update_container_subscription' ),
				type: 'POST',
				data: {
					order_id       : $editRow.data( 'subscription_id' ),
					subscription_id: $editRow.data( 'subscription_id' ),
					item_id        : $editRow.data( 'item_id' ),
					security       : wc_mnm_subscription_editing_params.edit_container_nonce,
					config         : Form.api.get_container_config()
				},
				success: function( response ) {

					if ( response.success && response.data ) {

						// Remove the edit form.
						$editRow.remove();
								
						$.each( response.data, function( key, value ) {
							$( key ).replaceWith( value );
						});

						$( document.body ).trigger( 'wc_mnm_subscription_updated_fragments_refreshed', [ response.data ] );

					} else {
						window.alert( response.data );
					}

				},
				complete: function() {
					$editRow.removeClass( 'processing' ).unblock();
				},
				fail: function() {
					window.alert( wc_mnm_subscription_editing_params.i18n_edit_failure_message );
				}
			} );

		};

		/**
		 * Scroll to totals
		 */
		this.scroll = function() {
			$( 'html, body' ).animate( {
				scrollTop: ( $( '.shop_table.order_details' ).offset().top - 100 )
			}, 1000 );
		};
		  
		// Launch.
		this.initialize();
  
	} // End WC_MNM_Subscription_Editing.
  
	/*-----------------------------------------------------------------*/
	/*  Initialization.                                                */
	/*-----------------------------------------------------------------*/
  
	new WC_MNM_Subscription_Editing( $(this) );
	  
} ) ( jQuery );
  