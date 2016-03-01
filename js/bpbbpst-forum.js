(function ($) {
	// Hide or show the mailing list
	$( '.forum-support-settings input:radio' ).on( 'click', function( e ) {
		if ( $( e.target ).prop( 'checked' ) ) {
			if ( 3 === Number( $( e.target ).val() ) ) {
				$( '.bpbbpst-mailing-list' ).hide();
				$( '.bpbbpst-support-guides' ).hide();
			} else {
				$( '.bpbbpst-mailing-list' ).show();

				// Support only extras for the New Topic Form
				if ( 2 === Number( $( e.target ).val() ) ) {
					$( '.bpbbpst-support-guides' ).show();
				} else {
					$( '.bpbbpst-support-guides' ).hide();
				}
			}
		}
	} );

	// Hide or show (if it exists) the support metabox
	if ( $( '#bpbbpst_forum_settings' ).length ) {
		$( '#bbp_forum_type_select' ).on( 'change', function( e ) {
			if ( 'category' === $( e.target ).val() ) {
				$( '#bpbbpst_forum_settings' ).hide();
			} else {
				$( '#bpbbpst_forum_settings' ).show();
			}
		} );
	}
}(jQuery));
