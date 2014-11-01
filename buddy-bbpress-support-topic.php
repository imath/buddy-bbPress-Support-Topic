<?php
/*
Plugin Name: Buddy-bbPress Support Topic
Plugin URI: http://imathi.eu/tag/buddy-bbpress-support-topic/
Description: Adds a support feature to your bbPress powered forums
Version: 2.0
Requires at least: 4.0
Tested up to: 4.0
License: GNU/GPL 2
Author: imath
Author URI: http://imathi.eu/
Text Domain: buddy-bbpress-support-topic
Domain Path: /languages/
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'BP_bbP_Support_Topic' ) ) :
/**
 * Main Buddy-bbPress Support Topic Class
 *
 * Extends bbPress 2.5 and up with a support feature
 *
 * @since 2.0
 */
class BP_bbP_Support_Topic {

	// plugin's global vars
	public $globals;

	/**
	 * The constructor
	 *
	 * @since 2.0
	 *
	 * @uses  BP_bbP_Support_Topic::setup_globals() to reference some globals
	 * @uses  BP_bbP_Support_Topic::includes() to includes needed scripts
	 * @uses  BP_bbP_Support_Topic::setup_actions() to add some key action hooks
	 * @uses  BP_bbP_Support_Topic::setup_filters() to add some key filters
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
		$this->setup_filters();
	}

	/**
	 * Sets some globals
	 *
	 * @since  2.0
	 * @access private
	 *
	 * @uses   plugin_basename() to get the plugin's basename
	 * @uses   plugin_dir_path() to build plugin's path
	 * @uses   plugin_dir_url() to build plugin's url
	 * @uses   trailingslashit() to add a slash at the end of url/path
	 * @uses   apply_filters() to let other plugins or themes override globals
	 */
	private function setup_globals() {
		$this->globals = new stdClass();

		$this->globals->version = '2.0';

		$this->globals->file       = __FILE__ ;
		$this->globals->basename   = apply_filters( 'bpbbpst_plugin_basenname', plugin_basename( $this->globals->file ) );
		$this->globals->plugin_dir = apply_filters( 'bpbbpst_plugin_dir_path',  plugin_dir_path( $this->globals->file ) );
		$this->globals->plugin_url = apply_filters( 'bpbbpst_plugin_dir_url',   plugin_dir_url ( $this->globals->file ) );

		// Includes
		$this->globals->includes_dir = apply_filters( 'bpbbpst_includes_dir', trailingslashit( $this->globals->plugin_dir . 'includes'  ) );
		$this->globals->includes_url = apply_filters( 'bpbbpst_includes_url', trailingslashit( $this->globals->plugin_url . 'includes'  ) );

		$this->support_status  = array();
		$this->globals->domain = 'buddy-bbpress-support-topic';

		$this->globals->is_sidebar = false;

		// bbPress required version
		$this->globals->required_bbp_version = '2.5';
		$this->globals->site_bbp_version_ok  = version_compare( bbp_get_version(), $this->globals->required_bbp_version, '>=' );
	}

	/**
	 * Includes the needed file regarding to context
	 *
	 * @since  2.0
	 * @access private
	 *
	 * @uses   is_admin() to check for WordPress backend
	 */
	private function includes() {
		// includes the plugin functions
		require( $this->globals->includes_dir . 'functions.php' );
		// includes the plugin widgets
		require( $this->globals->includes_dir . 'widgets.php' );

		// includes the BuddyPress group component
		if( function_exists( 'buddypress' ) ) {
			require( $this->globals->includes_dir . 'buddypress.php' );
		}

		// includes plugin admin class
		if( is_admin() ) {
			require( $this->globals->includes_dir . 'admin.php' );
		}
	}

	/**
	 * Registers some key actions to extend bbPress
	 *
	 * @since  2.0
	 * @access private
	 *
	 * @uses   bbp_is_deactivation() to prevent interfering with bbPress deactivation process
	 * @uses   bbp_is_activation()  to prevent interfering with bbPress activation process
	 * @uses   add_action() to hook to key actions
	 * @uses   is_admin() to check for WordPress backend
	 * @uses   do_action_ref_array() to let plugins or themes do stuff once all actions are set
	 */
	private function setup_actions() {

		if ( bbp_is_deactivation() || bbp_is_activation() ) {
			return;
		}

		// Loads the translation
		add_action( 'bbp_init', array( $this, 'load_textdomain' ), 7 );

		// Loads the admin
		if( is_admin() ) {
			add_action( 'init', 'bpbbpst_admin' );
		}

		if ( ! $this->globals->site_bbp_version_ok ) {
			return;
		}

		// Defines support status, doing so in globals avoids strings in it to be translated
		add_action( 'bbp_init',                                   array( $this,  'setup_status' ),          9    );

		// Adding the support control to the topic new/edit form
		add_action( 'bbp_theme_before_topic_form_submit_wrapper', 'bpbbpst_maybe_output_support_field'           );

		// setting the support type on front end new topic form submission
		add_action( 'bbp_new_topic_post_extras',                  'bpbbpst_save_support_type',             10, 1 );

		// sends a notification in case of new support topic for the forum that enabled support feature
		add_action( 'bbp_new_topic',                              'bpbbpst_new_support_topic_notify',      10, 4 );

		// updating the support type on front end edit topic form submission
		add_action( 'bbp_edit_topic_post_extras',                 'bpbbpst_edit_support_type',             10, 1 );

		// moving a topic needs to adapt with the support settings of the new forum
		add_action( 'bbp_edit_topic',                             'bpbbpst_handle_moving_topic',            9, 2 );

		//enqueueing scripts
		add_action( 'bbp_enqueue_scripts',                        'bpbbpst_enqueue_scripts'                      );

		// catching ajax status changes
		add_action( 'wp_ajax_bbp_change_support_status',          'bpbbpst_change_support_status'                );

		// adding support mention before topic titles in loops
		add_action( 'bbp_theme_before_topic_title',               'bpbbpst_add_support_mention'                  );

		// Waits a bit to filter the topic title to let plugin play with get_the_title()
		add_action( 'bbp_head',                                   'bpbbpst_filter_topic_title',              999 );

		// For Bpbbpst_Support_New_Support widget usage (adds a referer field)
		add_action( 'bpbbpst_output_support_extra_field',         'bpbbpst_referer_extra_field',           10, 1 );
		add_action( 'bbp_theme_before_reply_content',             'bpbbpst_display_referer_to_moderators'        );

		// Register the widgets
		add_action( 'bbp_widgets_init', array( 'Bpbbpst_Support_Stats', 'register_widget' ),       10 );
		add_action( 'bbp_widgets_init', array( 'Bpbbpst_Support_New_Support', 'register_widget' ), 10 );

		// Neutralize title filter in Sidebar
		add_action( 'dynamic_sidebar_before', 'bpbbpst_set_sidebar_true'  );
		add_action( 'dynamic_sidebar_after',  'bpbbpst_set_sidebar_false' );

		do_action_ref_array( 'bpbbpst_after_setup_actions', array( &$this ) );
	}

	/**
	 * Registers the available support status
	 *
	 * @since 2.0
	 *
	 * @uses  apply_filters() call 'bpbbpst_available_support_status' to add/remove/edit support status
	 */
	public function setup_status() {
		// Available support status
		$this->support_status = apply_filters( 'bpbbpst_available_support_status', array(
			'topic-not-resolved' => array(
				'sb-caption'   => __( 'Not resolved', 'buddy-bbpress-support-topic' ),
				'value'        => 1,
				'prefix-title' => __( '[Support request] ', 'buddy-bbpress-support-topic' ),
				'admin_class'  => 'waiting',
				'dashicon'     => array( 'class' => 'bpbbpst-dashicon-no', 'content' => '"\f158"' ),
			),
			'topic-resolved' => array(
				'sb-caption'   => __( 'Resolved', 'buddy-bbpress-support-topic' ),
				'value'        => 2,
				'prefix-title' => __( '[Resolved] ', 'buddy-bbpress-support-topic' ),
				'admin_class'  => 'approved',
				'dashicon'     => array( 'class' => 'bpbbpst-dashicon-yes', 'content' => '"\f147"' ),
			),
			'topic-not-support' => array(
				'sb-caption'   => __( 'Not a support topic', 'buddy-bbpress-support-topic' ),
				'value'        => 0,
				'prefix-title' => '',
				'admin_class'  => 'waiting',
			),
		));
	}

	/**
	 * Registers key filters to extend bbPress
	 *
	 * @since  2.0
	 * @access private
	 *
	 * @uses   add_filter() to filter bbPress at key points
	 */
	private function setup_filters() {
		if ( ! $this->globals->site_bbp_version_ok ) {
			return;
		}

		// removes the title filter
		add_filter( 'bbp_get_template_part', 'bpbbpst_topic_is_single', 99, 3 );

		// Displays the support status selectbox in topic front admin links
		add_filter( 'bbp_get_topic_admin_links', 'bpbbpst_support_admin_links', 10, 2 );

		// in case a forum is set as a support only one strip the not a support question status
		add_filter( 'bpbbpst_get_support_status', 'bpbbpst_neutralize_not_support', 1, 1 );
	}

	/**
	 * Loads the translation files
	 *
	 * @since 2.0
	 *
	 * @uses  apply_filters() to let plugins or themes override values
	 * @uses  get_locale() to get the language of WordPress config
	 * @uses  load_texdomain() to load the translation at specific places
	 * @uses  load_plugin_textdomain() to load the translation at regular places
	 */
	public function load_textdomain() {
		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale', get_locale(), $this->globals->domain );

		// Need custom strings for en_US ?
		if ( empty( $locale ) ) {
			$mofile = $this->globals->domain . '.mo';
		} else {
			$mofile = sprintf( '%1$s-%2$s.mo', $this->globals->domain, $locale );
		}

		// Setup paths to current locale file
		$mofile_local  = trailingslashit( $this->globals->plugin_dir . 'languages' ) . $mofile;
		$mofile_global = WP_LANG_DIR . '/' . $this->globals->domain . '/' . $mofile;

		// Look in global /wp-content/languages/buddy-bbpress-support-topics folder
		load_textdomain( $this->globals->domain, $mofile_global );

		// Look in local /wp-content/plugins/buddy-bbpress-support-topics/languages/ folder
		load_textdomain( $this->globals->domain, $mofile_local );

		// Look in global /wp-content/languages/plugins/
		load_plugin_textdomain( $this->globals->domain );
	}
}

endif; // class_exists check

/**
 * Adds the main class of the plugin to bbPress main instance
 *
 * Waits for bbPress to be ready before doing so.
 *
 * @since 2.0
 *
 * @uses  bbpress() the main instance of bbPress
 * @uses  BP_bbP_Support_Topic() to start the plugin
 */
function bpbbpst() {
	// let's park into the extend part of main bbPress instance
	bbpress()->extend->bpbbpst = new BP_bbP_Support_Topic();
}
add_action( 'bbp_ready', 'bpbbpst', 9 );

/**
 * Catch the plugin activation to store a transient
 *
 * Once the plugin is activated, Admin will be redirected
 * to plugin's welcome screen.
 *
 * @since 2.0
 *
 * @uses  set_transient() to put a transient
 */
function bpbbst_activate() {
	// Let's just put a transcient, the rest belongs to admin class
	set_transient( '_bpbbst_welcome_screen', true, 30 );
}
add_action( 'activate_' . plugin_basename( __FILE__ ) , 'bpbbst_activate' );
