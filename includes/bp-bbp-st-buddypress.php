<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

//adds the checkbox to let the topic author say he needs support
add_action( 'groups_forum_new_topic_after', 'bp_bbp_st_add_support_type' );
add_action( 'bp_after_group_forum_post_new', 'bp_bbp_st_add_support_type' );


add_action( 'groups_new_forum_topic', 'bp_bbp_st_bbpold_save_support_type_for_topic', 11, 2 );

function bp_bbp_st_bbpold_save_support_type_for_topic( $group_id, $topic_datas ) {

	if ( !empty( $_POST['_bp_bbp_st_is_support'] ) ) {
		bb_update_topicmeta( $topic_datas->topic_id, 'support_topic', 1 );
	}
}

add_filter('bp_get_the_topic_title', 'bp_bbp_st_print_support_type_on_topic_title', 11, 1 );

function bp_bbp_st_print_support_type_on_topic_title( $topic_title ) {
	
	if( !bp_is_group_forum_topic_edit() && !bp_is_group_forum_topic() ) {
		
		$topic_id = bp_get_the_topic_id();

		$support_status = bb_get_topicmeta( $topic_id, 'support_topic');
		
		if( empty( $support_status ) )
			return $topic_title;
		
		if ( $support_status == 2 )
			return __('[Resolved] ', 'buddy-bbpress-support-topic') . $topic_title;
		
		if ( $support_status == 1 )
			return __('[Support request] ', 'buddy-bbpress-support-topic') . $topic_title;
		
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
	</div>
	<?php
	}
}

/**
* is bbPress the new one ?
*/
add_action( !class_exists( 'bbPress' ) ? 'bp_actions' : 'bp_bbp_st_dummy', 'bp_bbp_st_enqueue_support_cssjs');

function bp_bbp_st_dummy() {
	do_action('bp_bbp_st_dummy');
}

function bp_bbp_st_enqueue_support_cssjs() {
	if( bp_is_group_forum_topic_edit() || bp_is_group_forum_topic() ) {
		wp_enqueue_style( 'bp-bbp-st-css', BP_BBP_ST_PLUGIN_URL_CSS . '/bp-bbp-st.css');
		wp_enqueue_script( 'bp-bbp-st-js', BP_BBP_ST_PLUGIN_URL_JS . '/bp-bbp-st.js', array('jquery') );
	}
		
}

add_action('wp_ajax_change_support_status', 'bp_bbp_st_change_support_status');

function bp_bbp_st_change_support_status(){
	
	if( !empty( $_POST['topic_id'] ) ){
		
		do_action( 'bbpress_init' );
		
		if( empty($_POST['support_status']) ) {
			bb_delete_topicmeta( $_POST['topic_id'], 'support_topic' );
		} else {
			bb_update_topicmeta( $_POST['topic_id'], 'support_topic', $_POST['support_status'] );
		}
		echo 1;
	} else {
		echo 0;
	}	
	die();
}