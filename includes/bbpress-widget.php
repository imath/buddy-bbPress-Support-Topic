<?php

/**
 * Buddy-bbPress Support Topic bbPress widget
 *
 * @package    Buddy-bbPress Support Topic
 * @subpackage bbpress-widget
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Buddy-bbPress Support Topic statistic Widget
 *
 * Adds a widget which displays the support topic statistics
 *
 * @uses   WP_Widget
 * @author imath
 */
class Bpbbpst_Support_Stats extends WP_Widget {
	
	/**
	 * Buddy-bbPress Support Topic statistic Widget
	 *
	 * Registers the widget
	 *
	 * @uses   add_filter() to filter bbPress topic query, breadcrumb and pagination
	 * @uses   add_action() to enqueue widget style
	 * @author imath
	 */
	function __construct() {

		$widget_ops = array( 'description' => __( 'Displays support topic global statistics or of the active forum', 'buddy-bbpress-support-topic' ) );
		parent::__construct( false, $name = __( 'Support Topics Stats', 'buddy-bbpress-support-topic' ), $widget_ops );

		/* bbPress filters to handle breadcrumb, pagination and topics query */
		add_filter( 'bbp_after_has_topics_parse_args', array( $this, 'filter_topics_query_by_status' ), 20, 1 );
		add_filter( 'bbp_get_breadcrumb',              array( $this, 'breadcrumb_for_status' ), 20, 3 );
		add_filter( 'bbp_topic_pagination',            array( $this, 'pagination_for_status' ), 20, 1 );

		/* finally add some style */
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_style' ), 10 );
	}


	/**
	 * Register the widget
	 *
	 * @uses   register_widget()
	 * @author imath
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
	 * @param  mixed $args Arguments
	 * @param  array $instance Instance
	 * @uses   bbp_get_forum_id() to get the forum id
	 * @uses   bpbbpst_bbpress_support_statistics() To get the statistics according to its parameter
	 * @uses   add_query_arg() to add the querystring to filter the support topics
	 * @author imath
	 */
	function widget( $args, $instance ) {


		extract( $args );

	    if ( !$instance['bpbbpst_title'] )
	    	$instance['bpbbpst_title'] = __( 'Support Topics Stats', 'buddy-bbpress-support-topic' );
	
		if ( !$instance['show_forum_link'] )
	    	$instance['show_forum_link'] = false;

	    $forum_id = bbp_get_forum_id();
	    $stats_params = "";

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

		$support_statistics = bpbbpst_bbpress_support_statistics( $stats_params );


	    if ( !empty( $support_statistics ) ){

	    	$num_percent  = $support_statistics['percent'];
			$text_percent = __( 'Resolved so far', 'buddy-bbpress-support-topic' );

			$num_resolved  = $support_statistics['resolved'];
			$text_resolved = __('Resolved', 'buddy-bbpress-support-topic' );

			if( !empty( $forum_id ) ) {
				$link = add_query_arg( array( 'support_status' => 2 ), bbp_get_forum_permalink() );
				$num_resolved = '<a href="'. $link .'" title="'. $text_resolved .'">'. $num_resolved .'</a>' ;
				$text_resolved = '<a href="'. $link .'" title="'. $text_resolved .'">'. $text_resolved .'</a>' ;
			}

			$num_unsolved  = $support_statistics['unsolved'];
			$text_unsolved = __( 'To resolve', 'buddy-bbpress-support-topic' );

			if( !empty( $forum_id ) ) {
				$link = add_query_arg( array( 'support_status' => 1 ), bbp_get_forum_permalink() );
				$num_unsolved = '<a href="'. $link .'" title="'. $text_unsolved .'">'. $num_unsolved .'</a>' ;
				$text_unsolved = '<a href="'. $link .'" title="'. $text_unsolved .'">'. $text_unsolved .'</a>' ;
			}

	    	echo $before_widget;
			echo $before_title . $instance['bpbbpst_title'] . $after_title; ?>

			<ul class="bpbbpst-widget">
				<li class="bpbbpst-percent">
					<span class="bpbbpst-text"><?php echo $text_percent;?></span> <span class="bpbbpst-num"><?php echo $num_percent;?></span>
				</li>
				<li class="bpbbpst-resolved">
					<span class="bpbbpst-num"><?php echo $num_resolved;?></span> <span class="bpbbpst-text"><?php echo $text_resolved;?></span>
				</li>
				<li class="bpbbpst-unsolved">
					<span class="bpbbpst-num"><?php echo $num_unsolved;?></span> <span class="bpbbpst-text"><?php echo $text_unsolved;?></span>
				</li>
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
	 * @param  array $new_instance The new instance options
	 * @param  array $old_instance The old instance options
	 * @author imath
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['bpbbpst_title'] = strip_tags( $new_instance['bpbbpst_title'] );
		$instance['show_forum_link'] = intval( $new_instance['show_forum_link'] );
		
		return $instance;
	}


	/**
	 * Output the statistics widget options form
	 *
	 * @param  $instance Instance
	 * @uses   wp_parse_args() to merge instance defined arguments into defaults array
	 * @uses   Bpbbpst_Support_Stats::get_field_id() To output the field id
	 * @uses   Bpbbpst_Support_Stats::get_field_name() To output the field name
	 * @uses   checked() to activate the checkbox if it needs to
	 * @author imath
	 */
	function form( $instance ) {
		$defaults = array(
	                     'bpbbpst_title'   => __( 'Support Topics Stats', 'buddy-bbpress-support-topic' ),
						 'show_forum_link' => false
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
	 * @param  mixed $args Arguments
	 * @uses   bbp_is_single_forum() to check it's a single forum page
	 * @return mixed $args the new arguments eventually including the meta_query
	 * @author imath
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

		return $args;
	}


	/**
	 * Adds the support status at the end of the breadcrumb
	 *
	 * @param  string $trail the breadcrumb html
	 * @param  array $crumbs the different element to show in the breadcrumb
	 * @param  array $args arguments to organize the display of the breadcrumb
	 * @uses   bbp_is_single_forum() to check it's a single forum page
	 * @uses   bbp_get_forum_permalink() to get the forum's permalink
	 * @return string $trail the new breadcrumb eventually including the support status
	 * @author imath
	 */
	function breadcrumb_for_status( $trail, $crumbs, $args ) {
		
		if( !bbp_is_single_forum() )
			return $trail;
	
		if( empty( $_GET['support_status'] ) )
			return $trail;

		$last = $crumbs[ count($crumbs) -1 ];

		$sep = $args['sep'];
		if ( ! empty( $sep ) )
			$sep = $args['sep_before'] . $sep . $args['$sep_after'];


		$pad_sep = $args['pad_sep'];
		// Pad the separator
		if ( !empty( $pad_sep ) )
			$sep = str_pad( $sep, strlen( $sep ) + ( (int) $pad_sep * 2 ), ' ', STR_PAD_BOTH );
	
		$support_status = ( $_GET['support_status'] == 2 ) ? __('Resolved', 'buddy-bbpress-support-topic') : __('Not resolved', 'buddy-bbpress-support-topic') ;

		$trail = str_replace( $last, '<a href="'. bbp_get_forum_permalink() .'" >'. $last .'</a>' . $sep . $support_status, $trail );

		return $trail;

	}


	/**
	 * Adds the support status at the end of the breadcrumb
	 *
	 * @param  array pagination pagination settings
	 * @uses   bbp_is_single_forum() to check it's a single forum page
	 * @return array pagination the new pagination settings including the support status
	 * @author imath
	 */
	function pagination_for_status( $pagination ) {
	
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
	 * @uses   wp_enqueue_style() to load the css file
	 * @author imath
	 */
	function enqueue_style() {
		wp_enqueue_style( 'bpbbpst-bbpress-widget-css', BPBBPST_PLUGIN_URL_CSS . '/bpbbpst-bbpress-widget.css' );
	}

}

add_action( 'bbp_widgets_init', array( 'Bpbbpst_Support_Stats', 'register_widget' ), 10 );
?>