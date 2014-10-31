(function ($) {
	// avoids a css style
	$( '.bpbbpst-mailing-list li' ).each( function() {
		$( this ).css( 'list-style', 'none' );
	} );

	$( '.forum-support-settings li' ).each( function() {
		$( this ).css( 'list-style', 'none' );
	} );

	$( '.forum-support-settings input:radio' ).on( 'click', function() {

		if( $( this ).attr( 'checked') ) {

			if( $(this).val() == 3 ) {
				$( '.bpbbpst-mailing-list' ).hide();
			} else {
				$( '.bpbbpst-mailing-list' ).show();
			}
		}

	} );

	// Hide or show (if it exists) the support metabox
	if ( $( '#bpbbpst_forum_settings' ).length ) {
		$( '#bbp_forum_type_select' ).on( 'change', function() {
			if ( 'category' == $( this ).val() ) {
				$( '#bpbbpst_forum_settings' ).hide();
			} else {
				$( '#bpbbpst_forum_settings' ).show();
			}
		} );
	}

}(jQuery));
