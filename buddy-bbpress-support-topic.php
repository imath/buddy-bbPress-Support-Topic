<?php
/*
Plugin Name: Buddy-bbPress Support Topic
Plugin URI: http://imathi.eu/category/buddypress/
Description: Adds a support type to a forum topic and manage the status of it 
Version: 1.0-beta3
Requires at least: 3.5
Tested up to: 3.5
License: GNU/GPL 2
Author: imath
Author URI: http://imathi.eu/
Network: true
Text Domain: buddy-bbpress-support-topic
Domain Path: /languages/
*/

//constants
define ( 'BP_BBP_ST_PLUGIN_NAME',    'buddy-bbpress-support-topic' );
define ( 'BP_BBP_ST_PLUGIN_URL',      WP_PLUGIN_URL . '/' . basename( dirname( __FILE__ ) ) );
define ( 'BP_BBP_ST_PLUGIN_DIR',      WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) );
define ( 'BP_BBP_ST_PLUGIN_URL_CSS',  plugins_url('css' , __FILE__) );
define ( 'BP_BBP_ST_PLUGIN_URL_JS',   plugins_url('js' , __FILE__) );
define ( 'BP_BBP_ST_PLUGIN_VERSION',  '1.0-beta3' );
define ( 'BP_BBP_ST_TOPIC_CPT_ID',    apply_filters( 'bbp_topic_post_type',  'topic'     ) );

add_action( 'bp_include', 'bp_bbp_st_buddypress_init' );

function bp_bbp_st_buddypress_init() {
	
	require( BP_BBP_ST_PLUGIN_DIR . '/includes/bp-bbp-st-buddypress.php' );
		
}

add_action( 'bbp_includes', 'bp_bbp_st_bbpress_init' );

function bp_bbp_st_bbpress_init() {
	
	require( BP_BBP_ST_PLUGIN_DIR . '/includes/bp-bbp-st-bbpress.php' );
		
}

// checkbox can be added from bbpress or buddypress forums
function bp_bbp_st_add_support_type( $checked = false ) {
	if( !empty( $checked ) )
		$checked = 'checked';
	?>
	<p><input type="checkbox" value="support" name="_bp_bbp_st_is_support" id="bp_bbp_st_is_support" <?php echo $checked;?>> <?php _e('This is a support topic','buddy-bbpress-support-topic') ;?></p>
	<?php
}


/**
* bp_bbp_st_load_textdomain
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
add_action ( 'plugins_loaded', 'bp_bbp_st_load_textdomain', 2 );

function bp_bbp_st_install(){
	if( !get_option('bp-bbp-st-version') || "" == get_option('bp-bbp-st-version')){
		update_option('bp-bbp-st-version', BP_BBP_ST_PLUGIN_VERSION );
	}
}

register_activation_hook( __FILE__, 'bp_bbp_st_install' );

?>