<?php

/**
 * Buddy-bbPress Support Topic widgets
 *
 * @package    Buddy-bbPress Support Topic
 * @subpackage Widgets
 *
 * @since      2.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Buddy-bbPress Support Topic statistic Widget
 *
 * Adds a widget which displays the support topic statistics
 *
 * @since  1.1
 * 
 * @uses   WP_Widget
 */
class Bpbbpst_Support_Stats extends WP_Widget {
	
	/**
	 * Buddy-bbPress Support Topic statistic Widget
	 *
	 * @since  1.1
	 *
	 * @uses   WP_Widget::__construct() to init the widget
	 * @uses   add_filter() to filter bbPress topic query, breadcrumb and pagination
	 * @uses   add_action() to enqueue widget style
	 */
	function __construct() {

		$widget_ops = array( 'description' => __( 'Displays support topic global statistics or of the active forum', 'buddy-bbpress-support-topic' ) );
		parent::__construct( false, $name = __( 'Support Topics Stats', 'buddy-bbpress-support-topic' ), $widget_ops );

		/* bbPress filters to handle breadcrumb, pagination and topics query */
		add_filter( 'bbp_after_has_topics_parse_args', array( $this, 'filter_topics_query_by_status' ), 20, 1 );
		add_filter( 'bbp_get_breadcrumb',              array( $this, 'breadcrumb_for_status' ),         20, 3 );
		add_filter( 'bbp_topic_pagination',            array( $this, 'pagination_for_status' ),         20, 1 );

		/* finally add some style */
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_style' ), 10 );
	}


	/**
	 * Registers the widget
	 *
	 * @since  1.1
	 * 
	 * @uses   register_widget() to register the widget
	 */
	function register_widget() {
		register_widget( 'Bpbbpst_Support_Stats' );
	}
	

	/**
	 * Displays the output, the statistics
	 * 
	 * Depending if the widget is displayed on a single forum template
	 * it displays the current forum stats or the global stats.
	 * If on a single forum, add links to filter by support status the topics
	 *
	 * @since  2.0
	 *
	 * @param  mixed $args Arguments
	 * @param  array $instance Instance
	 * @uses   bbp_get_forum_id() to get the forum id
	 * @uses   bbp_is_topic_edit() to check if the topic is being edited
	 * @uses   bbp_get_topic_forum_id() to get the forum parent of the edited topic
	 * @uses   bbp_get_topic_id() to get the edited topic id
	 * @uses   bpbbpst_support_statistics() To get the statistics according to its parameter
	 * @uses   add_query_arg() to add the querystring to filter the support topics
	 * @uses   bbp_get_forum_permalink() to get the forum permalink
	 * @uses   esc_attr() to sanitize attributes
	 * @uses   bbp_forums_url() to display the link to forums archive
	 * @return string html output
	 */
	function widget( $args, $instance ) {


		extract( $args );

	    if ( !$instance['bpbbpst_title'] )
	    	$instance['bpbbpst_title'] = __( 'Support Topics Stats', 'buddy-bbpress-support-topic' );
	
		if ( !$instance['show_forum_link'] )
	    	$instance['show_forum_link'] = false;

	    $forum_id = bbp_get_forum_id();
	    $stats_params = "";

	    if( empty( $forum_id ) && bbp_is_topic_edit() )
	    	$forum_id = bbp_get_topic_forum_id( bbp_get_topic_id() );

	    if( !empty( $forum_id ) ) {

	    	$stats_params = array( 
			'meta_query'     => array( array( 
											'key'     => '_bpbbpst_support_topic',
											'value'   => 1,
											'type'    => 'numeric',
											'compare' => '>=' 
										),
										array( 
											'key'     => '_bbp_forum_id',
											'value'   =>  $forum_id,
											'compare' => '='
										)
								)
				);

	    }	    

		$support_statistics = bpbbpst_support_statistics( $stats_params );

	    if ( !empty( $support_statistics['total_support'] ) ){

	    	$status_stats = $support_statistics['allstatus'];

			if( !is_array( $status_stats ) || count( $status_stats ) < 1 )
				return false;

	    	$num_percent  = $support_statistics['percent'];
			$text_percent = __( 'Resolved so far', 'buddy-bbpress-support-topic' );

	    	echo $before_widget;
			echo $before_title . $instance['bpbbpst_title'] . $after_title; ?>

			<ul class="bpbbpst-widget">
				<li class="bpbbpst-percent">
					<span class="bpbbpst-text"><?php echo $text_percent;?></span> <span class="bpbbpst-num"><?php echo $num_percent;?></span>
				</li>

				<?php foreach( $status_stats as $key => $stat ):
					
					$num  = $stat['stat'];
					$text = $stat['label'];
					$class = $stat['front_class'];

					if( !empty( $forum_id ) ) {
						$link = add_query_arg( array( 'support_status' => $key ), bbp_get_forum_permalink() );
						$num  = '<a href="'. $link .'" title="'. esc_attr( $text ) .'">'. $num .'</a>' ;
						$text = '<a href="'. $link .'" title="'. esc_attr( $text ) .'">'. $text .'</a>' ;
					}

				?>

					<li class="bpbbpst-<?php echo $class;?> status">
						<span class="bpbbpst-num"><?php echo $num;?></span> <span class="bpbbpst-text"><?php echo $text;?></span>
					</li>

				<?php endforeach;?>
				
			</ul>

			<?php if( !is_bbpress() && !empty( $instance['show_forum_link'] ) ) : ?>
				<p><a href="<?php bbp_forums_url();?>" title="<?php _e( 'Go to forums' , 'buddy-bbpress-support-topic' );?>"><?php _e( 'Go to forums' , 'buddy-bbpress-support-topic' );?></a></p>
			<?php endif ;?>
			
			<?php echo $after_widget;
	    }

	}


	/**
	 * Update the statistics widget options (title)
	 *
	 * @since  1.1
	 *
	 * @param  array $new_instance The new instance options
	 * @param  array $old_instance The old instance options
	 * @return array the instance
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		$instance['bpbbpst_title'] = strip_tags( $new_instance['bpbbpst_title'] );
		
		if( !empty( $new_instance['show_forum_link'] ) )	
			$instance['show_forum_link'] = intval( $new_instance['show_forum_link'] );
			
		else
			$instance['show_forum_link'] = 0;
		
		return $instance;
	}


	/**
	 * Output the statistics widget options form
	 *
	 * @since 1.1
	 *
	 * @param  $instance Instance
	 * @uses   wp_parse_args() to merge instance defined arguments into defaults array
	 * @uses   Bpbbpst_Support_Stats::get_field_id() To output the field id
	 * @uses   Bpbbpst_Support_Stats::get_field_name() To output the field name
	 * @uses   checked() to activate the checkbox if it needs to
	 * @return string html output
	 */
	function form( $instance ) {
		$defaults = array(
	                     'bpbbpst_title'   => __( 'Support Topics Stats', 'buddy-bbpress-support-topic' ),
						 'show_forum_link' => 0
	                );

	    $instance = wp_parse_args( (array) $instance, $defaults );

	    $bpbbpst_title = strip_tags( $instance['bpbbpst_title'] );
		$show_forum_link = strip_tags( $instance['show_forum_link'] );

	    ?>

	    <p>
	    	<label for="bpbbpst_title"><?php _e( 'Title:', 'buddy-bbpress-support-topic' ); ?>
	    		<input class="widefat" id="<?php echo $this->get_field_id( 'bpbbpst_title' ); ?>" name="<?php echo $this->get_field_name( 'bpbbpst_title' ); ?>" type="text" value="<?php echo esc_attr( $bpbbpst_title ); ?>" style="width: 100%" />
	    	</label>
		</p>
			<label for="show_forum_link">
	    		<input id="<?php echo $this->get_field_id( 'show_forum_link' ); ?>" name="<?php echo $this->get_field_name( 'show_forum_link' ); ?>" type="checkbox" value="1" <?php checked( true, $show_forum_link );?> /> <?php _e( 'Show forum link', 'buddy-bbpress-support-topic' ); ?>
	    	</label>
			<p class="description"><?php _e( 'When not on a forum page, display a link to it', 'buddy-bbpress-support-topic' )?></p>
	    <?php
	}


	/**
	 * Adds a meta_query to the topics query
	 *
	 * @since  1.1
	 *
	 * @param  mixed $args Arguments
	 * @uses   bbp_is_single_forum() to check it's a single forum page
	 * @return mixed $args the new arguments eventually including the meta_query
	 */
	function filter_topics_query_by_status( $args = "" ) {
		
		if( !bbp_is_single_forum() )
			return $args;
	
		if( empty( $_GET['support_status'] ) )
			return $args;

		/* if meta_query exists, we dont want to break anything */
		if( !empty( $args['meta_query'] ) )
			return $args;

		$args['meta_query'] = array( array( 
											'key'     => '_bpbbpst_support_topic',
											'value'   => intval( $_GET['support_status'] ),
											'compare' => '=' 
										) );

		$args['show_stickies'] = false;

		return $args;
	}


	/**
	 * Adds the support status at the end of the breadcrumb
	 *
	 * @since  2.0
	 *
	 * @param  string $trail the breadcrumb html
	 * @param  array $crumbs the different element to show in the breadcrumb
	 * @param  array $args arguments to organize the display of the breadcrumb
	 * @uses   bbp_is_single_forum() to check it's a single forum page
	 * @uses   bpbbpst_get_selected_support_status() to get the caption for the filtered status
	 * @uses   bbp_get_forum_permalink() to get the forum's permalink
	 * @uses   esc_html() to sanitize the status
	 * @return string $trail the new breadcrumb eventually including the support status
	 */
	function breadcrumb_for_status( $trail, $crumbs, $args ) {
		
		if( !bbp_is_single_forum() )
			return $trail;
	
		if( empty( $_GET['support_status'] ) )
			return $trail;

		$last = $crumbs[ count($crumbs) -1 ];

		$sep = $args['sep'];
		if ( ! empty( $sep ) )
			$sep = $args['sep_before'] . $sep . $args['sep_after'];


		$pad_sep = $args['pad_sep'];
		// Pad the separator
		if ( !empty( $pad_sep ) )
			$sep = str_pad( $sep, strlen( $sep ) + ( (int) $pad_sep * 2 ), ' ', STR_PAD_BOTH );
	
		$support_status = bpbbpst_get_selected_support_status(  $_GET['support_status'] );

		$trail = str_replace( $last, '<a href="'. bbp_get_forum_permalink() .'" >'. $last .'</a>' . $sep . esc_html( $support_status['sb-caption'] ), $trail );

		return $trail;

	}


	/**
	 * Adds the support status at the end of the querystring
	 *
	 * @since  1.1
	 *
	 * @param  array pagination pagination settings
	 * @uses   bbp_is_single_forum() to check it's a single forum page
	 * @return array pagination the new pagination settings including the support status
	 */
	function pagination_for_status( $pagination = array() ) {
	
		if( !bbp_is_single_forum() )
			return $pagination;
	
		if( empty( $_GET['support_status'] ) )
			return $pagination;

		$pagination['base'] .= '?support_status=' .intval( $_GET['support_status'] )  ;

		return $pagination;
	}


	/**
	 * Enqueue some style for the widget
	 *
	 * @since  2.0
	 * 
	 * @uses   wp_enqueue_style() to load the css file
	 * @uses   bpbbpst_get_plugin_url() to get plugin's url
	 * @uses   bpbbpst_get_plugin_version() to get plugin's version
	 */
	function enqueue_style() {
		wp_enqueue_style( 'bpbbpst-bbpress-widget-css', bpbbpst_get_plugin_url( 'css' ). 'bpbbpst-bbpress-widget.css', false, bpbbpst_get_plugin_version() );
	}

}

add_action( 'bbp_widgets_init', array( 'Bpbbpst_Support_Stats', 'register_widget' ), 10 );


/**
 * Buddy-bbPress Support Topic new Support Topic widget
 *
 * Adds a widget to add a link to directly add a new topic to 
 * a support only forum
 *
 * @since  2.0
 * 
 * @uses   WP_Widget
 */
class Bpbbpst_Support_New_Support extends WP_Widget {
	
	/**
	 * Buddy-bbPress Support Topic new support toppic widget
	 *
	 * @since  2.0
	 *
	 * @uses   WP_Widget::__construct() to init the widget
	 * @uses   add_filter() to filter bbPress topic query, breadcrumb and pagination
	 * @uses   add_action() to enqueue widget style
	 */
	function __construct() {

		$widget_ops = array( 'description' => __( 'Displays a short message to invite users to add a new support topic on support only forums', 'buddy-bbpress-support-topic' ) );
		$control_ops = array('width' => 400, 'height' => 200);
		parent::__construct( false, $name = __( 'New Support Topic', 'buddy-bbpress-support-topic' ), $widget_ops, $control_ops );

	}


	/**
	 * Register the widget
	 *
	 * @since  2.0
	 *
	 * @uses   register_widget() to register the widget
	 */
	function register_widget() {
		register_widget( 'Bpbbpst_Support_New_Support' );
	}
	

	/**
	 * Displays the output, the button to post new support topics
	 *
	 * @since  2.0
	 *
	 * @param  mixed $args Arguments
	 * @param  array $instance Instance
	 * @uses   apply_filters() to let plugins or themes modify the value
	 * @uses   do_action() to let plugins or themes run some actions from this point
	 * @uses   esc_html() to sanitize the button caption
	 * @uses   bbp_get_forum_permalink() to build link to forum
	 * @uses   trailingslashit() to be sure a slash is finishing the url
	 * @uses   wpautop() to automatically add paragraphs to text
	 * @return string html output
	 */
	function widget( $args, $instance ) {

		extract( $args );

	    if ( empty( $instance['bpbbpst_title'] ) )
	    	$instance['bpbbpst_title'] = __( 'New Support topic', 'buddy-bbpress-support-topic' );

	    if ( empty( $instance['bpbbpst_button'] ) )
	    	$instance['bpbbpst_button'] = __( 'Ask for Support', 'buddy-bbpress-support-topic' );
	
		if ( empty( $instance['bpbbpst_forum_id'] ) )
	    	$instance['bpbbpst_forum_id'] = false;

	    $text = apply_filters( 'widget_text', empty( $instance['bpbbpst_text'] ) ? '' : $instance['bpbbpst_text'], $instance );

	    do_action( 'bpbbpst_new_topic_widget_before_content' );

	    if( empty( $instance['bpbbpst_forum_id'] ) )
	    	return false;

	    $widget_display = apply_filters( 'bpbbpst_new_topic_widget_display', true );

	    if( empty( $widget_display ) )
	    	return false;

	    $button_caption = esc_html( $instance['bpbbpst_button'] );
	    $forum_post_form_link = trailingslashit( bbp_get_forum_permalink( $instance['bpbbpst_forum_id'] ) );

	    if( !empty( $instance['bpbbpst_referer'] ) )
	    	$forum_post_form_link .= "?bpbbpst-referer=1";

	    $forum_post_form_link .= '#bbp_topic_title';

	     
	    $html_link = '<a href="'. $forum_post_form_link .'" title="'. $button_caption . '" class="button submit bpbbpst-btn">'. $button_caption. '</a>';

	    $a_link = apply_filters( 'bpbbpst_new_support_widget_button', $html_link, $forum_post_form_link, $button_caption );

	    echo $before_widget;
		echo $before_title . $instance['bpbbpst_title'] . $after_title; ?>

		<div class="textwidget">

			<?php echo !empty( $instance['bpbbpst_filter'] ) ? wpautop( $text ) : $text; ?>

		</div>

		<div class="bpbbpst-action">
			<?php echo $a_link;?>
		</div>
			
		<?php 
		do_action( 'bpbbpst_new_topic_widget_after_content' );
		echo $after_widget;

	}


	/**
	 * Update the new support topic widget options (title)
	 *
	 * @since  2.0
	 * 
	 * @param  array $new_instance The new instance options
	 * @param  array $old_instance The old instance options
	 * @uses   current_user_can() to check for current user's capabiility
	 * @uses   wp_filter_post_kses() to sanitize the text
	 * @return array the instance
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		$instance['bpbbpst_title'] = strip_tags( $new_instance['bpbbpst_title'] );
		$instance['bpbbpst_button'] = strip_tags( $new_instance['bpbbpst_button'] );

		if ( current_user_can('unfiltered_html') )
			$instance['bpbbpst_text'] =  $new_instance['bpbbpst_text'];
		else
			$instance['bpbbpst_text'] = stripslashes( wp_filter_post_kses( addslashes($new_instance['bpbbpst_text']) ) );
		
		$instance['bpbbpst_filter'] = isset( $new_instance['bpbbpst_filter'] );
		$instance['bpbbpst_forum_id'] = !empty( $new_instance['bpbbpst_forum_id'] ) ? intval( $new_instance['bpbbpst_forum_id'] ) : 0;
		$instance['bpbbpst_referer'] = isset( $new_instance['bpbbpst_referer'] );
		
		return $instance;
	}


	/**
	 * Output the new support topic widget options form
	 *
	 * @since  2.0
	 * @param  $instance Instance
	 * @uses   wp_parse_args() to merge instance defined arguments into defaults array
	 * @uses   esc_textarea() to sanitize the text
	 * @uses   esc_attr() to sanitize attibutes
	 * @uses   Bpbbpst_Support_New_Support::get_field_id() To output the field id
	 * @uses   Bpbbpst_Support_New_Support::get_field_name() To output the field name
	 * @uses   checked() to activate the checkbox if it needs to
	 * @uses   bpbbpst_get_support_only_forums() to get the selectbox of support only forums
	 */
	function form( $instance ) {
		$defaults = array(
	                     'bpbbpst_title'   => __( 'New Support topic', 'buddy-bbpress-support-topic' ),
						 'bpbbpst_button' => __( 'Ask for Support', 'buddy-bbpress-support-topic' ),
						 'bpbbpst_text' => '',
						 'bpbbpst_forum_id' => 0
	                );

	    $instance = wp_parse_args( (array) $instance, $defaults );

	    $bpbbpst_title = strip_tags( $instance['bpbbpst_title'] );
	    $bpbbpst_button = strip_tags( $instance['bpbbpst_button'] );
		$bpbbpst_text = esc_textarea( $instance['bpbbpst_text'] );
		$bpbbpst_forum_id = intval( $instance['bpbbpst_forum_id'] );
	    ?>

	    <p>
	    	<label for="bpbbpst_title"><?php _e( 'Title:', 'buddy-bbpress-support-topic' ); ?>
	    		<input class="widefat" id="<?php echo $this->get_field_id( 'bpbbpst_title' ); ?>" name="<?php echo $this->get_field_name( 'bpbbpst_title' ); ?>" type="text" value="<?php echo esc_attr( $bpbbpst_title ); ?>" style="width: 100%" />
	    	</label>
		</p>
		<p>
	    	<label for="bpbbpst_button"><?php _e( 'Button caption:', 'buddy-bbpress-support-topic' ); ?>
	    		<input class="widefat" id="<?php echo $this->get_field_id( 'bpbbpst_button' ); ?>" name="<?php echo $this->get_field_name( 'bpbbpst_button' ); ?>" type="text" value="<?php echo esc_attr( $bpbbpst_button ); ?>" style="width: 100%" />
	    	</label>
		</p>
		<textarea class="widefat" rows="8" cols="20" id="<?php echo $this->get_field_id('bpbbpst_text'); ?>" name="<?php echo $this->get_field_name('bpbbpst_text'); ?>"><?php echo $bpbbpst_text; ?></textarea>

		<p><input id="<?php echo $this->get_field_id('bpbbpst_filter'); ?>" name="<?php echo $this->get_field_name('bpbbpst_filter'); ?>" type="checkbox" <?php checked(isset($instance['bpbbpst_filter']) ? $instance['bpbbpst_filter'] : 0); ?> />&nbsp;<label for="<?php echo $this->get_field_id('bpbbpst_filter'); ?>"><?php _e( 'Automatically add paragraphs', 'buddy-bbpress-support-topic' ); ?></label></p>
		<p>
			<label for="<?php echo $this->get_field_id('bpbbpst_forum_id'); ?>"><?php _e('Select the support only forum:', 'buddy-bbpress-support-topic') ?></label>

			<?php bpbbpst_get_support_only_forums( $bpbbpst_forum_id, $this->get_field_id('bpbbpst_forum_id'), $this->get_field_name('bpbbpst_forum_id')  );?>
			
		</p>
		<p><input id="<?php echo $this->get_field_id('bpbbpst_referer'); ?>" name="<?php echo $this->get_field_name('bpbbpst_referer'); ?>" type="checkbox" <?php checked(isset($instance['bpbbpst_referer']) ? $instance['bpbbpst_referer'] : 0); ?> />&nbsp;<label for="<?php echo $this->get_field_id('bpbbpst_referer'); ?>"><?php _e( 'Automatically display a link to referer for moderators', 'buddy-bbpress-support-topic' ); ?></label></p>
	    <?php
	}

}

add_action( 'bbp_widgets_init', array( 'Bpbbpst_Support_New_Support', 'register_widget' ), 10 );
