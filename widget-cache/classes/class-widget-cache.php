<?php
/**
 * The main Widget Cache class
 *
 * @author Ohad Raz
 * @package widget-cache
 */

namespace kebbet\muplugin\widgetcache;

/**
 * Widget_Cache
 */
class Widget_Cache {
	/**
	 * Transient exiration time.
	 *
	 * @var int
	 */
	private $cache_time = 43200; // 12 hours in seconds.

	/**
	 * Wheter to output time comment or not.
	 *
	 * @var bool
	 */
	private $comment_output = false;

	/**
	 * Class constructor where we will call our filter and action hooks.
	 */
	public function __construct() {
		add_filter( 'widget_display_callback', array( $this, 'cache_widget_output' ), 10, 3 );
		add_action( 'in_widget_form', array( $this, 'in_widget_form' ), 5, 3 );
		add_filter( 'widget_update_callback', array( $this, 'widget_update_callback' ), 5, 3 );
	}

	/**
	 * Simple function to generate a unique id for the widget transient
	 * based on the widget's instance and arguments
	 *
	 * @param array $i widget instance.
	 * @param array $a widget arguments.
	 * @return string md5 hash
	 */
	private function get_sidebar_key( $i, $a ) {
		return 'WC-' . md5( wp_json_encode( array( $i, $a ) ) );
	}

	/**
	 * The HTML-output for the widget.
	 *
	 * @param array     $instance The current widget instance's settings.
	 * @param WP_Widget $widget   The current widget instance.
	 * @param array     $args     An array of default widget arguments.
	 * @return mixed array|boolean
	 */
	public function cache_widget_output( $instance, $widget, $args ) {

		if ( false === $instance ) {
			return $instance;
		}

		// Skip cache on preview.
		if ( $widget->is_preview() ) {
			return $instance;
		}
		// Check if we need to cache this widget?
		if ( isset( $instance['wc_cache'] ) && true === $instance['wc_cache'] ) {
			return $instance;
		}

		$timer_start    = microtime( true ); // Simple timer to clock the widget rendring.
		$transient_name = $this->get_sidebar_key( $instance, $args ); // Create a uniqe transient ID for this widget instance.
		$cached_widget  = get_transient( $transient_name ); // Get the "cached version of the widget".

		if ( false === $cached_widget ) {
			// It wasn't there, so render the widget and save it as a transient
			// start a buffer to capture the widget output.
			ob_start();
			// This renders the widget.
			$widget->widget( $args, $instance );
			// Get rendered widget from buffer.
			$cached_widget = ob_get_clean();
			// Save/cache the widget output as a transient.
			set_transient( $transient_name, $cached_widget, $this->cache_time );
		}

		// Output the widget.
		echo wp_kses_post( $cached_widget );

		if ( true === $this->comment_output ) {
			// Output rendering time as an html comment.
			echo '<!-- From widget cache in ' . number_format( microtime( true ) - $timer_start, 5 ) . ' seconds -->';
		}

		// After the widget was rendered and printed we return false to short-circuit the normal display of the widget.
		return false;
	}

	/**
	 * This method displays a checkbox in the widget panel
	 *
	 * @param WP_Widget $t     The widget instance, passed by reference.
	 * @param null      $return   Return null if new fields are added.
	 * @param array     $instance An array of the widget's settings.
	 */
	public function in_widget_form( $t, $return, $instance ) {
		$instance = wp_parse_args(
			(array) $instance,
			array(
				'title'    => '',
				'text'     => '',
				'wc_cache' => null,
			)
		);

		if ( ! isset( $instance['wc_cache'] ) ) {
			$instance['wc_cache'] = null;
		}
		$id    = $t->get_field_id( 'wc_cache' );
		$name  = $t->get_field_name( 'wc_cache' );
		$label = __( 'Mark this option to not cache this widget.', 'widget-cache' );
		?>
			<input id="<?php echo esc_html( $id ); ?>" name="<?php echo esc_html( $name ); ?>" type="checkbox" <?php checked( isset( $instance['wc_cache'] ) ? $instance['wc_cache'] : 0 ); ?> />
			<label for="<?php echo esc_html( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<?php
	}

	/**
	 * Update callback for widget
	 *
	 * @param array $instance     The current widget instance's settings.
	 * @param array $new_instance Array of new widget settings.
	 * @param array $old_instance Array of old widget settings.
	 * @return array
	 */
	public function widget_update_callback( $instance, $new_instance, $old_instance ) {
		// Save the checkbox if it is set.
		$instance['wc_cache'] = isset( $new_instance['wc_cache'] );
		return $instance;
	}
}
