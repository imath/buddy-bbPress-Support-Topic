( function ( $ ) {

	$( 'body' ).on( 'change', '.support-select-status', function( event ) {
		var self = event.target;
		var data = {
			'topic_id'                       : $( self ).data( 'topicsupport' ) || 0,
			'support_status'                 : $( self ).val(),
			'selectedIndex'                  : self.selectedIndex || 0,
			'_wpnonce_bpbbpst_support_status': $( self ).parent().find( '#_wpnonce_bpbbpst_support_status' ).val() || '',
		};

		$( '.support-select-status' ).each( function( i, select ) {
			$( select ).prop( 'disabled', true );
		} );

		$( '.support-select-box' ).each( function( i, span ) {
			$( span ).append( '<a class="loading support-loader">' + bpbbpstbbp_vars.loading + '</a>' );
			wp.a11y.speak( bpbbpstbbp_vars.loading );
		} );

		// Trigger an event to let Plugins do something before the Ajax request
		$( self ).trigger( 'bpbbpstBeforeStatusChange', data );

		// Ajax Set the support status
		$.post( ajaxurl, $.extend( data, { 'action': 'bbp_change_support_status' } ), function( response ) {
			if ( response !== "-1" ) {
				$( '.support-loader' ).each( function( i, a ) {
					$( a ).remove();
				} );

				$( '.support-select-status' ).each( function( i, select ) {
					$( select ).prop( 'disabled', false );

					if ( data.selectedIndex !== self.selectedIndex ) {
						self.selectedIndex = data.selectedIndex;
					}
				} );

				// Trigger an event to inform Plugins the Ajax request succeeded
				$( self ).trigger( 'bpbbpstStatusChangeSuccess', data );
				wp.a11y.speak( bpbbpstbbp_vars.statusChangeSuccess );

				$( '.bbp-st-topic-support' ).html( bpbbpstbbp_vars.supportStatus[data.support_status] );

			} else {
				// Trigger an event to inform Plugins the Ajax request failed
				$( self ).trigger( 'bpbbpstStatusChangeError', data );
				wp.a11y.speak( bpbbpstbbp_vars.statusChangeError );

				alert( bpbbpstbbp_vars.securitycheck );
			}
		} );
	} );

}( jQuery ) );
