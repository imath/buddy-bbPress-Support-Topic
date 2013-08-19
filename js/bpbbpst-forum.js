jQuery(document).ready(function($){
	
	// avoids a css style
	$( '.bpbbpst-mailing-list li' ).each( function(){
		$(this).css( 'list-style', 'none' );
	});
	$( '.forum-support-settings li' ).each( function(){
		$(this).css( 'list-style', 'none' );
	});

	$( '.forum-support-settings input:radio' ).on( 'click', function(){

		if( $(this).attr( 'checked') ) {

			if( $(this).val() == 3 )
				$( '.bpbbpst-mailing-list' ).hide();
			else
				$( '.bpbbpst-mailing-list' ).show();
		}
		
	})

});