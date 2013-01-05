<?php
/*
Plugin Name: Buddy-bbPress Support Topic
Plugin URI: http://imathi.eu/
Description: Adds a support type to a forum topic and manage the status of it 
Version: 0.1
Requires at least: 3.3.2
Tested up to: 3.4
License: GNU/GPL 2
Author: imath
Author URI: http://imathi.eu/
Network: true
*/

//constants
define ( 'BP_BBP_ST_PLUGIN_NAME', 'buddy-bbpress-support-topic' );
define ( 'BP_BBP_ST_PLUGIN_URL', WP_PLUGIN_URL . '/' . BP_BBP_ST_PLUGIN_NAME );
define ( 'BP_BBP_ST_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . BP_BBP_ST_PLUGIN_NAME );
define ( 'BP_BBP_ST_PLUGIN_URL_CSS',  plugins_url('css' , __FILE__) );
define ( 'BP_BBP_ST_PLUGIN_URL_JS',  plugins_url('js' , __FILE__) );
define ( 'BP_BBP_ST_PLUGIN_VERSION', '0.1' );



add_action( 'groups_forum_new_topic_after', 'bp_bbp_st_add_support_type' );
add_action( 'bp_after_group_forum_post_new', 'bp_bbp_st_add_support_type' );

function bp_bbp_st_add_support_type() {
	?>
	<p><input type="checkbox" value="support" name="_bp_bbp_st_is_support" id="bp_bbp_st_is_support"> <?php _e('This is a support topic','buddy-bbpress-support-topic') ;?></p>
	<?php
}



add_action( 'groups_new_forum_topic', 'bp_bbp_st_save_support_type_for_topic', 11, 2 );

function bp_bbp_st_save_support_type_for_topic( $group_id, $topic_datas ) {

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
			<option value="1" <?php if($support_status==1) echo 'selected';?>><?php _e('Unsolved', 'buddy-bbpress-support-topic');?></option>
			<option value="2" <?php if($support_status==2) echo 'selected';?>><?php _e('Resolved', 'buddy-bbpress-support-topic');?></option>
			<option value="0" <?php if( empty( $support_status ) ) echo 'selected';?>><?php _e('Not a support topic', 'buddy-bbpress-support-topic');?></option>
		</select>
	</div>
	<?php
	}
}



add_action('bp_actions', 'bp_bbp_st_enqueue_support_cssjs');

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


/**
* bp_checkins_load_textdomain
* translation!
* 
*/
function bp_bbp_st_load_textdomain() {

	// try to get locale
	$locale = apply_filters( 'bp_bbp_st_load_textdomain_get_locale', get_locale() );

	// if we found a locale, try to load .mo file
	if ( !empty( $locale ) ) {
		// default .mo file path
		$mofile_default = sprintf( '%s/languages/%s-%s.mo', BP_BBP_ST_PLUGIN_DIR, BP_BBP_ST_PLUGIN_NAME, $locale );
		// final filtered file path
		$mofile = apply_filters( 'bp_bbp_st_load_textdomain_mofile', $mofile_default );
		
		// make sure file exists, and load it
		if ( file_exists( $mofile ) ) {
			load_textdomain( BP_BBP_ST_PLUGIN_NAME, $mofile );
		}
	}
}
add_action ( 'bp_init', 'bp_bbp_st_load_textdomain', 2 );

function bp_bbp_st_install(){
	if( !get_option('bp-bbp-st-version') || "" == get_option('bp-bbp-st-version')){
		update_option('bp-bbp-st-version', BP_BBP_ST_PLUGIN_VERSION );
	}
}

register_activation_hook( __FILE__, 'bp_bbp_st_install' );

?>