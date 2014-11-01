<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'BuddyPress_Support_Topic' ) && class_exists( 'BP_Group_Extension' ) ) :
/**
 * Loads BuddyPress Specific parts
 *
 * Extends the group extension to create a new admin tab in groups front end
 * setup some key hooks
 *
 * At least, BuddyPress 1.8 is required.
 *
 * @package    Buddy-bbPress Support Topic
 * @subpackage BuddyPress
 *
 * @since      2.0
 *
 * @uses   BP_Group_Extension
 */
class BuddyPress_Support_Topic extends BP_Group_Extension {

	/**
	 * The constructor
	 *
	 * @since 2.0
	 *
	 * @uses  BuddyPress_Support_Topic::enable_edit_item() to check if a forum is set for the group
	 * @uses  BuddyPress_Support_Topic::setup_actions() to register some key actions and filters
	 * @uses  BP_Group_Extension::init()
	 */
	public function __construct() {

		$args = array(
        	'slug'              => 'forum-support',
       		'name'              => 'Forum Support',
       		'enable_nav_item'   => false,
       		'screens'           => array(
       			'create' => array( 'enabled' => false ),
       			'edit'   => array( 'enabled' => $this->enable_edit_item() ),
       			'admin'  => array( 'enabled' => false ),
       		)
    	);

    	parent::init( $args );

		$this->setup_actions();

	}

	/**
	 * Checks a forum is activated for the current group
	 *
	 * @since  2.0
	 *
	 * @uses   bp_is_group() to check we're in a single group
	 * @uses   bp_get_new_group_id() to get the created group id
	 * @uses   bp_get_current_group_id() to get current group id
	 * @uses   bp_get_new_group_enable_forum() to check forum is enabled
	 * @uses   groups_get_groupmeta() to get the forum settings for this group
	 * @return boolean true|false
	 */
	public function enable_edit_item() {
		// Bail if not viewing a single group
		if ( ! bp_is_group() ) {
			return;
		}

		$group_id = bp_get_new_group_id();

		if ( empty( $group_id ) ) {
			$group_id = bp_get_current_group_id();
		}

		// Are forums enabled for this group?
		$checked = bp_get_new_group_enable_forum() || groups_get_groupmeta( $group_id, 'forum_id' );

		return (bool) $checked;
	}

	/**
	 * Sets some key actions and filters
	 *
	 * @since 2.0
	 *
	 * @uses  add_action() to hook our action to specific points
	 * @uses  add_filter() to eventually modify some key parts
	 */
	public function setup_actions() {
		// Enqueues a js script to hide/show the recipients
		add_action( 'bp_enqueue_scripts',             array( $this, 'enqueue_forum_js' )                  );

		// Topic title in a BuddyPress group
		add_filter( 'bbp_get_topic_title',            array( $this, 'filter_topic_title' ),         10, 2 );

		// removes above filter as soon as we can !
		add_filter( 'bbp_get_template_part',          array( $this, 'remove_topic_title_filer' ),  100, 3 );

		// adds the list of 'BuddyPress group mods' recipients to forum admin ui
		add_action( 'bpbbpst_forum_support_options',  array( $this, 'admin_group_mods_list' ),      10, 2 );

		// saves the group recipients from forum admin
		add_action( 'bpbbpst_forum_settings_updated', array( $this, 'admin_group_mods_list_save' ), 10, 2 );

		// merges bp group specific recipients with bbPress ones
		add_filter( 'bpbbpst_list_recipients',        array( $this, 'merge_bp_recipients' ),        10, 3 );
	}

	/**
	 * Adds a javascript to WordPress scripts queue
	 *
	 * @since 2.0
	 *
	 * @uses  bp_is_group_admin_screen() to make sure we're in plugin's group admin tab
	 * @uses  wp_enqueue_script() to add the script to WordPress queue
	 * @uses  bpbbpst_get_plugin_url() to get the plugin's url
	 * @uses  bpbbpst_get_plugin_version() to get plugin's version
	 */
	public function enqueue_forum_js() {
		if ( bp_is_group_admin_screen( 'forum-support' ) ) {
			wp_enqueue_script( 'bpbbpst-forum-js', bpbbpst_get_plugin_url( 'js' ) . 'bpbbpst-forum.js', array( 'jquery' ), bpbbpst_get_plugin_version() );
		}
	}

	/**
	 * Adds the support status to topic title when on single topic
	 *
	 * @since  2.0
	 *
	 * @param  string $topic_title the topic title
	 * @uses   bp_is_group_forum_topic() to check we're viewing a topic
	 * @uses   bpbbpst_add_support_mention() to build the right support status
	 * @return string the topic title
	 */
	public function filter_topic_title( $topic_title = '', $topic_id = 0 ) {
		// Avoid the prefix to be displayed in <title>
		if ( ! did_action( 'bp_head' ) ) {
			return $topic_title;
		}

		if ( bp_is_group_forum_topic() ) {
			return bpbbpst_add_support_mention( $topic_id, false ) . $topic_title ;
		} else {
			return $topic_title;
		}
	}

	/**
	 * Removes the above filter as soon as we can
	 *
	 * This prevents the filter to spread in places we don't want to.
	 *
	 * @since  2.0
	 *
	 * @param  array  $templates the list of templates
	 * @param  string $slug
	 * @param  string $name
	 * @uses   remove_filter() to remove our filter on the topic title
	 * @return array             the list of templates
	 */
	public function remove_topic_title_filer( $templates = array(), $slug = '', $name = '' ) {

		if ( in_array( $name, array( 'single-topic', 'topic' ) ) ) {
			remove_filter( 'bbp_get_topic_title', array( $this, 'filter_topic_title' ), 10, 2 );
		}

		return $templates;
	}

	/**
	 * Displays the content of plugin's group admin tab
	 *
	 * @since  2.0
	 *
	 * @param  integer $group_id the group id
	 * @uses   bbp_get_group_forum_ids() to get the forum id attached to this group
	 * @uses   bpbbpst_get_forum_support_setting() to get the forum support setting
	 * @uses   bpbbpst_display_forum_setting_options() to display the forum support available options
	 * @uses   groups_get_groupmeta() to get a specific group setting
	 * @uses   BuddyPress_Support_Topic::group_list_moderators() to build the list of admins and mods for the group
	 * @uses   bpbbpst_array_checked() to eventually add a checked attribute to user's checkbox
	 * @uses   bp_core_get_user_displayname() to get user's display name
	 * @return string html output
	 */
	public function edit_screen( $group_id = 0 ) {

		if ( empty( $group_id ) ) {
			return false;
		}

		$forum_ids = bbp_get_group_forum_ids( $group_id );

		// Get the first forum ID
		if ( ! empty( $forum_ids ) ) {
			$forum_id = (int) is_array( $forum_ids ) ? $forum_ids[0] : $forum_ids;
		} else {
			return false;
		}

		$support_feature = bpbbpst_get_forum_support_setting( $forum_id );

		$style = ( $support_feature == 3 ) ? 'style="display:none"' : '';

		bpbbpst_display_forum_setting_options( $support_feature );

		$recipients = groups_get_groupmeta( $group_id, '_bpbbpst_support_bp_recipients' );

		if ( empty( $recipients ) ) {
			$recipients = array();
		}

		$group_forum_moderators = $this->group_list_moderators( $group_id );
		?>
		<div class="bpbbpst-mailing-list" <?php echo $style;?>>
			<h4><?php _e( 'Who should receive an email notification when a new support topic is posted ?', 'buddy-bbpress-support-topic' );?></h4>
			<input type="hidden" name="_bpbbpst_support_groups[]" value="<?php echo $group_id;?>"/>
			<ul class="bp-moderators-list">
				<?php foreach ( $group_forum_moderators as $moderator ) : ?>
					<li>
						<input type="checkbox" value="<?php echo $moderator->user_id;?>" name="_bpbbpst_support_bp_recipients[<?php echo $group_id;?>][]" <?php bpbbpst_array_checked( $recipients, $moderator->user_id );?>>
						<?php echo bp_core_get_user_displayname( $moderator->user_id ) ;?> (<?php echo $moderator->role ;?>)
					</li>
				<?php endforeach;?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Saves the group settings
	 *
	 * @since 2.0
	 *
	 * @param integer $group_id the group id
	 * @uses  bbp_get_group_forum_ids() to get group's forum ids
	 * @uses  delete_post_meta() to eventually remove a forum setting
	 * @uses  update_post_meta() to save the forum setting
	 * @uses  BuddyPress_Support_Topic::admin_group_mods_list_save() to eventually save the recipients for this group
	 * @uses  bp_core_add_message() to add a user's notice to inform him of success / fail
	 * @uses  bp_core_redirect() to redirect user and avoid double post
	 * @uses  bp_get_group_permalink() to get current group permalink
	 * @uses  buddypress() to get BuddyPress main instance
	 */
	public function edit_screen_save( $group_id = 0 ) {
		$success = false;

		$forum_ids = bbp_get_group_forum_ids( $group_id );

		// Get the first forum ID
		if ( ! empty( $forum_ids ) ) {
			$forum_id = (int) is_array( $forum_ids ) ? $forum_ids[0] : $forum_ids;
		}

		$support_feature = ! empty( $_POST['_bpbbpst_forum_settings'] ) ? intval( $_POST['_bpbbpst_forum_settings'] ) : 1;

		if ( 3 == $support_feature ) {
			delete_post_meta( $forum_id, '_bpbbpst_support_recipients' );
		}

		$success = update_post_meta( $forum_id, '_bpbbpst_forum_settings', $support_feature );

		// let's use admin function for recipients
		$this->admin_group_mods_list_save( $forum_id , $support_feature );

		if ( ! $success ) {
			bp_core_add_message( __( 'There was an error saving, please try again', 'buddy-bbpress-support-topic' ), 'error' );
		} else {
			bp_core_add_message( __( 'Settings saved successfully', 'buddy-bbpress-support-topic' ) );
		}

		bp_core_redirect( bp_get_group_permalink( buddypress()->groups->current_group ) . 'admin/' . $this->slug );
	}

	/**
	 * Builds a list of Moderators for the forum of the group (Admin + Mods)
	 *
	 * @since  2.0
	 *
	 * @param  integer $forum_id the forum id
	 * @param  string  $style    hide or show the recipients
	 * @uses   bbp_get_group_forum_ids() to get group's forum ids
	 * @uses   groups_get_groups() to build a detailled list of groups using the forum
	 * @uses   esc_url() to sanitize url
	 * @uses   bp_get_group_permalink() to build the group permalink
	 * @uses   esc_html() to sanitize the group name
	 * @uses   groups_get_groupmeta() to get the previously saved recipients
	 * @uses   BuddyPress_Support_Topic::group_list_moderators() to get Group Admins and Mods
	 * @uses   bpbbpst_array_checked() to eventually add a checked attribute to user's checkbox
	 * @uses   bp_core_get_user_displayname() to get user's display name
	 * @return string html  output
	 */
	public function admin_group_mods_list( $forum_id = 0, $style = '' ) {

		$group_ids = bbp_get_forum_group_ids( $forum_id );

		if ( empty( $group_ids ) || ! is_array( $group_ids ) ) {
			return false;
		}

		/* a forum can be in several groups... But strangely the forum meta seems to only keep the latest group..
		it might evolved so let's keep the following this way, we'll be ready */
		$groups = groups_get_groups( array( 'include' => implode( ',', $group_ids ) ) );

		if ( empty( $groups ) ) {
			return false;
		}
		?>
		<ul class="bpbbpst-mailing-list" <?php echo $style;?>>

		<?php foreach ( $groups['groups'] as $group ) : ?>

			<li>
				<h4><?php printf( __( 'In Group: %s', 'buddy-bbpress-support-topic' ), '<a href="'. esc_url( bp_get_group_permalink( $group ) ) .'">'. esc_html( $group->name ) .'</a>' );?></h4>
				<input type="hidden" name="_bpbbpst_support_groups[]" value="<?php echo $group->id;?>"/>
				<ul class="bp-moderators-list">

				<?php

				$recipients       = $group_moderators = array();
				$recipients       = groups_get_groupmeta( $group->id, '_bpbbpst_support_bp_recipients' );
				$group_moderators = $this->group_list_moderators( $group->id );

				foreach ( $group_moderators as $moderator ) : ?>
					<li>
						<input type="checkbox" value="<?php echo $moderator->user_id;?>" name="_bpbbpst_support_bp_recipients[<?php echo $group->id;?>][]" <?php bpbbpst_array_checked( $recipients, $moderator->user_id );?>>
						<?php echo bp_core_get_user_displayname( $moderator->user_id ) ;?> (<?php echo $moderator->role ;?>)
					</li>
				<?php endforeach ; ?>
				</ul>

			</li>

		<?php endforeach;?>

		</ul>
		<?php
	}

	/**
	 * Saves the recipients list for the forum of the group
	 *
	 * @since 2.0
	 *
	 * @param integer $forum_id        the forum id
	 * @param integer $support_feature the forum support setting
	 * @uses  groups_delete_groupmeta() to eventually remove a group setting
	 * @uses  groups_update_groupmeta() to save a group setting
	 */
	public function admin_group_mods_list_save( $forum_id = 0, $support_feature = 0 ) {

		if ( empty( $_POST['_bpbbpst_support_groups'] ) || ! is_array( $_POST['_bpbbpst_support_groups'] ) || empty( $forum_id ) ) {
			return;
		}

		$group_ids = array_map( 'intval', $_POST['_bpbbpst_support_groups'] );

		if ( $support_feature == 3 ) {
			foreach ( $group_ids as $group_id ) {
				groups_delete_groupmeta( $group_id, '_bpbbpst_support_bp_recipients' );
			}

		} else {
			$bp_recipients = ! empty( $_POST['_bpbbpst_support_bp_recipients'] ) ? $_POST['_bpbbpst_support_bp_recipients'] : false ;

			if ( ! empty( $bp_recipients ) && is_array( $bp_recipients ) && count( $bp_recipients ) > 0 ) {
				foreach ( $group_ids as $group_id ) {
					if ( ! empty( $bp_recipients[$group_id] ) && is_array( $bp_recipients[$group_id] ) && count( $bp_recipients[$group_id] ) > 0 ) {
						groups_update_groupmeta( $group_id, '_bpbbpst_support_bp_recipients', array_map( 'intval', $bp_recipients[$group_id] ) );
					} else {
						groups_delete_groupmeta( $group_id, '_bpbbpst_support_bp_recipients' );
					}
				}

			} else {
				foreach ( $group_ids as $group_id ) {
					groups_delete_groupmeta( $group_id, '_bpbbpst_support_bp_recipients' );
				}
			}
		}
	}

	/**
	 * In case of a notification, eventually adds group's forum recipients to bbPress ones
	 *
	 * @since  2.0
	 *
	 * @param  array   $recipients the list of bbPress moderators
	 * @param  string  $context    are we notifying users ?
	 * @param  integer $forum_id   the forum id
	 * @uses   bbp_get_group_forum_ids() to get group's forum ids
	 * @uses   groups_get_groupmeta() to get the previously saved recipients
	 * @return array              the list of forum moderators
	 */
	public function merge_bp_recipients( $recipients = array(), $context = 'admin', $forum_id = 0 ) {

		if ( $context == 'notify' && ! empty( $forum_id ) ) {
			$group_ids = bbp_get_forum_group_ids( $forum_id );

			if ( empty( $group_ids ) || ! is_array( $group_ids ) ) {
				return $recipients;
			}

			foreach ( $group_ids as $group_id ) {
				$bp_recipients = groups_get_groupmeta( $group_id, '_bpbbpst_support_bp_recipients' );

				if ( ! empty( $bp_recipients ) ) {
					// strips common values
					$bp_recipients = array_diff( $bp_recipients, $recipients );
					// merge unique
					$recipients = array_merge( $recipients, $bp_recipients );
				}

			}

		}

		return $recipients;
	}

	/**
	 * Builds the list of group mods and admins
	 *
	 * @since  2.0
	 *
	 * @param  integer $group_id the group id
	 * @uses   groups_get_group_admins() to get group admins
	 * @uses   groups_get_group_mods() to get group mods
	 * @return array            the list of group's forum admins and mods
	 */
	public static function group_list_moderators( $group_id = 0 ) {

		if ( empty( $group_id ) ) {
			return false;
		}

		$group_admins = array(
			array(
				'users' => groups_get_group_admins( $group_id ),
				'role'  => __( 'Group Admin', 'buddy-bbpress-support-topic' )
			)
		);

		$group_mods = array(
			array(
				'users' => groups_get_group_mods( $group_id ),
				'role'  => __( 'Group Mod', 'buddy-bbpress-support-topic' )
			)
		);

		$group_admins = array_map( 'bpbbpst_role_group_forum_map', $group_admins );
		$group_mods   = array_map( 'bpbbpst_role_group_forum_map', $group_mods );

		$group_forum_moderators = array_merge( $group_admins[0], $group_mods[0] );

		return $group_forum_moderators;
	}

	/**
	 * We do not use widgets
	 *
	 * @since  2.0
	 *
	 * @return boolean false
	 */
	public function widget_display() { return false; }

}

endif; // class_exists check

/**
 * Registers the Group extension if it needs to
 *
 * @since 2.0
 *
 * @uses  bpbbpst_is_bbp_required_version_ok() to check bbPress required version is ok
 * @uses  bbp_is_group_forums_active() to check group forums are active
 * @uses  bp_is_active() to check groups component is active
 * @uses  bp_get_version() to get BuddyPress version
 * @uses  bp_core_add_admin_notice() to add a notice to admin to invite him to upgrade
 * @uses  bp_register_group_extension() to finally load the plugin's group extension
 */
function bpbbpst_buddypress() {
	// Bail if bbPress required version doesn't match our need
	if ( ! bpbbpst_is_bbp_required_version_ok() ) {
		return;
	}

	if ( bbp_is_group_forums_active() && bp_is_active( 'groups' ) ) {

		// I want 1.8 !!
		if ( version_compare( bp_get_version(), '2.0', '<' ) ) {
			bp_core_add_admin_notice( sprintf( __('Buddy bbPress Support Status version %s requires at least BuddyPress 2.0, please upgrade BuddyPress :) or downgrade Buddy bbPress Support Status :(', 'buddy-bbpress-support-topic' ), bpbbpst_get_plugin_version() ) );
		} else {
			bp_register_group_extension( 'BuddyPress_Support_Topic' );
		}
	}

}
add_action( 'bp_init', 'bpbbpst_buddypress' );
