<?php

/**
 * Buddy-bbPress Support Topic BuddyPress functions
 *
 * @package Buddy-bbPress Support Topic
 * @subpackage buddypress-functions
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Hooks various BuddyPress hooks to display the checkbox to define support request
 *
 * @uses   bp_is_group_forum_topic_edit() to check if we're editing a topic
 * @uses   bp_get_the_topic_id() to get the id of topic if being edited
 * @uses   bb_get_topicmeta() to get the support status
 * @uses   bpbbpst_define_support() to display the checkbox and manage its state
 * @author imath
 */
function bpbbpst_buddypress_define_support() {
	$topic_id = false;
	
	if( bp_is_group_forum_topic_edit() )
		$topic_id = bp_get_the_topic_id();
		
	$checked = false;
	
	if( !empty( $topic_id ) ) {
		$support_status = bb_get_topicmeta( $topic_id, '_bpbbpst_support_topic' );
		
		if( !empty( $support_status ) )
			$checked = true;
			
	}
	
	bpbbpst_define_support( $checked );
}

add_action( 'groups_forum_new_topic_after', 'bpbbpst_buddypress_define_support' );
add_action( 'bp_after_group_forum_post_new', 'bpbbpst_buddypress_define_support' );
add_action( 'bp_group_after_edit_forum_topic', 'bpbbpst_buddypress_define_support' );


/**
 * Hooks groups_update_group_forum_topic to handle topic edition
 *
 * @param  object $topic_datas
 * @uses   bb_get_topicmeta() to get the support status
 * @uses   wp_verify_nonce() to check nonce
 * @uses   bpbbpst_buddypress_save_support_type() to save support status
 * @uses   bb_delete_topicmeta() to remove the support status
 * @author imath
 */
function bpbbpst_buddypress_edit_support_type( $topic_datas ) {
	
	$support_status = bb_get_topicmeta( $topic_datas->topic_id, '_bpbbpst_support_topic' );
	
	if( !empty( $_POST['_bp_bbp_st_is_support'] ) && wp_verify_nonce( $_POST['_wpnonce_bpbbpst_support_define'], 'bpbbpst_support_define' ) ) {

		if( empty( $support_status ) )
			bpbbpst_buddypress_save_support_type( false, $topic_datas );
			
	} else {
		if( !empty( $support_status ) )
			bb_delete_topicmeta( $topic_datas->topic_id, '_bpbbpst_support_topic' );
	}
		
}

add_action( 'groups_update_group_forum_topic', 'bpbbpst_buddypress_edit_support_type', 11, 1);


/**
 * Hooks groups_new_forum_topic to safely insert the support request 
 *
 * @param  int $group_id 
 * @param  object $topic_datas 
 * @uses   wp_verify_nonce() to check nonce
 * @uses   bb_update_topicmeta() to add or update the support status
 * @author imath
 */
function bpbbpst_buddypress_save_support_type( $group_id, $topic_datas ) {

	if ( !empty( $_POST['_bp_bbp_st_is_support'] ) && wp_verify_nonce( $_POST['_wpnonce_bpbbpst_support_define'], 'bpbbpst_support_define' ) ) {
		// no need to sanitize value as i arbitrary set the support topic option to 1
		bb_update_topicmeta( $topic_datas->topic_id, '_bpbbpst_support_topic', 1 );
	}
}

add_action( 'groups_new_forum_topic', 'bpbbpst_buddypress_save_support_type', 11, 2 );


/**
 * Hooks bp_get_the_topic_title to eventually add the support mention
 *
 * @param  string $topic_title the title of the topic
 * @return string $topic_title the title of the topic with the eventual support status mention
 * @uses   bp_is_group_forum_topic_edit() to check if we are editing or not the topic
 * @uses   bp_get_the_topic_id() to get the id of topic if being edited
 * @uses   bb_get_topicmeta() to get the support status
 * @uses   apply_filters() to call bp_bbp_st_print_support_type to let user add specific css
 * @author imath
 */
function bpbbpst_buddypress_add_support_mention( $topic_title ) {
	
	if( !bp_is_group_forum_topic_edit() ) {
		
		$topic_status = "";
		
		$topic_id = bp_get_the_topic_id();

		$support_status = bb_get_topicmeta( $topic_id, '_bpbbpst_support_topic' );
		
		if( empty( $support_status ) )
			return $topic_title;
		
		if ( $support_status == 2 )
			$topic_status = __( '[Resolved] ', 'buddy-bbpress-support-topic' ) ;
		
		if ( $support_status == 1 )
			$topic_status = __( '[Support request] ', 'buddy-bbpress-support-topic' ) ;
			
		if( !empty( $topic_status ) )
			return apply_filters( 'bp_bbp_st_print_support_type', $topic_status, $support_status ) . $topic_title ;
		else
			return $topic_title;
	}
		
	else
		return $topic_title;
}



/**
 * Wait for wp_head before filtering bp_get_the_topic_title
 *
 * @uses   add_filter() to filter bp_get_the_topic_title after wp_title()...
 * @author imath
 */
function bpbbpst_buddypress_delay_support_mention() {
	add_filter( 'bp_get_the_topic_title', 'bpbbpst_buddypress_add_support_mention', 11, 1 );
}

add_action( 'wp_head', 'bpbbpst_buddypress_delay_support_mention', 99 );


/**
 * Hooks bp_group_forum_topic_meta to display selectbox if user can moderate the support status
 *
 * @uses   bp_get_the_topic_id() to get the id of topic if being edited
 * @uses   bb_get_topicmeta() to get the support status
 * @uses   bp_group_is_admin() to allow group admins to moderate support status
 * @uses   bp_group_is_mod() to allow group moderators to moderate support status
 * @uses   bp_get_the_topic_is_mine() to allow topic author to moderate support status
 * @uses   selected() to activate the current option
 * @uses   wp_nonce_field() to create the security token
 * @author imath
 */
function bpbbpst_buddypress_display_selectbox() {
	$topic_id = bp_get_the_topic_id();
	
	$support_status = bb_get_topicmeta( $topic_id, '_bpbbpst_support_topic' );
	
	$can_edit = false;
	
	if ( bp_group_is_admin() || bp_group_is_mod() || bp_get_the_topic_is_mine() )
		$can_edit = true; 
		
	if( !empty( $support_status ) && $can_edit ){
	?>
	<div class="last admin-links support-left" id="support-select-box">

		<select id="support-select-status" name="_support_status" data-topicsupport="<?php echo $topic_id;?>">
			<option value="1" <?php selected( $support_status, 1 );?>><?php _e( 'Not resolved', 'buddy-bbpress-support-topic' );?></option>
			<option value="2" <?php selected( $support_status, 2 );?>><?php _e( 'Resolved', 'buddy-bbpress-support-topic' );?></option>
			<option value="0" <?php selected( $support_status, false );?>><?php _e( 'Not a support topic', 'buddy-bbpress-support-topic' );?></option>
		</select>
		<?php wp_nonce_field( 'bpbbpst_support_status', '_wpnonce_bpbbpst_support_status' );?>
	</div>
	<?php
	}
}

add_action( 'bp_group_forum_topic_meta', 'bpbbpst_buddypress_display_selectbox' );


/**
 * Hooks bp_actions to check if BuddyPress is still using bbPress 1.2 to power group forums.
 *
 * @uses   bp_forums_is_installed_correctly() do we have a bb-config.php file ?
 * @uses   bpbbpst_buddypress_enqueue_scripts() to enqueue the needed scripts
 * @author imath
 */
function bpbbpst_buddypress_check_config() {
	
	if( function_exists( 'bp_forums_is_installed_correctly' ) && bp_forums_is_installed_correctly() )
		bpbbpst_buddypress_enqueue_scripts();

}
add_action( 'bp_actions', 'bpbbpst_buddypress_check_config' );


/**
 * Enqueues javascript and styles for the selectbox
 *
 * @uses   bp_is_group_forum_topic_edit() to check if we are editing or not the topic
 * @uses   bp_is_group_forum_topic() to check if we are on a single topic
 * @uses   wp_enqueue_style() to enqueue the plugin style
 * @uses   wp_enqueue_script() to enqueue the script and its dependencies
 * @uses   wp_localize_script() to ensure translation of messages
 * @author imath
 */
function bpbbpst_buddypress_enqueue_scripts() {
	if( bp_is_group_forum_topic_edit() || bp_is_group_forum_topic() ) {
		
		/* we don't want too much javascripts..
		   if bbPress is activated for sitewide forums, we need stop him from
		   enqueueing its javascript.
		*/
		if( function_exists( bpbbpst_bbpress_enqueue_scripts ) )
			remove_action( 'wp_enqueue_scripts', 'bpbbpst_bbpress_enqueue_scripts' );
		
		wp_enqueue_style( 'bpbbpst-buddy-css', BPBBPST_PLUGIN_URL_CSS . '/bpbbpst-buddy.css');
		wp_enqueue_script( 'bpbbpst-buddy-js', BPBBPST_PLUGIN_URL_JS . '/bpbbpst-buddy.js', array('jquery') );
		wp_localize_script( 'bpbbpst-buddy-js', 'bpbbpstbuddy_vars', array(
					'securitycheck' => __( 'Security check failed', 'buddy-bbpress-support-topic' ),
					'loading'       => __( 'loading', 'buddy-bbpress-support-topic' )
				)
			);
	}
		
}


/**
 * Handles Ajax calls and change the support status
 *
 * @uses   wp_verify_nonce() to check nonce
 * @uses   do_action( 'bbpress_init' ) to launch bbPress 1.2
 * @uses   intval() to sanitize input
 * @uses   bb_delete_topicmeta() to remove the support status
 * @uses   bb_update_topicmeta() to add or update the support status
 * @uses   die() to make sure the script is exited
 * @author imath
 */
function bpbbpst_buddypress_change_support_status(){
	
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	if( !wp_verify_nonce( $_POST['_wpnonce_bpbbpst_support_status'], 'bpbbpst_support_status' ) ) {
		echo -1;
		die();
	}
	
	if( !empty( $_POST['topic_id'] ) ){
		
		do_action( 'bbpress_init' );

		$support_status = intval( $_POST['support_status'] );
		
		if( empty( $support_status ) ) {
			bb_delete_topicmeta( $_POST['topic_id'], '_bpbbpst_support_topic' );
		} else {
			bb_update_topicmeta( $_POST['topic_id'], '_bpbbpst_support_topic', $support_status );
		}
		echo 1;
	} else { 
		echo 0;
	}	
	die();
}

add_action( 'wp_ajax_buddy_change_support_status', 'bpbbpst_buddypress_change_support_status' );