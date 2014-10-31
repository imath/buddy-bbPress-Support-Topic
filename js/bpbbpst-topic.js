(function ($) {

	$( '.support-select-status' ).on( 'change', function(){
		var indiceChose   = $( this )[0].selectedIndex;
		var bpbbpst_nonce = $( this ).parent().find( '#_wpnonce_bpbbpst_support_status' ).val();

		$( '.support-select-status' ).each( function() {
			$( this ).prop( 'disabled', true );
		} );

		$( '.support-select-box' ).each( function() {
			$( this ).append('<a class="loading support-loader"> '+ bpbbpstbbp_vars.loading + '</a>');
		} );

		topic_id = $( this ).attr('data-topicsupport');
		support_status = $( this ).val();

		$.post( ajaxurl, {
			action:                            'bbp_change_support_status',
			'topic_id':                        topic_id,
			'support_status':                  support_status,
			'_wpnonce_bpbbpst_support_status': bpbbpst_nonce
		},
		function(response) {

			if( response != "-1" ) {

				$( '.support-loader' ).each( function() {
					$( this ).remove();
				} );
				$( '.support-select-status' ).each( function() {
					$( this ).prop( 'disabled', false );

					if( indiceChose != $(this)[0].selectedIndex ) {
						$( this )[0].selectedIndex = indiceChose;
					}
				});

			} else {
				alert( bpbbpstbbp_vars.securitycheck );
			}

		} );
	} );

}(jQuery));
