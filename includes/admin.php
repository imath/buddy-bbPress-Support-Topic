<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'BP_bbP_ST_Admin' ) ) :
/**
 * Loads Buddy-bbPress Support Topic plugin admin area
 *
 * @package    Buddy-bbPress Support Topic
 * @subpackage Administration
 *
 * @since      2.0
 */
class BP_bbP_ST_Admin {

	/**
	 * The admin loader
	 *
	 * @since 2.0
	 *
	 * @uses  BP_bbP_ST_Admin::setup_actions() to add some key hooks
	 * @uses  BP_bbP_ST_Admin::maybe_activate() to eventually load the welcome screen
	 */
	public function __construct() {
		$this->setup_actions();
		$this->maybe_activate();
	}

	/**
	 * Setup the admin hooks, actions and filters
	 *
	 * @since  2.0
	 * @access private
	 *
	 * @uses   bbp_is_deactivation() to prevent interfering with bbPress deactivation process
	 * @uses   bbp_is_activation()  to prevent interfering with bbPress activation process
	 * @uses   add_action() To add various actions
	 * @uses   add_filter() To add various filters
	 * @uses   bpbbpst_is_bbp_required_version_ok() to check if bbPress required version is ok
	 */
	private function setup_actions() {

		if ( bbp_is_deactivation() || bbp_is_activation() ) {
			return;
		}

		// Welcome Screen
		add_action( 'bbp_admin_menu',                           array( $this, 'welcome_screen_register' )             );
		add_action( 'bbp_admin_head',                           array( $this, 'welcome_screen_css' )                  );
		add_filter( 'plugin_action_links',                      array( $this, 'modify_plugin_action_links' ),   10, 2 );

		// Bail if bbPress required version doesn't match our need
		if ( ! bpbbpst_is_bbp_required_version_ok() ) {
			add_action( 'bbp_admin_notices', array( $this, 'admin_notices' ) );
			return;
		}

		// forums metabox
		add_action( 'bbp_forum_attributes_metabox',             array( $this, 'forum_meta_box_register' ),      10    );
		add_action( 'bbp_forum_attributes_metabox_save',        array( $this, 'forum_meta_box_save' ),          10, 1 );

		// Action to edit users query to only get forum moderators ( Keymasters+moderators )
		add_action( 'pre_get_users',                            array( $this, 'filter_user_query' ),            10, 1 );

		// enqueues a js script to hide show recipients
		add_action( 'load-post.php',                            array( $this, 'enqueue_forum_js'  )                   );
		add_action( 'load-post-new.php',                        array( $this, 'enqueue_forum_js'  )                   );

		// topics metabox
		add_action( 'bbp_topic_metabox',                        array( $this, 'topic_meta_box' ),               10, 1 );
		add_action( 'bbp_topic_attributes_metabox_save',        array( $this, 'topic_meta_box_save' ),          10, 2 );

		// moving a topic from the admin
		add_action( 'save_post',                                array( $this, 'topic_moved' ),                   9, 2 );

		// topics list columns
		add_filter( 'bbp_admin_topics_column_headers',          array( $this, 'topics_admin_column' ),          10, 1 );
		add_action( 'bbp_admin_topics_column_data',             array( $this, 'topics_column_data' ),           10, 2 );

		// topics list filter by support status
		add_action( 'restrict_manage_posts',                    array( $this, 'topics_admin_support_filter' ),  11    );
		add_filter( 'bbp_request',                              array( $this, 'topics_admin_support_request' ), 11, 1 );

		// topics bulk edit
		add_action( 'bulk_edit_custom_box',                     array( $this, 'bulk_topics_support' ),          10, 2 );
		add_action( 'load-edit.php',                            array( $this, 'bulk_update_support' )                 );

		// Dashboard right now bbPress widget
		add_action( 'bbp_dashboard_widget_right_now_table_end', array( $this, 'dashboard_widget' )                    );

		// At a glance Dashboard widget
		add_action( 'bbp_dashboard_at_a_glance',                array( $this, 'dashboard_at_a_glance' ),        10, 2 );
	}

	/**
	 * Display a notice to give the admin some feedback
	 *
	 * @since 2.0
	 *
	 * @uses    bpbbpst_get_plugin_version() to get plugin's version
	 * @uses    bpbbpst_bbp_required_version() to get bbPress required version
	 * @return  string HTML output
	 */
	public function admin_notices() {
		?>
		<div id="message" class="error fade">
			<p><?php printf( esc_html__( 'Version %s of Buddy-bbPress Support Topic requires version %s of bbPress to be activated. Please upgrade bbPress.', 'buddy-bbpress-support-topic' ), bpbbpst_get_plugin_version(), bpbbpst_bbp_required_version() ); ?></p>
		</div>
		<?php
	}

	/**
	 * Registers a new metabox in Forum's edit form (admin)
	 *
	 * @since 2.0
	 *
	 * @global $post_ID
	 * @uses   bbp_is_forum_category() to check if the forum is a category
	 * @uses   add_meta_box() to add the metabox to forum edit screen
	 * @uses   bbp_get_forum_post_type() to get forum post type
	 */
	public function forum_meta_box_register() {
		global $post_ID;

		if ( bbp_is_forum_category( $post_ID ) ) {
			return;
		}

		add_meta_box (
			'bpbbpst_forum_settings',
			__( 'Support settings', 'buddy-bbpress-support-topic' ),
			array( &$this, 'forum_meta_box_display' ),
			bbp_get_forum_post_type(),
			'normal',
			'low'
		);

	}

	/**
	 * Displays the content for the metabox
	 *
	 * @since 2.0
	 *
	 * @param object $forum the forum object
	 * @uses  bpbbpst_get_forum_support_setting() to get forum support setting
	 * @uses  bpbbpst_display_forum_setting_options() to list the available support settings
	 * @uses  bpbbpst_checklist_moderators() to list the bbPress keymasters and moderators
	 * @uses  do_action_ref_array() call 'bpbbpst_forum_support_options' to add your custom forum support settings
	 */
	public function forum_meta_box_display( $forum = false ) {
		if ( empty( $forum->ID ) ) {
			return;
		}

		$support_feature = bpbbpst_get_forum_support_setting( $forum->ID );

		$mailing_list_style = '';
		if ( 3 === (int) $support_feature ) {
			$mailing_list_style = 'style="display:none"';
		}

		$support_only_style = 'style="display:none"';

		if ( 2 === (int) $support_feature ) {
			$support_only_style = '';
		}

		bpbbpst_display_forum_setting_options( $support_feature );
		?>
		<div class="bpbbpst-mailing-list" <?php echo $mailing_list_style;?>>
			<h4><?php _e( 'Who should receive an email notification when a new support topic is posted ?', 'buddy-bbpress-support-topic' );?></h4>

			<?php bpbbpst_checklist_moderators( $forum->ID );?>
		</div>

		<?php do_action_ref_array( 'bpbbpst_forum_support_options', array( $forum->ID, $mailing_list_style ) ); ?>

		<div class="bpbbpst-support-guides" <?php echo $support_only_style;?>>
			<h4><?php _e( 'New Topic form extras', 'buddy-bbpress-support-topic' );?></h4>
			<label class="screen-reader-text" for="support-topic-intro"><?php esc_html_e( 'New Topic Guide', 'buddy-bbpress-support-topic' ); ?></label>
			<textarea rows="3" cols="40" name="_bpbbpst_support_topic[intro]" id="support-topic-intro" style="width:100%"><?php echo bpbbpst_get_forum_support_topic_intro( $forum->ID );?></textarea>
			<p class="description"><?php printf( esc_html__( 'Use this field to insert some instructions above the new topic form. Allowed tags are: %s', 'buddy-bbpress-support-topic' ), join( ', ', array_keys( (array) wp_kses_allowed_html( 'forum' ) ) ) ); ?></p>

			<label class="screen-reader-text" for="support-topic-tpl"><?php esc_html_e( 'New Topic Template', 'buddy-bbpress-support-topic' ); ?></label>
			<textarea rows="3" cols="40" name="_bpbbpst_support_topic[tpl]" id="support-topic-tpl" style="width:100%"><?php echo bpbbpst_get_forum_support_topic_template( $forum->ID );?></textarea>
			<p class="description"><?php esc_html_e( 'The text added within this field will be used as a template for the content of new topics.', 'buddy-bbpress-support-topic' ); ?></p>
		</div>
		<?php

		do_action_ref_array( 'bpbbpst_forum_support_options_after_guides', array( $forum->ID, $support_only_style ) );
	}

	/**
	 * Saves the forum metabox datas
	 *
	 * @since  2.0
	 *
	 * @param  integer $forum_id the forum id
	 * @uses   bbp_is_forum_category() to check if forum is a category
	 * @uses   update_post_meta() to save the forum support setting
	 * @uses   delete_post_meta() to eventually delete a setting if needed
	 * @uses   do_action() call 'bpbbpst_forum_settings_updated' to save your custom forum support settings
	 * @return integer           the forum id
	 */
	public function forum_meta_box_save( $forum_id = 0 ) {
		$support_feature   = false;
		$is_forum_category =  bbp_is_forum_category( $forum_id );

		if ( ! empty( $_POST['_bpbbpst_forum_settings'] ) ) {
			$support_feature = absint( $_POST['_bpbbpst_forum_settings'] );
		}

		// Forum is not a category, save the support metas
		if ( ! empty( $support_feature ) && ! $is_forum_category ) {
			update_post_meta( $forum_id, '_bpbbpst_forum_settings', $support_feature );

			if ( 3 === (int) $support_feature ) {
				delete_post_meta( $forum_id, '_bpbbpst_support_recipients' );
				delete_post_meta( $forum_id, '_bpbbpst_support_topic_intro' );
				delete_post_meta( $forum_id, '_bpbbpst_support_topic_tpl' );
			} else {
				$recipients = ! empty( $_POST['_bpbbpst_support_recipients'] ) ? array_map( 'intval', $_POST['_bpbbpst_support_recipients'] ) : false ;

				if ( ! empty( $recipients ) && is_array( $recipients ) && count( $recipients ) > 0 ) {
					update_post_meta( $forum_id, '_bpbbpst_support_recipients', $recipients );
				} else {
					delete_post_meta( $forum_id, '_bpbbpst_support_recipients' );
				}

				if ( 2 === (int) $support_feature ) {
					if ( ! empty( $_POST['_bpbbpst_support_topic']['intro'] ) ) {
						update_post_meta( $forum_id, '_bpbbpst_support_topic_intro', wp_unslash( $_POST['_bpbbpst_support_topic']['intro'] ) );
					} else {
						delete_post_meta( $forum_id, '_bpbbpst_support_topic_intro' );
					}

					if ( ! empty( $_POST['_bpbbpst_support_topic']['tpl'] ) ) {
						update_post_meta( $forum_id, '_bpbbpst_support_topic_tpl', wp_unslash( $_POST['_bpbbpst_support_topic']['tpl'] ) );
					} else {
						delete_post_meta( $forum_id, '_bpbbpst_support_topic_tpl' );
					}
				} else if ( ! empty( $_POST['_bpbbpst_support_topic'] ) ) {
					delete_post_meta( $forum_id, '_bpbbpst_support_topic_intro' );
					delete_post_meta( $forum_id, '_bpbbpst_support_topic_tpl' );
				}
			}

			do_action( 'bpbbpst_forum_settings_updated', $forum_id, $support_feature );

		// Check for support metas to eventually remove them
		} else if ( $is_forum_category ) {
			$support_feature = get_post_meta( $forum_id, '_bpbbpst_forum_settings', true );

			if ( ! empty( $support_feature ) ) {
				delete_post_meta( $forum_id, '_bpbbpst_forum_settings' );
				delete_post_meta( $forum_id, '_bpbbpst_support_recipients' );
				delete_post_meta( $forum_id, '_bpbbpst_support_topic_intro' );
				delete_post_meta( $forum_id, '_bpbbpst_support_topic_tpl' );
			}
		}

		return $forum_id;
	}

	/**
	 * Adds a js to WordPress scripts queue
	 *
	 * @since 2.0
	 *
	 * @uses  get_current_screen() to be sure we are in forum post screen
	 * @uses  bbp_get_forum_post_type() to get the forum post type
	 * @uses  wp_enqueue_script() to add the js to WordPress queue
	 * @uses  bpbbpst_get_plugin_url() to build the path to plugin's js folder
	 * @uses  bpbbpst_get_plugin_version() to get plugin's version
	 */
	public function enqueue_forum_js() {

		if ( ! isset( get_current_screen()->post_type ) || ( bbp_get_forum_post_type() != get_current_screen()->post_type ) ) {
			return;
		}

		wp_enqueue_script( 'bpbbpst-forum-js', bpbbpst_get_plugin_url( 'js' ) . 'bpbbpst-forum.js', array( 'jquery' ), bpbbpst_get_plugin_version(), true );
	}

	/**
	 * Hooks pre_get_users to build a cutom meta_query to list forum moderators
	 *
	 * First checks for who arguments to be sure we're requesting forum moderators
	 *
	 * @since  2.0
	 *
	 * @global object $wpdb (the database class)
	 * @global integer the current blog id
	 * @param  object $query the user query arguments
	 * @uses   bbp_get_keymaster_role() to get keymaster role
	 * @uses   bbp_get_moderator_role() to get moderator role
	 */
	public function filter_user_query( $query = false ) {
		global $wpdb;

		if ( empty( $query->query_vars['who'] ) || 'bpbbpst_moderators' != $query->query_vars['who'] ) {
			return;
		}

		$unset = array_fill_keys( array(
			'who',
			'blog_id',      // to avoid the extra meta query in multisite
			'meta_key',     // to make sure no primary meta query is set
			'meta_value',   // to make sure no primary meta query is set
			'meta_compare'  // to make sure no primary meta query is set
		), false );

		// Unset the query vars before adding our meta query one
		$query->query_vars = array_diff_key( $query->query_vars, $unset );

		// Set current blog
		$blog_id = get_current_blog_id();

		// Set meta key
		$meta_key = $wpdb->get_blog_prefix( $blog_id ) . 'capabilities';

		// Set meta query
		$query->query_vars['meta_query'] = array(
			'relation' => 'OR',
			array(
				'key' => $meta_key,
				'value' => bbp_get_keymaster_role(),
				'compare' => 'LIKE'
			),
			array(
				'key' => $meta_key,
				'value' => bbp_get_moderator_role(),
				'compare' => 'LIKE'
			),
		);
	}

	/**
	 * Adds a selectbox to update the support status to topic attributes metabox
	 *
	 * @since 2.0
	 *
	 * @param integer $topic_id the topic id
	 * @uses  bbp_get_topic_forum_id() to get the parent forum id
	 * @uses  bpbbpst_get_forum_support_setting() to get the support setting of the parent forum
	 * @uses  get_post_meta() to get the previuosly stored topic support status
	 * @uses  bpbbpst_get_selectbox() to build the support status selectbox
	 */
	public function topic_meta_box( $topic_id = 0 ) {
		// Since 2.0, we first need to check parent forum has support for support :)
		$forum_id = bbp_get_topic_forum_id( $topic_id );

		if ( empty( $forum_id ) ) {
			wp_nonce_field( 'bpbbpst_support_define', '_wpnonce_bpbbpst_support_define' );
			return false;
		}

		if ( 3 == bpbbpst_get_forum_support_setting( $forum_id ) ) {
			return false;
		}

		$support_status = get_post_meta( $topic_id, '_bpbbpst_support_topic', true );

		if ( empty( $support_status ) ) {
			$support_status = 0;
		}
		?>
		<p>
			<strong class="label"><?php _e( 'Support:', 'buddy-bbpress-support-topic' ); ?></strong>
			<label class="screen-reader-text" for="parent_id"><?php _e( 'Support', 'buddy-bbpress-support-topic' ); ?></label>
			<?php echo bpbbpst_get_selectbox( $support_status, $topic_id);?>
		</p>
		<?php
	}

	/**
	 * Saves support status for the topic (admin)
	 *
	 * @since  2.0
	 *
	 * @param  integer $topic_id the topic id
	 * @param  integer $forum_id the parent forum id
	 * @uses   wp_verify_nonce() for security reason
	 * @uses   delete_post_meta() to eventually delete the support status
	 * @uses   update_post_meta() to save the support status
	 * @uses   do_action() call 'bpbbpst_topic_meta_box_save' to perform custom actions for the topic support
	 * @return integer           the topic id
	 */
	public function topic_meta_box_save( $topic_id = 0, $forum_id = 0 ) {

		if ( ! isset( $_POST['_support_status'] ) || $_POST['_support_status'] === false ) {
			return $topic_id;
		}

		$new_status = intval( $_POST['_support_status'] );

		if ( $new_status !== false && ! empty( $_POST['_wpnonce_bpbbpst_support_status'] ) && wp_verify_nonce( $_POST['_wpnonce_bpbbpst_support_status'], 'bpbbpst_support_status') ) {

			if ( empty( $new_status ) ) {
				delete_post_meta( $topic_id, '_bpbbpst_support_topic' );
			} else {
				update_post_meta( $topic_id, '_bpbbpst_support_topic', $new_status );
			}

			do_action( 'bpbbpst_topic_meta_box_save', $new_status );

		}

		return $topic_id;
	}

	/**
	 * Handles the support status in case a topic moved to another forum (admin)
	 *
	 * In case a topic moves to another forum, we need to check the new parent forum
	 * support setting to eventually delete the support status or create it.
	 *
	 * @since 2.0
	 *
	 * @param integer $topic_id the topic id
	 * @param object $topic     the topic object
	 * @uses  get_current_screen() to make sure we're editing a topic from admin
	 * @uses  bbp_get_topic_post_type() to get topic post type
	 * @uses  bbp_is_post_request() to make sure we're playing with a post request
	 * @uses  wp_verify_nonce() for security reasons
	 * @uses  current_user_can() to check for current user's capability
	 * @uses  bpbbpst_handle_moving_topic() to handle topic move
	 */
	public function topic_moved( $topic_id = 0, $topic = false ) {
		if ( ! isset( get_current_screen()->post_type ) || ( bbp_get_topic_post_type() != get_current_screen()->post_type ) ) {
			return $topic_id;
		}

		// Bail if doing an autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $topic_id;
		}

		// Bail if not a post request
		if ( ! bbp_is_post_request() ) {
			return $topic_id;
		}

		// Nonce check
		if ( empty( $_POST['bbp_topic_metabox'] ) || ! wp_verify_nonce( $_POST['bbp_topic_metabox'], 'bbp_topic_metabox_save' ) ) {
			return $topic_id;
		}

		// Bail if current user cannot edit this topic
		if ( !current_user_can( 'edit_topic', $topic_id ) ) {
			return $topic_id;
		}

		// Get the forum ID
		$forum_id = ! empty( $_POST['parent_id'] ) ? (int) $_POST['parent_id'] : 0;

		if ( empty( $forum_id ) ) {
			return $topic_id;
		}

		if ( $the_topic = wp_is_post_revision( $topic_id ) ) {
			$topic_id = $the_topic;
		}

		bpbbpst_handle_moving_topic( $topic_id, $forum_id );
	}

	/**
	 * Registers a new column to topics admin list to show support status
	 *
	 * @since  2.0
	 *
	 * @param  array  $columns the registered columns
	 * @return array           the columns with the support one
	 */
	public function topics_admin_column( $columns = array() ) {
		$columns['buddy_bbp_st_support'] = __( 'Support', 'buddy-bbpress-support-topic' );

		return $columns;
	}

	/**
	 * Displays the support status of each topic row
	 *
	 * @since 2.0
	 *
	 * @param string  $column   the column id
	 * @param integer $topic_id the topic id
	 * @uses  bpbbpst_add_support_mention() to output the topic support status
	 */
	public function topics_column_data( $column = '', $topic_id = 0 ) {
		if ( $column == 'buddy_bbp_st_support' && ! empty( $topic_id ) ) {
			bpbbpst_add_support_mention( $topic_id );
		}
	}

	/**
	 * Adds a selectbox to allow filtering topics by status (admin)
	 *
	 * @since 2.0
	 *
	 * @uses  get_current_screen() to be sure we're on topic admin list
	 * @uses  bbp_get_topic_post_type() to get topic post type
	 * @uses  bpbbpst_get_selectbox() to output the support status selectbox
	 */
	public function topics_admin_support_filter() {
		if ( get_current_screen()->post_type == bbp_get_topic_post_type() ) {

			$selected = empty( $_GET['_support_status'] ) ? -1 : intval( $_GET['_support_status'] );
			//displays the selectbox to filter by support status
			echo bpbbpst_get_selectbox( $selected , 'adminlist' );
		}
	}

	/**
	 * Filters bbPress query to include a support status meta query
	 *
	 * @since  2.0
	 *
	 * @param  array  $query_vars the bbPress query vars
	 * @uses   is_admin() to check we're in WordPress backend
	 * @return array the query vars with a support meta query
	 */
	public function topics_admin_support_request( $query_vars = array() ) {
		if ( ! is_admin() ) {
			return $query_vars;
		}

		if ( empty( $_GET['_support_status']  ) ) {
			return $query_vars;
		}

		$support_status = intval( $_GET['_support_status'] );

		if ( ! empty( $query_vars['meta_key'] ) ) {

			if ( $support_status == -1 ) {
				return $query_vars;
			}

			unset( $query_vars['meta_value'], $query_vars['meta_key'] );

			$query_vars['meta_query'] = array(
				array(
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

			if ( $support_status == -1 ) {
				return $query_vars;
			}

			$query_vars['meta_key']   = '_bpbbpst_support_topic';
			$query_vars['meta_value'] = $support_status;

		}

		return $query_vars;
	}

	/**
	 * Adds an inline edit part to topics row to allow support bulk edit
	 *
	 * @since  2.0
	 *
	 * @param  string $column_name the colum name id
	 * @param  string $post_type   the post type id
	 * @uses   bpbbpst_get_support_status() to get available support statuses
	 * @return string              html output
	 */
	public function bulk_topics_support( $column_name = '', $post_type = '' ) {
		if ( $column_name != 'buddy_bbp_st_support' ) {
			return;
		}

		$all_status = bpbbpst_get_support_status();

		if ( empty( $all_status ) || ! is_array( $all_status ) ) {
			return;
		}
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<div class="inline-edit-group">
					<label class="alignleft">
						<span class="title"><?php _e( 'Support' ); ?></span>
						<select name="_support_status">
							<?php foreach ( $all_status as $status ) : ?>
								<option value="<?php echo $status['value'];?>"><?php echo $status['sb-caption']; ?></option>
							<?php endforeach ; ?>
						</select>
					</label>
				</div>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Bulk update support statuses for selected topics
	 *
	 * @since  2.0
	 *
	 * @uses   wp_parse_id_list() to sanitize list of topic ids
	 * @uses   bbp_get_topic_forum_id() to get the forum parent id
	 * @uses   bpbbpst_get_forum_support_setting() to get forum parent support setting
	 * @uses   update_post_meta() to update the support statuses for selected topic ids
	 * @return boolean true
	 */
	public function bulk_update_support() {

		if ( ! isset( $_GET['bulk_edit'] ) ) {
			return;
		}

		if ( ! isset( $_GET['post_type'] ) || $_GET['post_type'] != bbp_get_topic_post_type() ) {
			return;
		}

		if ( ! isset( $_GET['_support_status'] ) ) {
			return;
		}

		if ( ! isset( $_GET['post'] ) ) {
			return;
		}

		$topic_ids = wp_parse_id_list( $_GET['post'] );

		$support_status = intval( $_GET['_support_status'] );

		foreach ( $topic_ids as $topic_id ) {
			// we need to check the topic belongs to a support featured forum
			$forum_id = bbp_get_topic_forum_id( $topic_id );

			if ( empty( $forum_id ) || ( 3 == bpbbpst_get_forum_support_setting( $forum_id ) && 0 != $support_status ) ) {
				continue;
			}

			if ( 2 == bpbbpst_get_forum_support_setting( $forum_id ) && 0 == $support_status ) {
				continue;
			}

			update_post_meta( $topic_id, '_bpbbpst_support_topic', $support_status );
		}

		return true;
	}

	/**
	 * Extends bbPress right now Dashboard widget to display support statistics
	 * bbPress Right now Dashboard widget seems to disappear in 2.6
	 *
	 * @since  2.0
	 *
	 * @uses   bpbbpst_support_statistics() to build the support statistics
	 * @uses   current_user_can() to check for current user's capability
	 * @uses   add_query_arg() to build links to topic admin list filtered by support status
	 * @uses   bbp_get_topic_post_type() to get the topic post type
	 * @uses   get_admin_url() to get the admin url
	 * @return string html output
	 */
	public function dashboard_widget() {
		$support_statistics = bpbbpst_support_statistics();

		if ( empty( $support_statistics['total_support'] ) ) {
			return false;
		}

		$status_stats = $support_statistics['allstatus'];

		if ( ! is_array( $status_stats ) || count( $status_stats ) < 1 ) {
			return false;
		}

		?>
		<div class="table table_content" style="margin-top:40px">
			<p class="sub"><?php _e( 'Support topics', 'bbpress' ); ?></p>
			<table>
				<tr class="first">

					<td class="first b b-topics"><span class="total-count"><?php echo $support_statistics['percent']; ?></span></td>
					<td class="t topics"><?php _e( 'Resolved so far', 'buddy-bbpress-support-topic' ); ?></td>

				</tr>

				<?php foreach ( $status_stats as $key => $stat ) : ?>

					<tr class="first">

					<?php
					$num  = $stat['stat'];
					$text = $stat['label'];
					$class = $stat['admin_class'];

					if ( current_user_can( 'publish_topics' ) ) {
						$link = add_query_arg( array( 'post_type' => bbp_get_topic_post_type(), '_support_status' => $key ), get_admin_url( null, 'edit.php' ) );
						$num  = '<a href="' . $link . '" class="' . $class . '">' . $num  . '</a>';
						$text = '<a href="' . $link . '" class="' . $class . '">' . $text . '</a>';
					}
					?>

					<td class="first b b-topic_tags"><?php echo $num; ?></td>
					<td class="t topic_tags"><?php  echo $text; ?></td>

				</tr>

				<?php endforeach ; ?>

			</table>

		</div>
		<?php
	}

	/**
	 * Register custom elements in the at a glance Dashboard Widget
	 * This is appearing in bbPress 2.6
	 *
	 * @since  2.0
	 *
	 * @param  array  $elements list of shortcut links
	 * @param  array  $stats    bbPress stats
	 * @return array           same elements with plugin's ones if needed
	 */
	public function dashboard_at_a_glance( $elements = array(), $stats = array() ) {
		if ( empty( $elements ) || empty( $stats['topic_count'] ) ) {
			return $elements;
		}

		$support_statistics = bpbbpst_support_statistics();

		if ( empty( $support_statistics['total_support'] ) ) {
			return $elements;
		}

		$status_stats = $support_statistics['allstatus'];

		if ( ! is_array( $status_stats ) || count( $status_stats ) < 1 ) {
			return $elements;
		}

		foreach ( (array) $status_stats as $key => $stat ) {
			$link       = add_query_arg( array( 'post_type' => bbp_get_topic_post_type(), '_support_status' => $key ), get_admin_url( null, 'edit.php' ) );
			$text       = sprintf( '%d %s', $stat['stat'], $stat['label'] );
			$class      = array( $stat['admin_class'] );

			if ( ! empty( $stat['dashicon']['class'] ) ) {
				$class[] = $stat['dashicon']['class'];
			}

			$elements[] = '<a href="' . esc_url( $link ) . '" class="' . join( ' ', $class ) . '">' . esc_html( $text ) . '</a>';
		}

		return $elements;
	}

	/**
	 * Check for welcome screen transient to eventually redirect admin to welcome screen
	 *
	 * @since 2.0
	 *
	 * @uses  get_transient() to get the transient created on plugin's activation
	 * @uses  delete_transient() to remove it
	 * @uses  is_network_admin() to check for network admin area
	 * @uses  wp_safe_redirect() to redirect admin to welcome screen
	 * @uses  add_query_arg() to build the link to the welcome screen
	 * @uses  admin_url() to get admin url
	 */
	public function maybe_activate() {

		if ( ! get_transient( '_bpbbst_welcome_screen' ) ) {
			return;
		}

		// Delete the redirect transient
		delete_transient( '_bpbbst_welcome_screen' );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		 wp_safe_redirect( add_query_arg( array( 'page' => 'bpbbst-about' ), admin_url( 'index.php' ) ) );
	}

	/**
	 * Adds a submenu to dashboard page to register the welcome screen
	 *
	 * @since  2.0
	 *
	 * @uses   add_dashboard_page() to build the dashboard submenu
	 * @uses   get_option() to get db version
	 * @uses   bpbbpst_get_plugin_version() to get plugin's version
	 * @uses   update_option() to eventually update db version
	 * @uses   do_action() call 'bpbbpst_upgrade' to perform custom actions once the plugin has been upgraded
	 */
	public function welcome_screen_register() {

		$this->about_page = add_dashboard_page(
			__( 'Welcome to Buddy-bbPress Support Topic',  'buddy-bbpress-support-topic' ),
			__( 'Welcome to Buddy-bbPress Support Topic',  'buddy-bbpress-support-topic' ),
			'manage_options',
			'bpbbst-about',
			array( &$this, 'welcome_screen_display' )
		);

		// do not upgrade version if required is not ok
		if ( ! bpbbpst_is_bbp_required_version_ok() ) {
			return;
		}

		$db_version = get_option( 'bp-bbp-st-version' );
		$plugin_version = bpbbpst_get_plugin_version();

		if ( empty( $db_version ) || $plugin_version != $db_version ) {
			update_option( 'bp-bbp-st-version', $plugin_version );

			do_action( 'bpbbpst_upgrade', $plugin_version, $db_version );
		}
	}

	/**
	 * Displays the welcome screen of the plugin
	 *
	 * @since  2.0
	 *
	 * @uses   bpbbpst_get_plugin_version() to get plugin's version
	 * @uses   bpbbpst_get_plugin_url() to get plugin's url
	 * @uses   bpbbpst_bbp_required_version() to get bbPress required version
	 * @uses   esc_url() to sanitize urls
	 * @uses   admin_url() to build the admin url of welcome screen
	 * @uses   add_query_arg() to add arguments to the admin url
	 * @return string html
	 */
	public function welcome_screen_display() {
		$display_version = bpbbpst_get_plugin_version();
		$plugin_url = bpbbpst_get_plugin_url();
		$bbpress_required = sprintf( '<strong>bbPress %s</strong>', bpbbpst_bbp_required_version() );
		?>
		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Buddy-bbPress Support Topic %s', 'buddy-bbpress-support-topic' ), $display_version ); ?></h1>

			<?php if ( ! bpbbpst_is_bbp_required_version_ok() ) :?>
				<div class="about-text"><?php printf( esc_html__( 'Ouch! Buddy-bbPress Support Topic %s requires at least %s, see below why it might be a great idea to upgrade bbPress ;)', 'buddy-bbpress-support-topic' ), $display_version, $bbpress_required ); ?></div>
			<?php else : ?>
				<div class="about-text"><?php printf( esc_html__( 'Thank you for using the latest version of Buddy-bbPress Support Topic (%s). Support only forums got some nice improvements!', 'buddy-bbpress-support-topic' ), $display_version ); ?></div>
			<?php endif; ?>

			<div class="bpbbpst-badge">
				<div class="badge"></div>
				<div class="badge-text"><?php esc_html_e( 'Support', 'buddy-bbpress-support-topic' ); ?></div>
			</div>

			<h2 class="nav-tab-wrapper wp-clearfix">
				<a class="nav-tab nav-tab-active" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'bpbbst-about' ), 'index.php' ) ) ); ?>">
					<?php esc_html_e( 'What&#39;s New', 'buddy-bbpress-support-topic' ); ?>
				</a>
			</h2>

			<div class="headline-feature feature-section one-col">
				<h2><?php esc_html_e( 'Guidelines & support topic templates for your support only forums.', 'buddy-bbpress-support-topic' ); ?></h2>

				<div class="featured-image">
					<img src="https://cldup.com/0iNnnWZw5d.png" alt="<?php esc_attr_e( 'Guidelines and Support topic template', 'buddy-bbpress-support-topic' ); ?>">
				</div>

				<p><?php esc_html_e( 'Providing the right answer often requires to have all needed informations about an issue. Support only forums can now use some guidelines and/or a support topic template to help the user describe the issue the best way.', 'buddy-bbpress-support-topic' ); ?></p>
			</div>

			<div class="feature-section two-col">
				<div class="col">
					<h3><?php esc_html_e( 'Guidelines', 'buddy-bbpress-support-topic' ); ?></h3>

					<p><?php esc_html_e( 'They are displayed before the topic form to let the user know about the specific rules to observe for the support forum he will post his topic into.', 'buddy-bbpress-support-topic' ); ?></p>

					<h3><?php esc_html_e( 'Support topic templates', 'buddy-bbpress-support-topic' ); ?></h3>

					<p><?php esc_html_e( 'Just like guidelines, each forum can include a specific topic template to display a checklist or some specific advices into the content area of the topic form.', 'buddy-bbpress-support-topic' ); ?></p>
				</div>
				<div class="col">
					<div class="media-container">
						<img src="https://cldup.com/3SOgj-v0ny.png" alt="<?php esc_attr_e( 'Guidelines and Support topic template inside the topic form', 'buddy-bbpress-support-topic' ); ?>">
					</div>
				</div>
			</div>

			<div class="clear"></div>

			<hr>

			<div class="feature-section two-col">
				<div class="col">
					<div class="media-container">
						<img src="https://cldup.com/3t2XlLbz1b.png" alt="<?php esc_attr_e( 'reply checkbox', 'buddy-bbpress-support-topic' ); ?>">
					</div>
				</div>
				<div class="col">
					<h3><?php esc_html_e( 'Mark this topic as resolved!', 'buddy-bbpress-support-topic' ); ?></h3>

					<p><?php esc_html_e( 'The reply to the topic you are about to post is fixing the issue? Great job, you can now directly mark the support topic as resolved by activating the corresponding checkbox.', 'buddy-bbpress-support-topic' ); ?></p>
				</div>
			</div>

			<div class="clear"></div>

			<hr>

			<h3 class="wp-people-group"><?php printf( esc_html__( 'Many thanks to %s contributors', 'buddy-bbpress-support-topic' ), $display_version ); ?></h3>

			<p class="wp-credits-list">
				<a href="https://profiles.wordpress.org/danieliser">danieliser</a>,
				<a href="https://profiles.wordpress.org/imath">imath</a>,
				<a href="https://profiles.wordpress.org/g3ronim0/">G3ronim0</a>.
			</p>
		<?php
	}

	/**
	 * Outputs some css rules if on welcome screen or on dashboard (at a glance widget)
	 *
	 * @since  2.0
	 *
	 * @uses   remove_submenu_page() to remove the page from dashoboard menu
	 * @uses   bpbbpst_get_plugin_url() to get the plugin url
	 * @uses   get_current_screen() to check current page is the welcome screen
	 * @uses   bpbbpst_get_support_status() to get all available support status
	 * @uses   sanitize_html_class() to sanitize the clases
	 * @uses   sanitize_text_field() to sanitize the content
	 * @return string css rules
	 */
	public function welcome_screen_css() {
		// Avoid a notice in network administration
		if ( is_network_admin() ) {
			return;
		}

		remove_submenu_page( 'index.php', 'bpbbst-about');

		if (  $this->about_page == get_current_screen()->id ) {
			?>
			<style type="text/css" media="screen">
				/*<![CDATA[*/

				.about-wrap .bpbbpst-badge .badge {
					font: normal 150px/1 'dashicons' !important;
					/* Better Font Rendering =========== */
					-webkit-font-smoothing: antialiased;
					-moz-osx-font-smoothing: grayscale;

					color: #333;
					display: inline-block;
					content:'';
				}

				.about-wrap .bpbbpst-badge .badge:before{
					content: "\f488";
				}

				.bpbbpst-badge {
					background-color: #FFF;
					width: 150px;
					-webkit-box-shadow: 0 1px 3px rgba(0,0,0,.2);
					box-shadow: 0 1px 3px rgba(0,0,0,.2);
				}

				.bpbbpst-badge .badge-text {
					color:#333;
					font-size: 14px;
					text-align: center;
					font-weight: 600;
					margin: 5px 0 0;
					height: 30px;
					text-rendering: optimizeLegibility;
				}

				.about-wrap .bpbbpst-badge {
					position: absolute;
					top: 0;
					right: 0;
				}
					body.rtl .about-wrap .bpbbpst-badge {
						right: auto;
						left: 0;
					}

				/*]]>*/
			</style>
			<?php
		} else if ( 'dashboard' == get_current_screen()->id ) {
			?>
			<style type="text/css" media="screen">
				/*<![CDATA[*/

				<?php foreach ( (array) bpbbpst_get_support_status() as $status ) : ?>

					<?php if ( empty( $status['dashicon']['class'] ) || empty( $status['dashicon']['content'] ) ) : continue ; endif ;?>

					#dashboard_right_now a.<?php echo sanitize_html_class( $status['dashicon']['class'] ) ;?>:before {
						content: <?php echo sanitize_text_field( $status['dashicon']['content'] ) ;?>;
					}

				<?php endforeach ;?>

				/*]]>*/
			</style>
			<?php
		}
	}

	/**
	 * Adds a custom link to the welcome screen in plugin's list for our row
	 *
	 * @since  2.0
	 *
	 * @param  array  $links the plugin links
	 * @param  string $file  the plugin in row
	 * @uses   plugin_basename() to get plugin's basename
	 * @uses   bbpress() to get bbPress main instance
	 * @uses   add_query_arg() to build the url to our welcome screen
	 * @uses   admin_url() to get admin url to dashoboard
	 * @uses   esc_html__() to sanitize translated string
	 * @return array         the plugin links
	 */
	public function modify_plugin_action_links( $links = array(), $file = '' ) {
		// Do not touch to links in network admin
		if ( is_network_admin() ) {
			return $links;
		}

		// Return normal links if not BuddyPress
		if ( plugin_basename( bbpress()->extend->bpbbpst->globals->file ) != $file ) {
			return $links;
		}

		// Add a few links to the existing links array
		return array_merge( $links, array(
			'about'    => '<a href="' . add_query_arg( array( 'page' => 'bpbbst-about' ), admin_url( 'index.php' ) ) . '">' . esc_html__( 'About', 'buddy-bbpress-support-topic' ) . '</a>'
		) );

	}

}

endif; // class_exists check

/**
 * Setup Buddy-bbPress Support Topic Admin
 *
 * @since 2.0
 *
 * @uses  bbpress() to get main bbPress instance
 * @uses  BP_bbP_ST_Admin() to start the admin part of the plugin
 */
function bpbbpst_admin() {
	bbpress()->extend->bpbbpst->admin = new BP_bbP_ST_Admin();
}
