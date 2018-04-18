<?php
/**
 * Desc.
 *
 * @package CRMServiceWP
 *
 * Plugin Name:				CRM-Service
 * Plugin URI:
 * Description: 			Integration between CRM-Service and WordPress
 * Author: 						sippis
 * Author URI:				https://www.dude.fi
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl.html
 * Version: 					0.1.1-alpha
 * Requires at least:	4.9.4
 * Tested up to: 			4.9.4
 *
 * Text Domain: crmservice
 * Domain Path: /languages
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-02-27 15:47:00
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-04-18 11:20:39
 */

namespace CRMServiceWP;

use CRMServiceWP;
use CRMServiceWP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'Plugin' ) ) :
	/**
	 *  Main class for plugin.
	 */
	class Plugin {
		/**
		 *  Instance of helper.
		 *
		 *  @var resource
		 */
		protected static $helper;

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

			// load send and all that jazz only if SOAP and API checks are OK.

			// Include files needed always.
			include_once self::crmservice_base_path( 'classes/class-helper.php' );
			include_once self::crmservice_base_path( 'classes/class-cpt.php' );
			include_once self::crmservice_base_path( 'classes/class-api.php' );
			include_once self::crmservice_base_path( 'classes/class-forms-common.php' );

			// Get instance of helper.
			self::$helper = new CRMServiceWP\Helper\Helper();

			// Include files and run hooks needed only in admin.
			if ( \is_admin() ) {
				include_once self::crmservice_base_path( 'classes/admin/class-settings.php' );

				\add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );
				\add_action( 'admin_init', array( __CLASS__, 'maybe_show_admin_notices' ) );
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
			$screen = \get_current_screen();

			\wp_enqueue_style( 'crmservice', \plugins_url( 'assets/admin/main.css', __FILE__ ), array(), '0.1.0' );

			if ( 'post' === $screen->base || 'edit' === $screen->base ) {
				if ( 'crmservice_form' === $screen->post_type ) {
					\wp_enqueue_script( 'crmservice', \plugins_url( 'assets/admin/metabox.js', __FILE__ ), array(), '0.1.0', true );
					\wp_localize_script( 'crmservice', 'crmservice', array(
						'root' 			=> \esc_url_raw( \rest_url() ),
						'nonce' 		=> \wp_create_nonce( 'wp_rest' ),
					) );
				}
			}
		} // end enqueue_admin

		/**
		 *  Check if we need to show some admin notices
		 *
		 *  @since  0.1.1-alpha
		 */
		public static function maybe_show_admin_notices() {
			// Maybe show notice from url.
			if ( \current_user_can( 'manage_options' ) && isset( $_GET['crmservice_message'] ) ) {
				\add_action( 'admin_notices', array( __CLASS__, 'notice_from_url' ) );
			}

			// Maybe show SOAP compatibility error.
			if ( \current_user_can( 'edit_posts' ) && ! self::$helper->check_soap_support() ) {
				\add_action( 'admin_notices', array( __CLASS__, 'notice_soap_support' ) );
			}

			// Maybe show API connectivity issue warning.
			if ( \current_user_can( 'edit_posts' ) && ! self::$helper->check_api_credentials_health() ) {
				\add_action( 'admin_notices', array( __CLASS__, 'notice_no_api_connection' ) );
			}

			// Maybe show form plugin setting missing warning.
			if ( \current_user_can( 'manage_options' ) && ! self::$helper->get_form_plugin() ) {
				\add_action( 'admin_notices', array( __CLASS__, 'notice_form_plugin_not_configured' ) );
			}

			if ( \current_user_can( 'manage_options' ) && ! self::$helper->check_if_form_plugin_active() ) {
				\add_action( 'admin_notices', array( __CLASS__, 'notice_form_plugin_not_active' ) );
			}
		} // end maybe_show_admin_notices

		/**
		 *  Show warnings from url parameter.
		 *
		 *  @since  0.1.1-alpha
		 */
		public static function notice_from_url() {
			$text_string = false;

			if ( 'purgecache' === $_GET['crmservice_message'] ) { // phpcs:ignore WordPress.VIP.ValidatedSanitizedInput.InputNotValidated
				$classes = 'notice-success';
				$text_string = \wp_kses( 'Plugin cache cleared.', 'crmservice' );
			} else if ( 'reset' === $_GET['crmservice_message'] ) { // phpcs:ignore WordPress.VIP.ValidatedSanitizedInput.InputNotValidated
				$classes = 'notice-success';
				$text_string = \wp_kses( 'Plugin setting reseted.', 'crmservice' );
			}

			if ( $text_string ) {
				include_once CRMServiceWP\Plugin::crmservice_base_path( 'views/admin/notice.php' );
			}
		} // end notice_from_url

		/**
		 *  Show SOAP compatibility issue warning
		 *
		 *  @since  0.1.1-alpha
		 */
		public static function notice_soap_support() {
			$classes = 'notice-error';
			$text_string = \wp_kses( '<b>CRM-Service plugin can not work</b>, because your server does not have SOAP client installed or activated. Please contact your system administrator.', 'crmservice' );

			include_once CRMServiceWP\Plugin::crmservice_base_path( 'views/admin/notice.php' );
		} // end notice_no_api_connection

		/**
		 *  Show API connectivity issue warning
		 *
		 *  @since  0.1.1-alpha
		 */
		public static function notice_no_api_connection() {
			$classes = 'notice-error';
			$text_string = \wp_kses( '<b>CRM-Service plugin can not connect to CRM-Service!</b>', 'crmservice' );

			if ( ! \current_user_can( 'manage_options' ) ) {
				$text_string .= ' ' . \wp_kses( 'Please contact your site administrator to fix this issue.', 'crmservice' );
			} else {
				// Translators: %s is link to settings page.
		    $text_string .= ' ' . wp_sprintf( wp_kses( 'There might be a temporary problem with our API or you might have added wrong <a href="%s">API credentials</a>. If this problem persist, please contact our support.', 'crmservice' ), self::$helper->get_plugin_page_url( array( 'page' => 'crmservice' ) ) );
			}

			include_once CRMServiceWP\Plugin::crmservice_base_path( 'views/admin/notice.php' );
		} // end notice_no_api_connection

		/**
		 *  Show configuration issue warning
		 *
		 *  @since  0.1.1-alpha
		 */
		public static function notice_form_plugin_not_configured() {
			$classes = 'notice-warning';
			$text_string = \wp_kses( '<b>CRM-Service plugin can not work</b>, form plugin is not selected.', 'crmservice' );

			include_once CRMServiceWP\Plugin::crmservice_base_path( 'views/admin/notice.php' );
		} // end notice_form_plugin_not_configured

		/**
		 *  Show form plugin issue warning
		 *
		 *  @since  0.1.1-alpha
		 */
		public static function notice_form_plugin_not_active() {
			$classes = 'notice-warning';
			$text_string = \wp_kses( '<b>CRM-Service plugin can not work</b>, form plugin selected is not active.', 'crmservice' );

			include_once CRMServiceWP\Plugin::crmservice_base_path( 'views/admin/notice.php' );
		} // end notice_form_plugin_not_active
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
