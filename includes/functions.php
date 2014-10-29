<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Gets the plugin's version
 *
 * @since  2.0
 *
 * @uses   bbpress() the main bbPress instance
 * @return string the plugin version
 */
function bpbbpst_get_plugin_version() {
	return bbpress()->extend->bpbbpst->globals->version;
}

/**
 * Gets the plugin's url
 *
 * @since  2.0
 *
 * @uses   bbpress() the main bbPress instance
 * @return string the plugin url
 */
function bpbbpst_get_plugin_url( $subfolder = '' ) {
	return trailingslashit( bbpress()->extend->bpbbpst->globals->plugin_url . $subfolder );
}

/**
 * Gets the plugin's path
 *
 * @since  2.0
 *
 * @uses   bbpress() the main bbPress instance
 * @return string the plugin path
 */
function bpbbpst_get_plugin_dir( $subfolder = '' ) {
	return trailingslashit( bbpress()->extend->bpbbpst->globals->plugin_dir . $subfolder );
}

/**
 * Gets the plugin's includes url
 *
 * @since  2.0
 *
 * @uses   bbpress() the main bbPress instance
 * @return string the plugin includes url
 */
function bpbbpst_get_includes_url( $subfolder = '' ) {
	return trailingslashit( bbpress()->extend->bpbbpst->globals->includes_url . $subfolder );
}

/**
 * Gets the plugin's includes path
 *
 * @since  2.0
 *
 * @uses   bbpress() the main bbPress instance
 * @return string the plugin includes path
 */
function bpbbpst_get_includes_dir( $subfolder = '' ) {
	return trailingslashit( bbpress()->extend->bpbbpst->globals->includes_dir . $subfolder );
}

/**
 * Gets the registered support statuses
 *
 * @since  2.0
 *
 * @uses   bbpress() the main bbPress instance
 * @uses   apply_filters() to let plugins or themes modify the value
 * @return array the support statuses
 */
function bpbbpst_get_support_status() {
	$bpbbpst = bbpress()->extend->bpbbpst;

	return apply_filters( 'bpbbpst_get_support_status', bbpress()->extend->bpbbpst->support_status );
}

/**
 * Gets the forum setting for support feature
 *
 * @since  2.0
 *
 * @param  integer $forum_id the forum id
 * @uses   get_post_meta() to get the forum support setting
 * @uses   apply_filters() to let plugins or themes modify the value
 * @return integer the forum support setting
 */
function bpbbpst_get_forum_support_setting( $forum_id = 0 ) {
	if( empty( $forum_id ) )
		return false;

	$forum_support_setting = get_post_meta( $forum_id, '_bpbbpst_forum_settings', true );

	if( empty( $forum_support_setting ) )
		$forum_support_setting = 1;

	return apply_filters( 'bpbbpst_get_forum_support_setting', intval( $forum_support_setting ) );
}

/**
 * Outputs a field to specify the topic is a support one
 *
 * First checks for parent forum support setting
 *
 * @since  2.0
 *
 * @uses   bbp_get_forum_id() to get the parent forum id
 * @uses   bbp_get_topic_id() to get the topic id
 * @uses   bbp_get_topic_forum_id() to have a fallback to get parent forum id thanks to topic id
 * @uses   bpbbpst_get_forum_support_setting() to get the parent forum setting for support feature
 * @uses   bbp_is_topic_edit() to check if the topic is being edited from front end
 * @uses   get_post_meta() to get topic's support option
 * @uses   do_action() to let plugins or themes run some actions from this point
 * @uses   wp_nonce_field() for security reasons
 * @return string the html output
 */
function bpbbpst_maybe_output_support_field() {
	$checked = $output = false;
	$forum_id = bbp_get_forum_id();
	$topic_id = bbp_get_topic_id();

	if( empty( $forum_id ) )
		$forum_id = bbp_get_topic_forum_id( $topic_id );

	$parent_forum_support_feature = bpbbpst_get_forum_support_setting( $forum_id );

	switch( $parent_forum_support_feature ) {

		case 2:
				$output = '<input type="hidden" value="support" name="_bp_bbp_st_is_support" id="bp_bbp_st_is_support_hidden">';
			break;

		case 3:
				$output = false;
			break;

		case 1:
		default:
				if( bbp_is_topic_edit() ){

					$support_status = get_post_meta( $topic_id, '_bpbbpst_support_topic', true );
					
					if( !empty( $support_status ) )
						$checked = true;
				}
		
				$output = '<input type="checkbox" value="support" name="_bp_bbp_st_is_support" id="bp_bbp_st_is_support" '. checked( true, $checked, false ).'> <label for="bp_bbp_st_is_support">'. __('This is a support topic','buddy-bbpress-support-topic') . '</label>' ;
			break;
	}

	if( empty( $output ) )
		return false;

	?>
	<p>
		<?php echo $output;?>
		
		<?php do_action( 'bpbbpst_output_support_extra_field', $parent_forum_support_feature );?>
		
		<?php wp_nonce_field( 'bpbbpst_support_define', '_wpnonce_bpbbpst_support_define' ); ?>
	</p>
	<?php
}

/**
 * Hooks bbp_new_topic_post_extras to safely insert the support request
 *
 * @since  2.0
 *
 * @param  integer $topic_id the id of the topic
 * @uses   wp_verify_nonce() to check nonce
 * @uses   update_post_meta() to set the support status to support request
 * @uses   esc_url_raw() to escape the referer before inserting it in db
 * @uses   do_action() to let plugins or themes run some actions from this point
 */
function bpbbpst_save_support_type( $topic_id = 0 ) {
	// if safe then store
	if ( !empty( $_POST['_bp_bbp_st_is_support'] ) && wp_verify_nonce( $_POST['_wpnonce_bpbbpst_support_define'], 'bpbbpst_support_define' ) ) {
		// no need to sanitize value as i arbitrary set the support topic option to 1
		update_post_meta( $topic_id, '_bpbbpst_support_topic', 1 );

		if( !empty( $_POST['_bp_bbp_st_referer'] ) )
			update_post_meta( $topic_id, '_bpbbpst_support_referer', esc_url_raw( $_POST['_bp_bbp_st_referer'] ) );

		do_action( 'bpbbpst_support_type_saved' );
	}
}

/**
 * Hooks bbp_edit_topic_post_extras to update the support status when topic is edited
 *
 * @since  2.0
 *
 * @param  integer $topic_id the id of the topic
 * @uses   wp_verify_nonce() to check nonce
 * @uses   get_post_meta() to get the stored status if it exists
 * @uses   update_post_meta() to edit the support status to the previous status, defaults to support request
 * @uses   delete_post_meta() to remove the support status
 * @uses   do_action() to let plugins or themes run some actions from this point
 */
function bpbbpst_edit_support_type( $topic_id = 0 ) {

	if( empty( $_POST['_wpnonce_bpbbpst_support_define'] ) || !wp_verify_nonce( $_POST['_wpnonce_bpbbpst_support_define'], 'bpbbpst_support_define' ) )
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

	do_action( 'bpbbpst_support_type_edited');
}

/**
 * Handles topic moves from a forum to another
 *
 * @since  2.0
 * 
 * @param  integer $topic_id the topic id
 * @param  integer $forum_id the new forum id
 * @uses   bbp_get_topic_forum_id() to get old forum id
 * @uses   bpbbpst_get_forum_support_setting() to get old and new forum support setting
 * @uses   delete_post_meta() to eventually remove a support status
 * @uses   get_post_meta() to get a previously saved support status
 * @uses   update_post_meta() to save a default support status if needed.
 */
function bpbbpst_handle_moving_topic( $topic_id = 0, $forum_id = 0 ) {
	if( empty( $topic_id ) || empty( $forum_id ) )
		return;

	$old_forum_id = bbp_get_topic_forum_id( $topic_id );

	//if old is new, then do nothing !
	if( $old_forum_id == $forum_id )
		return;

	$old_forum_support_feature = bpbbpst_get_forum_support_setting( $old_forum_id );
	$new_forum_support_feature = bpbbpst_get_forum_support_setting( $forum_id );

	//if old has same support feature than new, then do nothing
	if( $old_forum_support_feature == $new_forum_support_feature )
		return;
	// at this point it means old had a support feature and new one no
	if( $new_forum_support_feature == 3 ) {
		// delete_post_meta will be handled by bpbbpst_bbpress_edit_support_type
		if( isset( $_POST['_bp_bbp_st_is_support'] ) )
			unset( $_POST['_bp_bbp_st_is_support'] );
		if( isset( $_POST['_support_status'] ) )
			unset( $_POST['_support_status'] );
		
		delete_post_meta( $topic_id, '_bpbbpst_support_topic' );

	} else {
		$meta = get_post_meta( $topic_id, '_bpbbpst_support_topic', true );

		if( $old_forum_support_feature == 3 ) {
			// in this case nonce is not set
			if( $new_forum_support_feature == 2 )
				$meta = 1;
			
			update_post_meta( $topic_id, '_bpbbpst_support_topic', $meta );
		} else {
			if( empty( $_POST['_bp_bbp_st_is_support'] ) && $new_forum_support_feature == 2 )
				$_POST['_bp_bbp_st_is_support'] = 'support';

			if( !empty( $_POST['_bp_bbp_st_is_support'] ) && empty( $meta ) && $new_forum_support_feature == 1 )
				unset( $_POST['_bp_bbp_st_is_support'] );

			if( empty( $_POST['_support_status'] ) && empty( $meta ) && $new_forum_support_feature == 2 )
				$_POST['_support_status'] = 1;
		}

	}
}

/**
 * Hooks wp_enqueue_scripts to load needed js or css
 *
 * It only loads the scripts or stylesheets only on the template
 * where we need it (not on all page of the blog!)
 *
 * @since  2.0
 *
 * @uses   bbp_is_single_topic() to check if topic is on single template
 * @uses   bp_is_group_forum_topic() in case BuddyPress is activated
 * @uses   wp_enqueue_script() to enqueue the script and its dependencies
 * @uses   bpbbpst_get_plugin_url() to get plugin's url
 * @uses   bpbbpst_get_plugin_version() to get plugin's version
 * @uses   wp_localize_script() to ensure translation of messages
 */
function bpbbpst_enqueue_scripts(){
	
	/* 
	With BuddyPress activated, bbp_is_single_topic() is becoming true too late :(
	so we need to check for bp_is_group_forum_topic(), if this function exists ;)
	*/
	$bbpress_load_scripts = false;
	
	if( bbp_is_single_topic() )
		$bbpress_load_scripts = true;
	elseif( function_exists( 'bp_is_group_forum_topic' ) && bp_is_group_forum_topic() )
		$bbpress_load_scripts = true;
	
	if( $bbpress_load_scripts ) {
		wp_enqueue_script( 'bpbbpst-topic-js', bpbbpst_get_plugin_url( 'js' ) . 'bpbbpst-topic.js', array( 'jquery' ), bpbbpst_get_plugin_version() );
		wp_localize_script( 'bpbbpst-topic-js', 'bpbbpstbbp_vars', array(
					'securitycheck' => __( 'Security check failed', 'buddy-bbpress-support-topic' ),
					'loading'       => __( 'loading', 'buddy-bbpress-support-topic' )
				)
			);
	}
}

/**
 * Handles Ajax calls and change the support status
 *
 * @since  2.0
 *
 * @uses   wp_verify_nonce() to check nonce
 * @uses   delete_post_meta() to delete the support status
 * @uses   intval() to sanitize input
 * @uses   update_post_meta() to edit the support status
 * @uses   die() to make sure the script is exited
 */
function bpbbpst_change_support_status() {
	
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

/**
 * Hooks bbp_get_topic_admin_links to eventually add the selectbox of available support status
 *
 * @since  2.0
 *
 * @param  string $input the html containing bbPress admin links
 * @param  array $args the array containing eventual args
 * @uses   bbp_is_single_topic() to return input if not on single topic
 * @uses   bbp_get_topic_id() to get the topic id
 * @uses   bbp_parse_args() to merge user defined arguments into defaults array
 * @uses   bbp_get_topic_forum_id() to get parent forum id
 * @uses   bpbbpst_get_forum_support_setting() to check for parent forum support setting
 * @uses   current_user_can() to check user capacity to edit the topic
 * @uses   get_post_meta() to get the stored status if it exists
 * @uses   bpbbpst_get_selectbox() to get the selectbox of available status
 * @uses   apply_filters() to call the 'bpbbpst_support_admin_links' hook
 * @return string $input or the input with the selectbox
 */
function bpbbpst_support_admin_links( $input = '', $args = array() ) {
	
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

	// Since 2.0, we first need to check parent forum has support for support :)
	$forum_id = bbp_get_topic_forum_id( $r['id'] );

	if( empty( $forum_id ) )
		return $input;

	if( 3 == bpbbpst_get_forum_support_setting( $forum_id ) )
		return $input;
	
	// now let's check the post meta !
	$support_status = get_post_meta( $r['id'], '_bpbbpst_support_topic', true );
	
	if ( current_user_can( 'edit_topic', $r['id'] ) && !empty( $support_status ) ) {
		
		$support_selectbox = bpbbpst_get_selectbox( $support_status, $r['id'] ) . $r['sep'] ;

		$new_span = str_replace( $r['before'], $r['before'] . $support_selectbox, $input );

		return apply_filters( 'bpbbpst_support_admin_links', $new_span, $args );
		
	} else {
		return $input;
	}

}

/**
 * Gets the selected status from the list of available ones
 *
 * @since  2.0
 * 
 * @param  integer $selected the value of the support status
 * @uses   bpbbpst_get_support_status() to get the registered statuses
 * @return array            the selected status with arguments
 */
function bpbbpst_get_selected_support_status( $selected = 0 ) {

	$selected_status = array();

	$all_status = bpbbpst_get_support_status();

	foreach( $all_status as $key => $status ) {

		if( $status['value'] == $selected ) {
			$selected_status = $all_status[$key];
			$selected_status = array_merge( $selected_status, array( 'class' => $key ) );
		}

	}
	return $selected_status;
}

/**
 * Hooks bbp_theme_before_topic_title to add the support mention before the topic title
 *
 * @since  2.0
 * 
 * @param  integer $topic_id the topic id
 * @param  boolean $echo true to display, false to return
 * @uses   bbp_get_topic_id() to get the topic id if not set
 * @uses   bbp_get_topic_forum_id() to get parent forum id
 * @uses   bpbbpst_get_forum_support_setting() to check for parent forum support setting
 * @uses   get_post_meta() to get the support status
 * @uses   bpbbpst_get_selected_support_status() to get arguments for the selected status
 * @uses   sanitize_html_class() to sanitize html class
 * @return string the html output containing the support status
 */
function bpbbpst_add_support_mention( $topic_id = 0, $echo = true ) {
	$class = false;
	
	if( empty( $topic_id ) )
		$topic_id = bbp_get_topic_id();

	// Since 2.0, we first need to check parent forum has support for support :)
	$forum_id = bbp_get_topic_forum_id( $topic_id );

	if( empty( $forum_id ) )
		return;

	if( 3 == bpbbpst_get_forum_support_setting( $forum_id ) )
		return;

	$support_status = get_post_meta( $topic_id, '_bpbbpst_support_topic', true );
	$status = '';
	
	if( empty( $support_status ) && $support_status!=0 )
		return;

	/* new since 2.0 */
	$status = bpbbpst_get_selected_support_status( $support_status );

	if( empty( $status ) || !is_array( $status ) )
		return;

	if( !empty( $echo ) ) :?>
		<span class="bbp-st-topic-support <?php echo sanitize_html_class( $status['class'] );?>"><?php echo $status['prefix-title'];?></span>
	<?php else:
		return '<span class="bbp-st-topic-support '. sanitize_html_class( $status['class'] ).'">'. $status['prefix-title'] .'</span>';
	endif;	
}

/**
 * Removes the filter to the_title to avoid the support mention to be added in the_content
 *
 * @since  2.0
 *
 * @param  array $templates the hierarchy of bbPress templates
 * @param  string $slug the slug template part
 * @param  string $name the optionnal template part
 * @return array $templates unchanged
 */
function bpbbpst_topic_is_single( $templates = array(), $slug = '', $name = '' ){
	
	if( in_array( $name, array( 'single-topic', 'topic' ) ) )
		remove_filter( 'the_title', 'bpbbpst_change_topic_title', 99 );
	
	return $templates;
}

/**
 * Change the title of the topic to add support mention
 *
 * @since  2.0
 *
 * @param  string $title the title of the topic
 * @param  integer $id the id of the topic
 * @uses   bbp_is_topic() to check for topic post type
 * @uses   bbp_is_topic_edit() to check if topic is being edited
 * @uses   bbp_is_single_topic() to check if topic is on single template
 * @uses   bpbbpst_add_support_mention() to return the mention and the title
 * @return string $title the title with the eventual support mention
 */
function bpbbpst_change_topic_title( $title = '', $id = 0 ) {
	
	if( !bbp_is_topic( $id ) )
			return $title;
	
	if( bbp_is_topic_edit() || bbp_is_single_topic() ) {
		return bpbbpst_add_support_mention( false ) . $title;
	} else {
		return $title;
	}
}


/**
 * Hooks bbp_head to launch a filter to the_title to change the title
 *
 * it does it quite late to avoid filtering the get_the_title() WordPress
 * function that may be used by other plugin hooking wp_head such as jetpack.
 *
 * @since  2.0
 *
 * @uses   add_filter() to modify the_title
 */
function bpbbpst_filter_topic_title() {
	add_filter('the_title', 'bpbbpst_change_topic_title', 99, 2 );
}

/**
 * Outputs the selectbox of available support status
 *
 * @since  2.0
 *
 * @param  integer $support_status the current support status
 * @param  integer $topic_id the id of the topic
 * @uses   bpbbpst_get_support_status() to get the registered support statuses
 * @uses   selected() to activate the current option
 * @uses   wp_nonce_field() to create the security token
 * @uses   apply_filters() to call the 'bpbbpst_get_selectbox' hook
 * @return $output
 */
function bpbbpst_get_selectbox( $support_status = 1, $topic_id = 0 ) {
	
	if( empty( $topic_id ) )
		return;

	$all_status = bpbbpst_get_support_status();

	if( empty( $all_status ) || !is_array( $all_status ) )
		return;
	
	$output = '<span class="support-select-box">';
	$output .= '<select class="support-select-status" name="_support_status" data-topicsupport="'.$topic_id.'">';
	
	if( $topic_id == 'adminlist' )
		$output .= '<option value="-1">' . __('All support status') .'</option>';

	foreach( $all_status as $status ) {

		if( $topic_id == 'adminlist' && $status['value'] == 0 )
			continue;

		$output .= '<option value="'. $status['value'] .'" ';
		$output .= selected( $support_status, $status['value'], false );
		$output .= '>'. $status['sb-caption'] .'</option>';
	}
	
	$output .= '</select>';
	
	// nonce field
	if( $topic_id != 'adminlist' )
		$output .= wp_nonce_field( 'bpbbpst_support_status', '_wpnonce_bpbbpst_support_status', true, false );
	
	$output .= '</span>';
	
	return apply_filters( 'bpbbpst_get_selectbox', $output, $support_status, $topic_id );

}

/**
 * Eventually unsets the not a support questions for support only forums
 *
 * @since  2.0
 * 
 * @param  array  $all_status the registered support statuses
 * @uses   is_admin() to check for WordPress Backend
 * @uses   get_current_screen() to check for the topic admin area
 * @uses   bbp_get_topic_post_type() to get the topic post type
 * @uses   bbp_get_topic_forum_id() to get parent forum id
 * @uses   bpbbpst_get_forum_support_setting() to get parent forum support setting
 * @return array              the statuses without the not a support question if needed
 */
function bpbbpst_neutralize_not_support( $all_status = array() ) {

	$topic_id = bbp_get_topic_id();

	if( is_admin() && empty( $topic_id ) && isset( get_current_screen()->post_type ) && get_current_screen()->post_type == bbp_get_topic_post_type() && get_current_screen()->base == 'post' )
		$topic_id = get_the_ID();

	if( empty( $topic_id ) )
		return $all_status;

	$forum_id = bbp_get_topic_forum_id( $topic_id );

	if( empty( $forum_id ) )
		return $all_status;

	if( 2 == bpbbpst_get_forum_support_setting( $forum_id ) && !empty( $all_status['topic-not-support'] ) )
		unset( $all_status['topic-not-support'] );

	return $all_status;
}

/**
 * Check for a selected value in an array
 *
 * @since  2.0
 * 
 * @param  array   $current the complete array
 * @param  mixed   $tocheck the value to check in the array
 * @param  boolean $echo    wether to display or return
 * @uses   checked() to build the checked attribute
 * @return string html checked attribute
 */
function bpbbpst_array_checked( $current = array(), $tocheck = false , $echo = true ) {
	$checked = false;

	if( empty( $current ) || empty( $tocheck ) )
		return false;

	if( !is_array( $current ) )
		$current = explode( ',', $current );

	if( is_array( $current ) && in_array( $tocheck, $current ) )
		$checked = checked(  $tocheck,  $tocheck, false );
	else
		$checked = checked( $current, $tocheck, false );

	if( empty( $echo ) )
		return $checked;

	else
		echo $checked;
}

/**
 * Maps buddypress group mods to add their role in array
 *
 * @since 2.0
 * 
 * @param  array  $users the group users (mods or admins)
 * @return array        the users with their group "role"
 */
function bpbbpst_role_group_forum_map( $users = array() ) {

	$role = $users['role'];

	foreach( $users['users'] as $key => $user ) {
		if( !empty( $user->user_id ) ) {
			$user->role = $role;
			$users['users'][$key] = $user;
		}
	}

	return $users['users'];
}

/**
 * List all bbPress moderators that are subscribed to new support topics
 *
 * @since  2.0
 * 
 * @param  integer $forum_id the forum id
 * @param  string  $context  are we using form admin or inside our notify function ?
 * @uses   get_post_meta() to get array of subscribed moderators
 * @return array of user ids
 */
function bpbbpst_list_recipients( $forum_id = 0, $context = 'admin' ) {

	if( empty( $forum_id ) )
		return false;

	$recipients = get_post_meta( $forum_id, '_bpbbpst_support_recipients', true );

	if( empty( $recipients ) )
		$recipients = array();

	return apply_filters( 'bpbbpst_list_recipients', $recipients, $context, $forum_id );
}

/**
 * Displays the available 3 options for forum support settings
 *
 * @since  2.0
 * 
 * @param  integer $support_feature the previously saved option if any
 * @uses   checked() to eventually add a checked attribute to field
 * @return string                  html list of options
 */
function bpbbpst_display_forum_setting_options( $support_feature = 1 ) {
	?>
	<ul class="forum-support-settings">
		<li><input type="radio" name="_bpbbpst_forum_settings" id="bpbbpst_forum_settings_yes" value="1" <?php checked( 1, $support_feature );?>/> <?php _e( 'Leave users mark topics of this forum as support ones', 'buddy-bbpress-support-topic' ); ?></li>
		<li><input type="radio" name="_bpbbpst_forum_settings" id="bpbbpst_forum_settings_only" value="2" <?php checked( 2, $support_feature );?>/> <?php _e( 'This forum is dedicated to support, all topics will be marked as support ones.', 'buddy-bbpress-support-topic' ); ?></li>
		<li><input type="radio" name="_bpbbpst_forum_settings" id="bpbbpst_forum_settings_no" value="3" <?php checked( 3, $support_feature );?>/> <?php _e( 'This forum do not accept support topics', 'buddy-bbpress-support-topic' ); ?></li>
	</ul>
	<?php
}

/**
 * Displays a list of checkbox for moderators notifications
 * 
 * Admin can choose the moderators that will receive a notification
 * in case a new support topic has been posted.
 * 
 * @since  2.0
 * 
 * @param  integer $forum_id the forum id
 * @uses   get_users() to list all moderators
 * @uses   bpbbpst_list_recipients() to get the support recipients for the specified forum
 * @uses   bpbbpst_array_checked() to activate the checboxes regarding the recipients previously saved
 * @uses   bbp_get_dynamic_role_name() to get the role name of listed users
 * @uses   bbp_get_user_role() to get the forum role for listed users
 * @return string html the list of checkboxes
 */
function bpbbpst_checklist_moderators( $forum_id = false ) {

	$user_query = get_users( array( 'who' => 'bpbbpst_moderators' ) );

	if( !is_array( $user_query ) || count( $user_query ) < 1 )
		return false;

	$recipients = bpbbpst_list_recipients( $forum_id );
	?>
	<ul class="bbp-moderators-list">
	<?php foreach( $user_query as $user ) :?>
		<li>
			<input type="checkbox" value="<?php echo $user->data->ID;?>" name="_bpbbpst_support_recipients[]" <?php bpbbpst_array_checked( $recipients, $user->data->ID );?>> 
			<?php echo $user->data->display_name ;?> (<?php echo bbp_get_dynamic_role_name( bbp_get_user_role( $user->data->ID ) );?>)
		</li>
	<?php endforeach;?>
	</ul>
	<?php
}

/**
 * Returns an array of statistics element about support topics
 *
 * @since  2.0
 * 
 * @param  array $args  user can customize the query
 * @uses   bbp_get_topic_post_type() to get the topic post type identifier
 * @uses   bbp_parse_args() to merge args with defaults
 * @uses   WP_Query to make the request and get the support topics
 * @uses   bpbbpst_get_support_status() to get the registered support statuses
 * @uses   sanitize_html_class() to sanitize the class
 * @uses   get_post_meta() to get the support status of the topic
 * @uses   wp_reset_postdata() to restore the $post global to the current post in the main query
 * @uses   apply_filters() to let plugins or themes modify the value
 * @return array $support_stats the different statistics element
 */
function bpbbpst_support_statistics( $args = '' ) {
	
	$defaults = array( 
			'post_type'      => bbp_get_topic_post_type(),
			'posts_per_page' => -1,
			'meta_query'     => array( array( 
											'key' => '_bpbbpst_support_topic',
											'value' => 1,
											'type' => 'numeric',
											'compare' => '>=' 
								) )
				);
	$r = bbp_parse_args( $args, $defaults, 'support_statistics' );

	$support_query    = new WP_Query( $r );
	$total_support    = $support_query->found_posts;

	$all_status = bpbbpst_get_support_status();
	unset( $all_status['topic-not-support'] );

	$support_stat = array();

	foreach( $all_status as $key => $status ) {
		$support_stat[$status['value']] = array( 
			'stat'        => 0, 
			'label'       => $status['sb-caption'], 
			'admin_class' => $status['admin_class'],
			'front_class' => sanitize_html_class( $key )
		);
	}

	if ( $support_query->have_posts() ) :

		while (  $support_query->have_posts() ) :  $support_query->the_post();

			$db_status = get_post_meta( $support_query->post->ID, '_bpbbpst_support_topic', true );

			$support_stat[$db_status]['stat'] += 1;

		endwhile;

		wp_reset_postdata();

	endif;

	$goon = false;

	foreach( $support_stat as $notempty ) {
		if( $notempty['stat'] > 0 )
			$goon = true;
	}

	if( !empty( $goon ) ) {
		
		$percent_support  = number_format( ( $support_stat[2]['stat'] / $total_support ) * 100, 2 ) . '%';

		$support_stats['allstatus']     = $support_stat;
		$support_stats['total_support'] = $total_support;
		$support_stats['percent']       = $percent_support;

		return apply_filters( 'bpbbpst_support_statistics', $support_stats, $args );

	} else {

		return false;

	}

}

/**
 * Hooks bbp_new_topic to eventually send a notification to moderators
 * 
 * @since 2.0
 * 
 * @param  integer $topic_id       the topic id just created
 * @param  integer $forum_id       the forum id the topic is attached to
 * @param  array $anonymous_data
 * @param  integer $topic_author   the topic author id
 * @uses   bpbbpst_get_forum_support_setting() to get support settings for the forum the topic is attached to
 * @uses   bpbbpst_notify_moderators() to build and send the notifications to moderators.
 */
function bpbbpst_new_support_topic_notify( $topic_id = 0, $forum_id = 0, $anonymous_data = false, $topic_author = 0 ) {

	if( empty( $topic_id ) )
		return;

	if( empty( $forum_id ) )
		return;

	$forum_support_feature = bpbbpst_get_forum_support_setting( $forum_id );

	if( $forum_support_feature != 3 && !empty( $_POST['_bp_bbp_st_is_support'] ) )
		bpbbpst_notify_moderators( $topic_id, $forum_id, $anonymous_data, $topic_author );

}

/**
 * Sends a notification to bbPress moderators and eventually BP Groups one
 *
 * Inpired by : bbPress bbp_notify_subscribers() function
 * 
 * @since  2.0
 * 
 * @param  integer $topic_id       the topic id
 * @param  integer $forum_id       the forum id
 * @param  boolean $anonymous_data 
 * @param  integer $topic_author
 * @uses   bbp_get_topic_id() to validate the topic id
 * @uses   bbp_get_forum_id() to validate the forum id
 * @uses   bpbbpst_list_recipients() to get the moderators ids (bbPress & current BuddyPress group ones)
 * @uses   bbp_is_topic_published() to make sure the topic is published
 * @uses   bbp_get_topic_author_display_name() to get authors display name
 * @uses   remove_all_filters() to remove the filters applied to topic content and title
 * @uses   bbp_get_topic_title() to get the topic title
 * @uses   bbp_get_topic_content() to get the topic content
 * @uses   bbp_get_topic_permalink() to get the link to the topic
 * @uses   wp_specialchars_decode() to get the decoded string without html entities
 * @uses   get_option() to get blogname
 * @uses   bbp_get_forum_title() to get forum title
 * @uses   apply_filters() to let plugins or themes modify the value
 * @uses   get_userdata() to get the user's data (email...)
 * @uses   wp_mail() to send the mail
 * @uses   do_action() to let plugins or themes run some actions from this point
 * @return boolean true
 */
function bpbbpst_notify_moderators( $topic_id = 0, $forum_id = 0, $anonymous_data = false, $topic_author = 0 ) {

	/** Validation ************************************************************/
	$topic_id = bbp_get_topic_id( $topic_id );
	$forum_id = bbp_get_forum_id( $forum_id );

	$recipients = bpbbpst_list_recipients( $forum_id, 'notify' );

	if( empty( $recipients ) || !is_array( $recipients ) || count( $recipients ) < 1 )
		return;

	/** Topic *****************************************************************/

	// Bail if topic is not published
	if ( !bbp_is_topic_published( $topic_id ) )
		return false;

	// Poster name
	$topic_author_name = bbp_get_topic_author_display_name( $topic_id );

	// Remove filters from reply content and topic title to prevent content
	// from being encoded with HTML entities, wrapped in paragraph tags, etc...
	remove_all_filters( 'bbp_get_topic_content' );
	remove_all_filters( 'bbp_get_topic_title'   );

	// Strip tags from text
	$topic_title   = strip_tags( bbp_get_topic_title( $topic_id ) );
	$topic_content = strip_tags( bbp_get_topic_content( $topic_id ) );
	$topic_url     = bbp_get_topic_permalink( $topic_id );
	$blog_name     = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	$forum_name    = wp_specialchars_decode( bbp_get_forum_title( $forum_id ), ENT_QUOTES );

	// Loop through users
	foreach ( (array) $recipients as $user_id ) {

		/* Don't send notifications to the moderator if he is the one who made the post */
		if ( !empty( $topic_author ) && (int) $user_id == (int) $topic_author )
			continue;

		// For plugins to filter messages per reply/topic/user
		$message = sprintf( __( '%1$s wrote a new support topic: %2$s

%3$s

Topic Link: %4$s

-----------

You are receiving this email because a forum admin made you subscribe to new support topic of %5$s forum.

Please ask the admin to unsubscribe from these emails.', 'buddy-bbpress-support-topic' ),

			$topic_author_name,
			$topic_title,
			$topic_content,
			$topic_url,
			$forum_name
		);

		$message = apply_filters( 'bpbbpst_notify_moderators_mail_message', $message, $topic_id, $forum_id, $user_id );
		if ( empty( $message ) )
			continue;

		// For plugins to filter titles per reply/topic/user
		$subject = apply_filters( 'bpbbpst_notify_moderators_mail_title', '[' . $blog_name . '] New support topic on : ' . $forum_name, $topic_id, $forum_id, $user_id );
		if ( empty( $subject ) )
			continue;

		// Custom headers
		$headers = apply_filters( 'bpbbpst_notify_moderators_mail_headers', array() );

		// Get user data of this user
		$user = get_userdata( $user_id );

		// Send notification email
		wp_mail( $user->user_email, $subject, $message, $headers );
	}

	do_action( 'bpbbpst_notify_moderators', $topic_id, $forum_id, $recipients );

	return true;
}

/**
 * Builds a selectbox with forum set as support only ones
 *
 * @since  2.0
 * 
 * @param  integer $selected   the forum id previously selected
 * @param  string  $field_id   id of the selectbox
 * @param  string  $field_name name of the selectbox
 * @uses   bbp_get_forum_post_type() to get forum post type
 * @uses   WP_Query to build a loop
 * @uses   esc_attr() to sanitize value of field
 * @uses   bbp_get_forum_id() to validate forum id
 * @uses   selected() to add a selected attribute to option if needed
 * @uses   bbp_forum_title() to get the forum title
 * @uses   wp_reset_postdata() to reset the post datas 
 * @return string html output
 */
function bpbbpst_get_support_only_forums( $selected = 0, $field_id = '_support_only_forum', $field_name = '_support_only_forums' ) {

	$query_args = array( 
			'post_type'      => bbp_get_forum_post_type(),
			'posts_per_page' => -1,
			'meta_query'     => array( array( 
											'key' => '_bpbbpst_forum_settings',
											'value' => 2,
											'type' => 'numeric',
											'compare' => '=' 
								) )
				);

	$support_query = new WP_Query( $query_args );

	if ( $support_query->have_posts() ) :?>

		<select class="widefat" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>">

			<?php while (  $support_query->have_posts() ) :  $support_query->the_post();?>

				<option value="<?php echo esc_attr( bbp_get_forum_id( $support_query->post->ID ) ) ?>" <?php selected( $support_query->post->ID, $selected ) ?>><?php bbp_forum_title( $support_query->post->ID ) ?></option>

			<?php endwhile;?>

		</select>

	<?php wp_reset_postdata();

	else :
		_e( 'No support only forums were found', 'buddy-bbpress-support-topic' );

	endif;
}

/**
 * Outputs an hidden field with the referer url
 *
 * @since  2.0
 * 
 * @param  integer $support_type the forum support setting
 * @uses   esc_url_raw() to sanitize url
 * @uses   wp_get_referer() to get the referer url
 * @return string html output
 */
function bpbbpst_referer_extra_field( $support_type = 0 ) {
	if( !empty( $support_type ) && $support_type != 3 && !empty( $_REQUEST['bpbbpst-referer'] ) ) {
		$referer = esc_url_raw( wp_get_referer() );
		?>
		<input type="hidden" name="_bp_bbp_st_referer" value="<?php echo $referer;?>"/>
		<?php
	}
}

/**
 * Displays the referer above the support topic content
 *
 * @since  2.0
 *
 * @uses   get_post_meta() to get the saved referer
 * @uses   bbp_get_topic_id() to get the topic id
 * @uses   current_user_can() to check for current user's capability
 * @uses   bbp_get_reply_id() to get reply id
 * @uses   esc_url() to sanitize the referer
 * @return string html output
 */
function bpbbpst_display_referer_to_moderators() {
	$meta = get_post_meta( bbp_get_topic_id(), '_bpbbpst_support_referer', true );

	if( !empty( $meta ) && current_user_can( 'moderate' ) && bbp_get_reply_id() == bbp_get_topic_id() )
		echo '<pre>' . __( 'Referer', 'buddy-bbpress-support-topic' ) . ' :<br/>'. esc_url( $meta ).'</pre>';
}