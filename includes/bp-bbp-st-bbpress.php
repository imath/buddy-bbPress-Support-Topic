<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

//adds the checkbox to let the topic author say he needs support
add_action( 'bbp_theme_after_topic_form_type', 'bp_bbp_st_bbp_two_add_support_type' );

function bp_bbp_st_bbp_two_add_support_type() {
	$checked = false;
	
	if( bbp_is_topic_edit() ){
		$topic_id = bbp_get_topic_id();
		
		$support_status = get_post_meta( $topic_id, 'support_topic', true );
		
		if( !empty( $support_status ) )
			$checked = true;
	}
	
	bp_bbp_st_add_support_type( $checked );
}

//change the title if support is required
function bp_bbp_st_change_topic_title( $title, $id ) {
	$post = get_post( $id );
	
	if( $post->post_type != BP_BBP_ST_TOPIC_CPT_ID )
		return $title;
	
	if( bbp_is_topic_edit() || bbp_is_single_topic() ) {
		return bp_bbp_st_bbp_two_support_sticker( false ) . $title;
	} else {
		return $title;
	}
}

add_action( 'wp_head', 'bp_bbp_st_filter_topic_title');

function bp_bbp_st_filter_topic_title() {
	add_filter('the_title', 'bp_bbp_st_change_topic_title', 99, 2 );
}


// we never know..
add_action( 'bbp_enqueue_scripts', 'bp_bbp_st_enqueue_jquery' );

function bp_bbp_st_enqueue_jquery() {
	wp_enqueue_script('jquery');
}


add_action( 'get_template_part_content', 'bp_bbp_st_check_topic_single', 10, 2 );
add_action( 'get_template_part_form', 'bp_bbp_st_check_topic_single', 10, 2 );

function bp_bbp_st_check_topic_single( $slug, $name ){
	if( in_array( $name, array( 'single-topic', 'topic' ) ) ) {
		add_action( 'wp_footer', 'bp_bbp_st_js_topic_single');
		remove_filter( 'the_title', 'bp_bbp_st_change_topic_title', 99 );
	}
}



function bp_bbp_st_js_topic_single() {
	$loading = __('loading', 'buddy-bbpress-support-topic');
	?>
	<script type="text/javascript">
	jQuery(document).ready(function($){
		
		$('.support-select-status').change(function(){
			
			var indiceChose = $(this)[0].selectedIndex;
			
			$('.support-select-status').each(function(){
				$(this).prop( 'disabled', true );
			});
			
			$('.support-select-box').each(function(){
				$(this).append('<a class="loading support-loader"> <?php echo $loading;?></a>');
			});

			topic_id = $(this).attr('data-topicsupport');
			support_status = $(this).val();

			$.post( ajaxurl, {
				action: 'bb_two_change_support_status',
				'topic_id': topic_id,
				'support_status': support_status
			},
			function(response) {
				
				$('.support-loader').each(function(){
					$(this).remove();
				});
				$('.support-select-status').each(function(){
					$(this).prop( 'disabled', false );
					
					if( indiceChose != $(this)[0].selectedIndex )
						$(this)[0].selectedIndex = indiceChose;
				});
			});
		});
		
	});
	</script>
	<?php
}

add_action('wp_ajax_bb_two_change_support_status', 'bp_bbp_st_bb_two_change_support_status');

function bp_bbp_st_bb_two_change_support_status() {
	if( !empty( $_POST['topic_id'] ) ){
		
		if( empty( $_POST['support_status'] ) ) {
			delete_post_meta( $_POST['topic_id'], 'support_topic' );
		} else {
			update_post_meta( $_POST['topic_id'], 'support_topic', $_POST['support_status'] );
		}
		echo 1;
	} else {
		echo 0;
	}	
	die();
}

add_action('bbp_theme_before_topic_title', 'bp_bbp_st_bbp_two_support_sticker');

function bp_bbp_st_bbp_two_support_sticker( $topic_id = false, $echo = true ) {
	
	if( empty( $topic_id ) )
		$topic_id = bbp_get_topic_id();

	$support_status = get_post_meta( $topic_id, 'support_topic', true );
	$status = '';
	
	if( empty( $support_status ) )
		return;
	
	if ( $support_status == 2 ){
		$status = __('[Resolved] ', 'buddy-bbpress-support-topic') . $topic_title;
		$class = ' topic-resolved';
	}
		
	if ( $support_status == 1 ) {
		$status = __('[Support request] ', 'buddy-bbpress-support-topic') . $topic_title;
		$class = ' topic-not-resolved';
	}
		
	
	if( !empty( $status ) ) {
		if( !empty( $echo ) ) {
			?>
			<span class="bbp-st-topic-support<?php echo $class;?>"><?php echo $status;?></span>
			<?php
		} else {
			return '<span class="bbp-st-topic-support'.$class.'">'. $status .'</span>';
		}
	}
	
}

add_action( 'bbp_new_topic_post_extras', 'bp_bbp_st_save_support_type_for_topic', 10, 1 );

function bp_bbp_st_save_support_type_for_topic( $topic_id = false ) {
	if ( !empty( $_POST['_bp_bbp_st_is_support'] ) ) {
		update_post_meta( $topic_id, 'support_topic', 1 );
	}
}

add_action( 'bbp_edit_topic_post_extras', 'bp_bbp_st_edit_support_type_for_topic', 10, 1 );

function bp_bbp_st_edit_support_type_for_topic( $topic_id = false ) {
	if ( !empty( $_POST['_bp_bbp_st_is_support'] ) ) {
		$support = get_post_meta( $topic_id, 'support_topic', true );
		
		$support = empty( $support ) ? 1 : $support;
		update_post_meta( $topic_id, 'support_topic', $support );
	} else {
		$support = get_post_meta( $topic_id, 'support_topic', true );
		if( !empty( $support ) )
			delete_post_meta( $topic_id, 'support_topic' );
	}
}

function bp_bbp_st_get_output_selectbox( $disabled = 'disabled', $support_status = 1, $topic_id = false ) {
	
	if( empty( $topic_id ) )
		return;
	
	$output = '<span class="support-select-box">';
	$output .= '<select class="support-select-status" name="_support_status" '. $disabled.' data-topicsupport="'.$topic_id.'">';
	$output .= '<option value="1" ';
	
	if( $support_status == 1 )  
		$output .='selected';
	
	$output .= '>'.__('Not resolved', 'buddy-bbpress-support-topic') .'</option>';
	$output .= '<option value="2" ';
	
	if( $support_status == 2 ) 
		$output .='selected';
		
	$output .= '>'. __('Resolved', 'buddy-bbpress-support-topic') .'</option>';
	$output .= '<option value="0" ';
		
	if( empty( $support_status ) ) 
		$output .= 'selected';
		
	$output .= '>'. __('Not a support topic', 'buddy-bbpress-support-topic'). '</option>';
	$output .= '</select></span>';
	
	return apply_filters( 'buddy_bbp_st_get_output_selectbox', $output, $disabled, $support_status, $topic_id );

}

add_filter('bbp_get_topic_admin_links', 'bp_bbp_st_addsupport_admin_links', 10, 2 );


function bp_bbp_st_addsupport_admin_links( $input, $args ) {
	
	if ( !bbp_is_single_topic() )
		return $input;
		
	$defaults = array (
		'id'     => bbp_get_topic_id(),
		'before' => '<span class="bbp-admin-links">',
		'after'  => '</span>',
		'sep'    => ' | ',
		'links'  => array()
	);
	$r = bbp_parse_args( $args, $defaults, 'get_topic_admin_links' );
	
	if ( current_user_can( 'edit_topic', $r['id'] ) )
		$disabled = false;
		
	// now let's check the post meta !
	$support_status = get_post_meta( $r['id'], 'support_topic', true );
	
	if( !empty( $support_status ) ) {
		
		$support_selectbox = bp_bbp_st_get_output_selectbox( $disabled, $support_status, $r['id'] ) . $r['sep'] ;

		$new_span = str_replace( $r['before'], $r['before'] . $support_selectbox, $input );

		return apply_filters( 'bp_bbp_st_addsupport_admin_links', $new_span, $args );
		
	} else {
		return $input;
	}

}

/**
* admin
*/
add_filter( 'bbp_admin_topics_column_headers', 'bp_bbp_st_topics_add_admin_column', 10, 1 );

function bp_bbp_st_topics_add_admin_column( $columns ) {
	$columns['buddy_bbp_st_support'] = __( 'Support', 'buddy-bbpress-support-topic' );
	
	return $columns;
}

add_action( 'bbp_admin_topics_column_data', 'bp_bbp_st_topics_column_data', 10, 2 );

function bp_bbp_st_topics_column_data( $column, $topic_id ) {
	if( $column == 'buddy_bbp_st_support' && !empty( $topic_id ) ) {
		bp_bbp_st_bbp_two_support_sticker( $topic_id );
	}
}

add_action( 'bbp_topic_metabox', 'bp_bbp_st_topic_meta_box', 10, 1);

function bp_bbp_st_topic_meta_box( $topic_id ) {
	$support_status = get_post_meta( $topic_id, 'support_topic', true );
	?>
	<p>
		<strong class="label"><?php _e( 'Support:', 'buddy-bbpress-support-topic' ); ?></strong>
		<label class="screen-reader-text" for="parent_id"><?php _e( 'Support', 'buddy-bbpress-support-topic' ); ?></label>
		<?php echo bp_bbp_st_get_output_selectbox( false, $support_status, $topic_id);?>
	</p>
	<?php
}

add_action( 'save_post', 'bp_bbp_st_topic_meta_box_save', 10, 2 );

function bp_bbp_st_topic_meta_box_save( $topic_id, $post ) {
	
	if( BP_BBP_ST_TOPIC_CPT_ID != get_post_type( $post ) )
		return $topic_id;

	// Bail if doing an autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return $topic_id;

	// Bail if not a post request
	if ( 'POST' != strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return $topic_id;

	// Nonce check
	if ( empty( $_POST['bbp_topic_metabox'] ) || !wp_verify_nonce( $_POST['bbp_topic_metabox'], 'bbp_topic_metabox_save' ) )
		return $topic_id;

	// Bail if current user cannot edit this topic
	if ( !current_user_can( 'edit_topic', $topic_id ) )
		return $topic_id;
		
	$new_status = $_POST['_support_status'];
	
	if( $new_status !== false ) {
		
		if( empty( $new_status ) ) {
			delete_post_meta( $topic_id, 'support_topic' );
		} else {
			update_post_meta( $topic_id, 'support_topic', $new_status );
		}
		
		do_action( 'buddy_bbp_st_topic_meta_box_save', $new_status );
		
	}
	
	return $topic_id;
}