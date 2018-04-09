<?php
/**
 * Desc.
 *
 * @package CRMServiceWP
 *
 * Plugin Name:				CRM-Service
 * Plugin URI:
 * Description: 			Integration between Teamtailor and WordPress
 * Author: 						sippis
 * Author URI:				https://www.dude.fi
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl.html
 * Version: 					0.1.0-alpha
 * Requires at least:	4.9.4
 * Tested up to: 			4.9.4
 *
 * Text Domain: crmservice
 * Domain Path: /languages
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-02-27 15:47:00
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-04-06 10:24:36
 */

namespace CRMServiceWP;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'Plugin' ) ) :
	/**
	 *  Main class for plugin.
	 */
	class Plugin {
		/**
		 *  Wrapper function to get path of this plugin.
		 *
		 *  @since  0.1.0-alpha
		 *  @return string 	path to plugin
		 */
		public static function crmservice_base_path( $file = null ) {
			$path = \untrailingslashit( \plugin_dir_path( __FILE__ ) );

			if ( $file ) {
				$path .= "/{$file}";
			}

			return $path;
		} // end crmservice_base_path

		/**
		 *  Run the plugin.
		 *
		 *  @since  0.1.0-alpha
		 */
		public static function load() {
			// Include files needed always.
			include_once self::crmservice_base_path( 'classes/class-helper.php' );
			include_once self::crmservice_base_path( 'classes/class-cpt.php' );
			include_once self::crmservice_base_path( 'classes/class-api.php' );
			include_once self::crmservice_base_path( 'classes/class-forms-common.php' );

			// Include files and run hooks needed only in admin.
			if ( \is_admin() ) {
				include_once self::crmservice_base_path( 'classes/admin/class-settings.php' );

				add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );
			}

			// Add actions.
			\add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
		} // end load

		/**
		 *  Load plugin textdomain
		 *
		 *  @since  0.1.0-alpha
		 */
		public static function load_textdomain() {
			$loaded = \load_plugin_textdomain( 'crmservice', false, dirname( \plugin_basename( __FILE__ ) ) . '/language/' );
			if ( ! $loaded ) {
				$loaded = \load_muplugin_textdomain( 'crmservice', dirname( \plugin_basename( __FILE__ ) ) . '/language/' );
			}
		} // end load_textdomain

		/**
		 *  Enqueue our css and js if user is in dashboard
		 *
		 *  @since  0.1.0-alpha
		 */
		public static function enqueue_admin() {
			$screen = get_current_screen();

			wp_enqueue_style( 'crmservice', plugins_url( 'assets/admin/main.css', __FILE__ ), array(), '0.1.0' );

			if ( 'post' === $screen->base ) {
				if ( 'crmservice_form' === $screen->post_type ) {
					wp_enqueue_script( 'crmservice', plugins_url( 'assets/admin/metabox.js', __FILE__ ), array(), '0.1.0', true );
					wp_localize_script( 'crmservice', 'crmservice', array(
						'root' 			=> esc_url_raw( rest_url() ),
						'nonce' 		=> wp_create_nonce( 'wp_rest' ),
					) );
				}
			}
		} // end enqueue_admin
	} // end class Plugin

	/**
	 *  Start the plugin.
	 *
	 *  @since  0.1.0-alpha
	 */
	function load_plugin() {
    $pg = new Plugin();
    $pg->load();
	} // end load_plugin
endif;

// Add actio to really start the plugin.
add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_plugin' );
