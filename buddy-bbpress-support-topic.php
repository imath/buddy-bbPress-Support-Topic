<?php
/*
Plugin Name: Buddy-bbPress Support Topic
Plugin URI: http://imathi.eu/tag/buddy-bbpress-support-topic/
Description: Adds a support type to a forum topic and manage the status of it 
Version: 1.1-beta1
Requires at least: 3.5
Tested up to: 3.5
License: GNU/GPL 2
Author: imath
Author URI: http://imathi.eu/
Network: true
Text Domain: buddy-bbpress-support-topic
Domain Path: /languages/
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * constant definition block
 */
define ( 'BPBBPST_PLUGIN_NAME',    'buddy-bbpress-support-topic' );
define ( 'BPBBPST_PLUGIN_URL',     WP_PLUGIN_URL . '/' . basename( dirname( __FILE__ ) ) );
define ( 'BPBBPST_PLUGIN_DIR',     WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) );
define ( 'BPBBPST_PLUGIN_URL_CSS', plugins_url( 'css' , __FILE__ ) );
define ( 'BPBBPST_PLUGIN_URL_JS',  plugins_url( 'js' , __FILE__ ) );
define ( 'BPBBPST_PLUGIN_VERSION', '1.1-beta1' );


/**
 * Hooks bp_include to include BuddyPress group forums functions
 *
 * @author imath
 */
function bpbbpst_buddypress_init() {
	
	require( BPBBPST_PLUGIN_DIR . '/includes/buddypress-functions.php' );
		
}

add_action( 'bp_include', 'bpbbpst_buddypress_init' );


/**
 * Hooks bbp_includes to include bbPress functions
 *
 * @author imath
 */
function bpbbpst_bbpress_init() {
	
	require( BPBBPST_PLUGIN_DIR . '/includes/bbpress-functions.php' );
		
}

add_action( 'bbp_includes', 'bpbbpst_bbpress_init' );


/**
 * Outputs the checkbox to let user define his topic as a support one
 *
 * @param  boolean $checked true to check the box, false else
 * @uses   checked() to manage the checkbox state
 * @uses   wp_nonce_field() to generate a WordPress security token
 * @author imath
 */
function bpbbpst_define_support( $checked = false ) {
	?>
	<p>
		<input type="checkbox" value="support" name="_bp_bbp_st_is_support" id="bp_bbp_st_is_support" <?php checked( true, $checked );?>> <?php _e('This is a support topic','buddy-bbpress-support-topic') ;?>
		<?php wp_nonce_field( 'bpbbpst_support_define', '_wpnonce_bpbbpst_support_define' ); ?>
	</p>
	<?php
}


/**
 * Hooks plugins_loaded to load the translation file if it exists
 *
 * @uses   load_textdomain()
 * @author imath
 */
function bpbbpst_load_textdomain() {

	// try to get locale
	$locale = apply_filters( 'bp_bbp_st_load_textdomain_get_locale', get_locale() );

	// if we found a locale, try to load .mo file
	if ( !empty( $locale ) ) {
		// default .mo file path
		$mofile_default = sprintf( '%s/languages/%s-%s.mo', BPBBPST_PLUGIN_DIR, BPBBPST_PLUGIN_NAME, $locale );
		// final filtered file path
		$mofile = apply_filters( 'bp_bbp_st_load_textdomain_mofile', $mofile_default );
		
		// make sure file exists, and load it
		if ( file_exists( $mofile ) ) {
			load_textdomain( BPBBPST_PLUGIN_NAME, $mofile );
		}
	}
}

add_action ( 'plugins_loaded', 'bpbbpst_load_textdomain', 2 );


/**
 * Updates plugin version if needed once activated
 * 
 * @uses   get_option() to check if plugin version is in DB
 * @uses   update_option() stores plugin version
 * @author imath
 */
function bpbbpst_install(){
	if( !get_option( 'bp-bbp-st-version' ) || "" == get_option( 'bp-bbp-st-version' ) ){
		update_option( 'bp-bbp-st-version', BPBBPST_PLUGIN_VERSION );
	}
}

register_activation_hook( __FILE__, 'bpbbpst_install' );


/**
 * Hooks network_admin_menu or admin_menu to check for plugin DB version and eventually updates things
 * 
 * @global $wpdb
 * @global $bbdb
 * @uses   get_option() to check DB version VS plugin one
 * @uses   bp_forums_is_installed_correctly() to check if bb-config.php exists
 * @uses   update_option() updates DB version if necessary
 * @author imath
 */
function bpbbpst_upgrade() {
	global $wpdb, $bbdb;
	if( version_compare( BPBBPST_PLUGIN_VERSION, get_option( 'bp-bbp-st-version' ), '>' ) ) {
		
		// first let's take care of bb_meta !
		if( function_exists( 'bp_forums_is_installed_correctly' ) && bp_forums_is_installed_correctly() ) {
			
			do_action( 'bbpress_init' );
			
			$has_old_meta_key = false;
			
			$has_old_meta_key = $wpdb->get_var( "SELECT COUNT(meta_id) FROM {$bbdb->meta} WHERE meta_key = 'support_topic'" );
			
			if( !empty( $has_old_meta_key ) )
				$wpdb->update( $bbdb->meta, array('meta_key' => '_bpbbpst_support_topic'), array( 'meta_key' => 'support_topic' ) );
				
		}
		
		// then let's take care of post_meta !
		if( class_exists( 'bbPress' ) ) {
			
			$has_old_meta_key = false;
			
			$has_old_meta_key = $wpdb->get_var( "SELECT COUNT(meta_id) FROM {$wpdb->postmeta} WHERE meta_key = 'support_topic'" );
			
			if( !empty( $has_old_meta_key ) )
				$wpdb->update( $wpdb->postmeta, array('meta_key' => '_bpbbpst_support_topic'), array( 'meta_key' => 'support_topic' ) );
				
		}
		
		update_option( 'bp-bbp-st-version', BPBBPST_PLUGIN_VERSION );
	}
}

add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'bpbbpst_upgrade' );

?>