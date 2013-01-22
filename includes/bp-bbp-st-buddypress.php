<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

//adds the checkbox to let the topic author say he needs support
add_action( 'groups_forum_new_topic_after', 'bp_bbp_st_add_or_update_support_type' );
add_action( 'bp_after_group_forum_post_new', 'bp_bbp_st_add_or_update_support_type' );
add_action( 'bp_group_after_edit_forum_topic', 'bp_bbp_st_add_or_update_support_type' );

function bp_bbp_st_add_or_update_support_type() {
	
	if( bp_is_action_variable( 'edit' ) )
		$topic_id = bp_get_the_topic_id();
		
	$checked = false;
	
	if( !empty( $topic_id ) ) {
		$support_status = bb_get_topicmeta( $topic_id, 'support_topic');
		
		if( !empty( $support_status ) )
			$checked = true;
			
	}
	
	bp_bbp_st_add_support_type( $checked );
}


add_action( 'groups_update_group_forum_topic', 'bp_bbp_st_bbpold_edit_support_type_for_topic', 11, 1);

function bp_bbp_st_bbpold_edit_support_type_for_topic( $topic_datas ) {
	
	$support_status = bb_get_topicmeta( $topic_datas->topic_id, 'support_topic');
	
	if( !empty( $_POST['_bp_bbp_st_is_support'] ) && wp_verify_nonce( $_POST['_wpnonce_bpbbpst_support_define'],'bpbbpst_support_define') ) {

		if( empty( $support_status ) )
			bp_bbp_st_bbpold_save_support_type_for_topic( false, $topic_datas );
			
	} else {
		if( !empty( $support_status ) )
			bb_delete_topicmeta( $topic_datas->topic_id, 'support_topic' );
	}
		
}


add_action( 'groups_new_forum_topic', 'bp_bbp_st_bbpold_save_support_type_for_topic', 11, 2 );

function bp_bbp_st_bbpold_save_support_type_for_topic( $group_id, $topic_datas ) {

	if ( !empty( $_POST['_bp_bbp_st_is_support'] ) && wp_verify_nonce( $_POST['_wpnonce_bpbbpst_support_define'],'bpbbpst_support_define') ) {
		// no need to sanitize value as i arbitrary set the support topic option to 1
		bb_update_topicmeta( $topic_datas->topic_id, 'support_topic', 1 );
	}
}

add_filter('bp_get_the_topic_title', 'bp_bbp_st_print_support_type_on_topic_title', 11, 1 );

function bp_bbp_st_print_support_type_on_topic_title( $topic_title ) {
	
	if( !bp_is_group_forum_topic_edit() && !bp_is_group_forum_topic() ) {
		
		$topic_status = "";
		
		$topic_id = bp_get_the_topic_id();

		$support_status = bb_get_topicmeta( $topic_id, 'support_topic');
		
		if( empty( $support_status ) )
			return $topic_title;
		
		if ( $support_status == 2 )
			$topic_status = __('[Resolved] ', 'buddy-bbpress-support-topic') ;
		
		if ( $support_status == 1 )
			$topic_status = __('[Support request] ', 'buddy-bbpress-support-topic') ;
			
		if( !empty( $topic_status ) )
			return apply_filters( 'bp_bbp_st_print_support_type', $topic_status, $support_status ) . $topic_title ;
		else
			return $topic_title;
	}
		
	else
		return $topic_title;
}

add_action('bp_group_forum_topic_meta', 'bp_bbp_st_selectbox_support' );

function bp_bbp_st_selectbox_support() {
	$topic_id = bp_get_the_topic_id();
	
	$support_status = bb_get_topicmeta( $topic_id, 'support_topic');
	
	$disabled = 'disabled';
	
	if ( bp_group_is_admin() || bp_group_is_mod() || bp_get_the_topic_is_mine() )
		$disabled = false; 
	if( !empty( $support_status ) ){
	?>
	<div class="last admin-links support-left" id="support-select-box">

		<select id="support-select-status" name="_support_status" <?php echo $disabled;?> rel="<?php echo $topic_id;?>">
			<option value="1" <?php if($support_status==1) echo 'selected';?>><?php _e('Not resolved', 'buddy-bbpress-support-topic');?></option>
			<option value="2" <?php if($support_status==2) echo 'selected';?>><?php _e('Resolved', 'buddy-bbpress-support-topic');?></option>
			<option value="0" <?php if( empty( $support_status ) ) echo 'selected';?>><?php _e('Not a support topic', 'buddy-bbpress-support-topic');?></option>
		</select>
		<?php wp_nonce_field( 'bpbbpst_support_status', '_wpnonce_bpbbpst_support_status' );?>
	</div>
	<?php
	}
}

/**
* is bbPress the new one ?
*/
function bp_bbp_st_check_for_config() {
	
	if( function_exists('bp_forums_is_installed_correctly') && bp_forums_is_installed_correctly() )
		bp_bbp_st_enqueue_support_cssjs();

}
add_action('bp_actions', 'bp_bbp_st_check_for_config');


function bp_bbp_st_enqueue_support_cssjs() {
	if( bp_is_group_forum_topic_edit() || bp_is_group_forum_topic() ) {
		wp_enqueue_style( 'bp-bbp-st-css', BP_BBP_ST_PLUGIN_URL_CSS . '/bp-bbp-st.css');
		wp_enqueue_script( 'bp-bbp-st-js', BP_BBP_ST_PLUGIN_URL_JS . '/bp-bbp-st.js', array('jquery') );
		wp_localize_script('bp-bbp-st-js', 'bpbbpst_vars', array(
					'securitycheck'           => __( 'Security check failed', 'buddy-bbpress-support-topic' )
				)
			);
	}
		
}

add_action('wp_ajax_change_support_status', 'bp_bbp_st_change_support_status');

function bp_bbp_st_change_support_status(){
	
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	if( !wp_verify_nonce( $_POST['_wpnonce_bpbbpst_support_status'],'bpbbpst_support_status') ) {
		echo -1;
		die();
	}
	
	if( !empty( $_POST['topic_id'] ) ){
		
		do_action( 'bbpress_init' );

		$support_status = intval( $_POST['support_status'] );
		
		if( empty( $support_status ) ) {
			bb_delete_topicmeta( $_POST['topic_id'], 'support_topic' );
		} else {
			bb_update_topicmeta( $_POST['topic_id'], 'support_topic', $support_status );
		}
		echo 1;
	} else { 
		echo 0;
	}	
	die();
}