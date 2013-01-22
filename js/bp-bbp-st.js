jQuery(document).ready(function($){
	
	if( $('#topic-meta .admin-links').length ) {
		supportSelect = $('#support-select-box').html();

		$('#support-select-box').remove();

		$('#topic-meta .admin-links').append(supportSelect);
		
	} else {
		$('#support-select-box').removeClass('support-left');
	}
	
	
	$('#support-select-status').change(function(){
		$(this).prop( 'disabled', true );
		$(this).parent().append('<a class="loading" id="support-loader">loading</a>');
		
		var topic_id = $(this).attr('rel');
		var support_status = $(this).val();
		var bpbbpst_nonce = $(this).parent().find('#_wpnonce_bpbbpst_support_status').val();
		
		$.post( ajaxurl, {
			action: 'change_support_status',
			'topic_id': topic_id,
			'support_status': support_status,
			'_wpnonce_bpbbpst_support_status': bpbbpst_nonce
		},
		function(response) {
			
			if( response != "-1" ) {
				$('#support-loader').remove();
				$('#support-select-status').prop( 'disabled', false );
			} else {
				alert( bpbbpst_vars.securitycheck );
			}
			
		});
	});
});