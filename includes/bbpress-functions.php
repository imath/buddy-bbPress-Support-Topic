<?php

/**
 * Buddy-bbPress Support Topic bbPress functions
 *
 * @package    Buddy-bbPress Support Topic
 * @subpackage bbpress-functions
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Adds the checkbox to let the topic author say he needs support
 *
 * @uses   bbp_is_topic_edit() to check if topic is being edited
 * @uses   bbp_get_topic_id() to get the topic id if the topic is being edited
 * @uses   get_post_meta() to eventually get the support status
 * @uses   bpbbpst_define_support() to display the checkbox
 * @author imath
 */
function bpbbpst_bbpress_define_support() {
	$checked = false;
	
	if( bbp_is_topic_edit() ){
		$topic_id = bbp_get_topic_id();
		
		$support_status = get_post_meta( $topic_id, '_bpbbpst_support_topic', true );
		
		if( !empty( $support_status ) )
			$checked = true;
	}
	
	bpbbpst_define_support( $checked );
}

add_action( 'bbp_theme_before_topic_form_submit_wrapper', 'bpbbpst_bbpress_define_support' );


/**
 * Change the title of the topic to add support mention
 *
 * @param  string $title the title of the topic
 * @param  int $id the id of the topic
 * @return string $title the title with the eventual support mention
 * @uses   bbp_is_topic() to check for topic post type
 * @uses   bbp_is_topic_edit() to check if topic is being edited
 * @uses   bbp_is_single_topic() to check if topic is on single template
 * @uses   bpbbpst_bbpress_add_support_mention() to return the mention and the title
 * @author imath
 */
function bpbbpst_bbpress_change_topic_title( $title, $id ) {
	
	if( !bbp_is_topic( $id ) )
			return $title;
	
	if( bbp_is_topic_edit() || bbp_is_single_topic() ) {
		return bpbbpst_bbpress_add_support_mention( false ) . $title;
	} else {
		return $title;
	}
}


/**
 * Hooks wp_head to launch a filter to the_title to change the title
 *
 * it does it very late to avoid filtering the get_the_title() WordPress
 * function that may be used by other plugin hooking wp_head such as jetpack.
 *
 * @author imath
 */
function bpbbpst_bbpress_filter_topic_title() {
	add_filter('the_title', 'bpbbpst_bbpress_change_topic_title', 99, 2 );
}

add_action( 'wp_head', 'bpbbpst_bbpress_filter_topic_title', 99 );


/**
 * Hooks wp_enqueue_scripts to load needed js or css
 *
 * It only loads the scripts or stylesheets only on the template
 * where we need it (not on all page of the blog!)
 *
 * @uses   bbp_is_single_topic() to check if topic is on single template
 * @uses   wp_enqueue_script() to enqueue the script and its dependencies
 * @uses   wp_localize_script() to ensure translation of messages
 * @author imath
 */
function bpbbpst_bbpress_enqueue_scripts(){
	
	/* 
	In BuddyPress 1.7, we should use bbPress 2.2.3 for group forums
	Problem is that when on group forums, bbp_is_single_topic() is becoming true too late :(
	so we need to check for bp_is_group_forum_topic(), if this function exists ;)
	*/
	$bbpress_load_scripts = false;
	
	if( bbp_is_single_topic() )
		$bbpress_load_scripts = true;
	elseif( function_exists( 'bp_is_group_forum_topic' ) && bp_is_group_forum_topic() )
		$bbpress_load_scripts = true;
	
	if( $bbpress_load_scripts ) {
		wp_enqueue_script( 'bpbbpst-bbp-js', BPBBPST_PLUGIN_URL_JS . '/bpbbpst-bbp.js', array( 'jquery' ) );
		wp_localize_script( 'bpbbpst-bbp-js', 'bpbbpstbbp_vars', array(
					'securitycheck' => __( 'Security check failed', 'buddy-bbpress-support-topic' ),
					'loading'       => __( 'loading', 'buddy-bbpress-support-topic' )
				)
			);
	}
}

add_action( 'wp_enqueue_scripts', 'bpbbpst_bbpress_enqueue_scripts' );


/**
 * Removes the filter to the_title to avoid the support mention to be added in the_content
 *
 * @param  array $templates the hierarchy of bbPress templates
 * @param  string $slug the slug template part
 * @param  string $name the optionnal template part
 * @return array $templates unchanged
 * @author imath
 */
function bpbbpst_bbpress_topic_is_single( $templates, $slug, $name ){
	
	if( in_array( $name, array( 'single-topic', 'topic' ) ) ) {
		remove_filter( 'the_title', 'bpbbpst_bbpress_change_topic_title', 99 );
	}
	
	return $templates;
}

add_filter( 'bbp_get_template_part', 'bpbbpst_bbpress_topic_is_single', 99, 3 );


/**
 * Handles Ajax calls and change the support status
 *
 * @uses   wp_verify_nonce() to check nonce
 * @uses   delete_post_meta() to delete the support status
 * @uses   intval() to sanitize input
 * @uses   update_post_meta() to edit the support status
 * @uses   die() to make sure the script is exited
 * @author imath
 */
function bpbbpst_bbpress_change_support_status() {
	
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	if( !wp_verify_nonce( $_POST['_wpnonce_bpbbpst_support_status'], 'bpbbpst_support_status' ) ) {
		echo -1;
		die();
	}

	if( !empty( $_POST['topic_id'] ) ){
		
		if( empty( $_POST['support_status'] ) ) {
			delete_post_meta( $_POST['topic_id'], '_bpbbpst_support_topic' );
		} else {
			update_post_meta( $_POST['topic_id'], '_bpbbpst_support_topic', intval( $_POST['support_status'] ) );
		}
		echo 1;
	} else {
		echo 0;
	}	
	die();
}

add_action( 'wp_ajax_bbp_change_support_status', 'bpbbpst_bbpress_change_support_status' );


/**
 * Hooks bbp_theme_before_topic_title to add the support mention before the topic title
 *
 * @param  int $topic_id the topic id, defaults to false
 * @param  boolean $echo true to display, false to return
 * @return string the html output containing the support status
 * @uses   bbp_get_topic_id() to get the topic id if not set
 * @uses   get_post_meta() to get the support status
 * @uses   wp_verify_nonce() to check nonce
 * @author imath
 */
function bpbbpst_bbpress_add_support_mention( $topic_id = false, $echo = true ) {
	$class = false;
	
	if( empty( $topic_id ) )
		$topic_id = bbp_get_topic_id();

	$support_status = get_post_meta( $topic_id, '_bpbbpst_support_topic', true );
	$status = '';
	
	if( empty( $support_status ) )
		return;
	
	if ( $support_status == 2 ){
		$status = __( '[Resolved] ', 'buddy-bbpress-support-topic' );
		$class = ' topic-resolved';
	}
		
	if ( $support_status == 1 ) {
		$status = __( '[Support request] ', 'buddy-bbpress-support-topic' );
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

add_action( 'bbp_theme_before_topic_title', 'bpbbpst_bbpress_add_support_mention' );


/**
 * Hooks bbp_new_topic_post_extras to safely insert the support request
 *
 * @param  int $topic_id the id of the topic, defaults to false
 * @uses   wp_verify_nonce() to check nonce
 * @uses   update_post_meta() to set the support status to support request
 * @author imath
 */
function bpbbpst_bbpress_save_support_type( $topic_id = false ) {
	// if safe then store
	if ( !empty( $_POST['_bp_bbp_st_is_support'] ) && wp_verify_nonce( $_POST['_wpnonce_bpbbpst_support_define'], 'bpbbpst_support_define' ) ) {
		// no need to sanitize value as i arbitrary set the support topic option to 1
		update_post_meta( $topic_id, '_bpbbpst_support_topic', 1 );
	}
}

add_action( 'bbp_new_topic_post_extras', 'bpbbpst_bbpress_save_support_type', 10, 1 );


/**
 * Hooks bbp_edit_topic_post_extras to update the support status when topic is edited
 *
 * @param  int $topic_id the id of the topic, defaults to false
 * @uses   wp_verify_nonce() to check nonce
 * @uses   get_post_meta() to get the stored status if it exists
 * @uses   update_post_meta() to edit the support status to the previous status, defaults to support request
 * @uses   delete_post_meta() to remove the support status
 * @author imath
 */
function bpbbpst_bbpress_edit_support_type( $topic_id = false ) {

	if( !wp_verify_nonce( $_POST['_wpnonce_bpbbpst_support_define'], 'bpbbpst_support_define' ) )
		return;

	if ( !empty( $_POST['_bp_bbp_st_is_support'] ) ) {
		$support = get_post_meta( $topic_id, '_bpbbpst_support_topic', true );
		
		$support = empty( $support ) ? 1 : $support;
		update_post_meta( $topic_id, '_bpbbpst_support_topic', $support );
	} else {
		$support = get_post_meta( $topic_id, '_bpbbpst_support_topic', true );
		if( !empty( $support ) )
			delete_post_meta( $topic_id, '_bpbbpst_support_topic' );
	}
}

add_action( 'bbp_edit_topic_post_extras', 'bpbbpst_bbpress_edit_support_type', 10, 1 );


/**
 * Outputs the selectbox of available support status
 *
 * @param  int $support_status the current support status
 * @param  int $topic_id the id of the topic, defaults to false
 * @return $output
 * @uses   selected() to activate the current option
 * @uses   wp_nonce_field() to create the security token
 * @uses   apply_filters() to call the 'bpbbpst_bbpress_get_selectbox' hook
 * @author imath
 */
function bpbbpst_bbpress_get_selectbox( $support_status = 1, $topic_id = false ) {
	
	if( empty( $topic_id ) )
		return;
	
	$output = '<span class="support-select-box">';
	$output .= '<select class="support-select-status" name="_support_status" data-topicsupport="'.$topic_id.'">';
	
	if( $topic_id == 'adminlist' )
		$output .= '<option value="3">' . __('All support status') .'</option>';
	
	$output .= '<option value="1" ';
	$output .= selected( $support_status, 1, false );
	$output .= '>'.__('Not resolved', 'buddy-bbpress-support-topic') .'</option>';
	
	$output .= '<option value="2" ';
	$output .= selected( $support_status, 2, false );
	$output .= '>'. __('Resolved', 'buddy-bbpress-support-topic') .'</option>';
	
	if( $topic_id != 'adminlist' ) {
		$output .= '<option value="0" ';
		$output .= selected( $support_status, false, false );
		$output .= '>'. __('Not a support topic', 'buddy-bbpress-support-topic'). '</option>';
	}
	
	$output .= '</select>';

	// nonce field
	$output .= wp_nonce_field( 'bpbbpst_support_status', '_wpnonce_bpbbpst_support_status', true, false );
	$output .= '</span>';
	
	return apply_filters( 'bpbbpst_bbpress_get_selectbox', $output, $support_status, $topic_id );

}


/**
 * Hooks bbp_get_topic_admin_links to eventually add the selectbox of available support status
 *
 * @param  string $input the html containing bbPress admin links
 * @param  string $args the array containing eventual args
 * @return string $input or the input with the selectbox
 * @uses   bbp_is_single_topic() to return input if not on single topic
 * @uses   bbp_get_topic_id() to get the topic id
 * @uses   bbp_parse_args() to merge user defined arguments into defaults array
 * @uses   current_user_can() to check user capacity to edit the topic
 * @uses   get_post_meta() to get the stored status if it exists
 * @uses   bpbbpst_bbpress_get_selectbox() to get the selectbox of available status
 * @uses   apply_filters() to call the 'bpbbpst_bbpress_support_admin_links' hook
 * @author imath
 */
function bpbbpst_bbpress_support_admin_links( $input, $args ) {
	
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
	
	// now let's check the post meta !
	$support_status = get_post_meta( $r['id'], '_bpbbpst_support_topic', true );
	
	if ( current_user_can( 'edit_topic', $r['id'] ) && !empty( $support_status ) ) {
		
		$support_selectbox = bpbbpst_bbpress_get_selectbox( $support_status, $r['id'] ) . $r['sep'] ;

		$new_span = str_replace( $r['before'], $r['before'] . $support_selectbox, $input );

		return apply_filters( 'bpbbpst_bbpress_support_admin_links', $new_span, $args );
		
	} else {
		return $input;
	}

}

add_filter( 'bbp_get_topic_admin_links', 'bpbbpst_bbpress_support_admin_links', 10, 2 );


/**
* WordPress admin part of the plugin
*
* In the Topic menu of WordPress admin, these functions help to moderate
* the different support status.
*/


/**
 * Hooks bbp_admin_topics_column_headers to add a support status admin column header
 *
 * @param  array $columns, the bbPress topic list of columns 
 * @return array $columns, with the new support status column
 * @author imath
 */
function bpbbpst_bbpress_topics_admin_column( $columns ) {
	$columns['buddy_bbp_st_support'] = __( 'Support', 'buddy-bbpress-support-topic' );
	
	return $columns;
}

add_filter( 'bbp_admin_topics_column_headers', 'bpbbpst_bbpress_topics_admin_column', 10, 1 );


/**
 * Hooks bbp_admin_topics_column_data to inform about support status for the topics
 *
 * @param  string $column the current column of the topics table
 * @param  int $topic_id the id of the topic 
 * @uses   bpbbpst_bbpress_add_support_mention() to eventually inform on the support status
 * @author imath
 */
function bpbbpst_bbpress_topics_column_data( $column, $topic_id ) {
	if( $column == 'buddy_bbp_st_support' && !empty( $topic_id ) ) {
		bpbbpst_bbpress_add_support_mention( $topic_id );
	}
}

add_action( 'bbp_admin_topics_column_data', 'bpbbpst_bbpress_topics_column_data', 10, 2 );


/**
 * Hooks bbp_topic_metabox to add a selectbox to moderate support status
 *
 * @param  int $topic_id the id of the topic
 * @uses   get_post_meta() to get the stored status if it exists
 * @uses   bpbbpst_bbpress_get_selectbox() to display the support selectbox
 * @author imath
 */
function bpbbpst_bbpress_topic_meta_box( $topic_id ) {
	$support_status = get_post_meta( $topic_id, '_bpbbpst_support_topic', true );
	?>
	<p>
		<strong class="label"><?php _e( 'Support:', 'buddy-bbpress-support-topic' ); ?></strong>
		<label class="screen-reader-text" for="parent_id"><?php _e( 'Support', 'buddy-bbpress-support-topic' ); ?></label>
		<?php echo bpbbpst_bbpress_get_selectbox( $support_status, $topic_id);?>
	</p>
	<?php
}

add_action( 'bbp_topic_metabox', 'bpbbpst_bbpress_topic_meta_box', 10, 1 );


/**
 * Hooks save_post to save the support status in case of topics admin
 *
 * @param  int $topic_id the id of the topic
 * @param  object $post (not used) 
 * @return int $topic_id (unchanged)
 * @uses   wp_verify_nonce() to check nonce
 * @uses   current_user_can() to check user capacity
 * @uses   delete_post_meta() to remove the support status
 * @uses   update_post_meta() to edit the support status
 * @uses   do_action() to call bpbbpst_topic_meta_box_save to do other stuff if needed
 * @author imath
 */
function bpbbpst_topic_meta_box_save( $topic_id, $post ) {
	
	if( !bbp_is_topic( $topic_id ) )
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
	
	$new_status = intval( $_POST['_support_status'] );
	
	if( $new_status !== false && wp_verify_nonce( $_POST['_wpnonce_bpbbpst_support_status'], 'bpbbpst_support_status') ) {
		
		if( empty( $new_status ) ) {
			delete_post_meta( $topic_id, '_bpbbpst_support_topic' );
		} else {
			update_post_meta( $topic_id, '_bpbbpst_support_topic', $new_status );
		}
		
		do_action( 'bpbbpst_topic_meta_box_save', $new_status );
		
	}
	
	return $topic_id;
}

add_action( 'save_post', 'bpbbpst_topic_meta_box_save', 10, 2 );



/**
 * Hooks restrict_manage_posts if wp admin topics list is displayed
 *
 * @uses   bpbbpst_bbpress_get_selectbox to display the selectbox
 * @author imath
 */
function bpbbpst_bbpress_add_support_filter() {
	if( get_current_screen()->post_type == BPBBPST_TOPIC_CPT_ID ){
		
		$selected = empty( $_GET['_support_status'] ) ? 3 : intval( $_GET['_support_status'] );
		//displays the selectbox to filter by support status
		echo bpbbpst_bbpress_get_selectbox( $selected , 'adminlist' );
	}
	
}

add_action( 'restrict_manage_posts', 'bpbbpst_bbpress_add_support_filter', 11 );


/**
 * filters query_vars in order to add a meta_query to filter topics by support status
 *
 * @param  array $query_vars 
 * @return $query_vars eventually modified to integrate a meta query
 * @uses   intval() to sanitize data
 * @author imath
 */
function bpbbpst_bbpress_filter_support( $query_vars ){
	
	if( is_null( $_GET['_support_status']  ) )
		return $query_vars;
	
	$support_status = intval( $_GET['_support_status'] );
	

	if( !empty( $query_vars['meta_key'] ) ) {
		
		if( $support_status == 3 )
			return $query_vars;

		unset( $query_vars['meta_value'], $query_vars['meta_key'] );

		$query_vars['meta_query'] = array( array(
													'key' => '_bpbbpst_support_topic',
													'value' => $support_status,
													'compare' => '='
												),
											array(
													'key' => '_bbp_forum_id',
													'value' =>  intval( $_GET['bbp_forum_id'] ),
													'compare' => '='
												) 
										);

	} else {
		
		if( $support_status == 3 )
			return $query_vars;
		
		$query_vars['meta_key']   = '_bpbbpst_support_topic';
		$query_vars['meta_value'] = $support_status;
		
	}

	return $query_vars;
}

add_filter( 'bbp_request',  'bpbbpst_bbpress_filter_support', 11, 1 );