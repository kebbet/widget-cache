<?php
/**
 * Plugin Name:       Widget Cache
 * Plugin URI:        https://github.com/bainternet/GWP-Widget-Cache/blob/master/gwp_widget_cache.php
 * Description:       A plugin to cache WordPress Widgets using the Transients API, based on this tutorial http://generatewp.com/?p=10132
 * Version:           1.1
 * Author:            Ohad Raz
 * Author URI:        http://generatewp.com
 * Domain Path:       /languages
 * Requires at least: 5.3
 * Requires PHP:      5.2.4
 *
 * @author Ohad Raz
 * @package widget-cache
 */

namespace kebbet\muplugin\widgetcache;

/**
 * Hook into the 'init' action
 */
function init() {
	load_textdomain();
}
add_action( 'init', __NAMESPACE__ . '\init', 0 );

/**
 * Load plugin textdomain.
 */
function load_textdomain() {
	load_muplugin_textdomain( 'widget-cache', basename( dirname( __FILE__ ) ) . '/languages' );
}

// Get the main cleanup class and run it.
require_once plugin_dir_path( __FILE__ ) . 'classes/class-widget-cache.php';

/**
 * Initiate the class.
 */
function init_class() {
	$GLOBALS['Widget_Cache'] = new Widget_Cache();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\init_class' );
