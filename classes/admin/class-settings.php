<?php
/**
 * Handle settings page for this plugin.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 12:45:59
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2023-05-05 11:13:21
 *
 * @package crmservice
 */

namespace CRMServiceWP\Admin\Settings;

use CRMServiceWP;
use CRMServiceWP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 *  Class for plugin settings.
 *
 *  @since 1.0.0
 */
class Settings extends CRMServiceWP\Plugin {
	/**
	 *  Instance of helper.
	 *
	 *  @var resource
	 */
	protected static $helper;

	/**
	 *  Fire it up!
	 *
	 *  @since 1.0.0
	 */
	public function __construct() {
		// Get instance of helper.
		self::$helper = new CRMServiceWP\Helper\Helper();

		// Ignition done, give some kick.
		self::run();
	} // end __construct

	/**
	 *  Add hooks.
	 *
	 *  @since  1.0.0
	 */
	protected function run() {
		// Add link to settings on plugin list.
		\add_filter( 'plugin_action_links_crm-service/crm-service.php', array( __CLASS__, 'add_settings_link_to_plugin_list' ) );

		// Add our settings page to menu and make the actual page.
		\add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
		\add_action( 'admin_init', array( __CLASS__, 'add_setting_sections_and_fields' ) );

		\add_action( 'admin_init', array( __CLASS__, 'maybe_do_reset' ) );
		\add_action( 'admin_init', array( __CLASS__, 'maybe_clear_cache' ) );
	} // end run

	/**
	 *  Add link to settings on plugin list
	 *
	 *  @since 1.0.0
	 *  @param array $links links to show below plugin name.
	 */
	public static function add_settings_link_to_plugin_list( $links ) {
		if ( \current_user_can( 'manage_options' ) ) {
			$links[] = '<a href="' . self::$helper->get_plugin_page_url( array( 'page' => 'crmservice' ) ) . '">' . \esc_attr__( 'Settings', 'crmservice' ) . '</a>';
		}

		return $links;
	} // end add_settings_link_to_plugin_list

	/**
	 *  Add our page to admin menu
	 *
	 *  @since 1.0.0
	 */
	public static function add_menu_page() {
		\add_submenu_page(
			self::$helper->get_plugin_page_url( null, true ),
			\esc_attr__( 'CRM-service settings', 'crmservice' ),
			\esc_attr__( 'Settings', 'crmservice' ),
			'manage_options',
			'crmservice',
			array( __CLASS__, 'page_output' )
		);
	} // end add_menu_page

	/**
	 *  Output the settings page
	 *
	 *  @since  1.0.0
	 */
	public static function page_output() {
		self::maybe_send_bugreport();
		include_once CRMServiceWP\Plugin::crmservice_base_path( 'views/admin/settings.php' );
	} // end page_output

	/**
	 *  Register setting sections and fields
	 *
	 *  @since 1.0.0
	 */
	public static function add_setting_sections_and_fields() {
		// Add empty options with autoload false for performace reasons.
		\add_option( 'crmservice_api_baseurl', '', '', 'no' );
		\add_option( 'crmservice_api_key', '', '', 'no' );
		\add_option( 'crmservice_form_plugin', '', '', 'no' );

		\register_setting( 'crmservice_settings', 'crmservice_api_baseurl', array( __CLASS__, 'sanitize_callback_api_baseurl_scheme' ) );
		\register_setting( 'crmservice_settings', 'crmservice_api_key' );
		\register_setting( 'crmservice_settings', 'crmservice_form_plugin' );

		\add_settings_section(
			'crmservice_settings_api',
			\esc_attr__( 'API Credentials', 'crmservice' ),
			array( __CLASS__, 'section_callback_api' ),
			'crmservice_settings_api'
		);

		\add_settings_field(
			'crmservice_api_baseurl',
			\esc_attr__( 'API base URL', 'crmservice' ),
			array( __CLASS__, 'field_callback_api_baseurl' ),
			'crmservice_settings_api',
			'crmservice_settings_api'
		);

		\add_settings_field(
			'crmservice_api_key',
			\esc_attr__( 'API Key', 'crmservice' ),
			array( __CLASS__, 'field_callback_api_key' ),
			'crmservice_settings_api',
			'crmservice_settings_api'
		);

		\add_settings_section(
			'crmservice_settings_form_plugin',
			\esc_attr__( 'Form plugin', 'crmservice' ),
			array( __CLASS__, 'section_callback_api' ),
			'crmservice_settings_form_plugin'
		);

		\add_settings_field(
			'crmservice_api_form_plugin',
			\esc_attr__( 'Form plugin', 'crmservice' ),
			array( __CLASS__, 'field_callback_form_plugin' ),
			'crmservice_settings_form_plugin',
			'crmservice_settings_form_plugin'
		);
	} // end add_setting_sections_and_fields

	/**
	 *  So, there's really no good reason to document all these field callbacks.
	 *
	 *  phpcs:disable
	 */
	public static function section_callback_api() {
		return;
	} // end section_callback_api

	public static function field_callback_api_baseurl() {
		$value = \get_option( 'crmservice_api_baseurl' );
		$value = ( ! empty( $value ) ) ? $value : '';
		$readonly = ( ! empty( $value ) ) ? ' readonly' : '';

		echo '<input type="text" class="regular-text" id="crmservice_api_baseurl" name="crmservice_api_baseurl" value="' . \esc_attr( $value ) . '"' . $readonly . '/>';
	} // end field_callback_api_baseurl

	public static function field_callback_api_key() {
		$value = self::$helper->get_api_key();

		$value = ( ! empty( $value ) ) ? $value : '';
		$readonly = ( ! empty( $value ) ) ? ' readonly' : '';

		if ( getenv( 'CRMSERVICE_API_KEY' ) && ! empty( $readonly ) ) {
			_e( 'Defined in server envarioment variable', 'crm-service' );
			return;
		}

		echo '<input type="password" class="regular-text" id="crmservice_api_key" name="crmservice_api_key" value="' . \esc_attr( $value ) . '"' . $readonly . '/>';
	} // end field_callback_api_key

	public static function field_callback_form_plugin() {
		$value = \get_option( 'crmservice_form_plugin' );
		$supported_plugins = self::$helper->get_supported_form_plugins();

		if ( is_array( $supported_plugins ) && ! empty( $supported_plugins ) ) {
			echo '<select id="crmservice_form_plugin" name="crmservice_form_plugin">';
			echo '<option value="">' . \esc_attr__( 'Select', 'crmservice' ) . '</option>';

			foreach ( $supported_plugins as $plugin ) {
				$additions = ( $plugin['slug'] === $value ) ? ' selected' : '';
				$additions = ( ! $plugin['active'] ) ? $additions . ' disabled' : $additions;
				echo '<option value="' . \esc_attr( $plugin['slug'] ) . '"' . $additions . '>' . \esc_html( $plugin['name'] ) . '</option>';
			}

			echo '</select>';
		} else {
			echo '<p>' . \esc_attr__( 'None of the supported form plugins are installed or activated.', 'crmservice' ) . '</p>';
		}
	} // end field_callback_form_plugin

	public static function sanitize_callback_api_baseurl_scheme( $value = '' ) {
		if ( ! empty( $value ) ) {
			$value = self::$helper->url_add_scheme( $value );
		}

		return $value;
	} // end function sanitize_callback_api_baseurl_scheme
	// phpcs:enable

	/**
	 *  Check if url parameter and nonce says that we need to purge plugin cache.
	 *
	 *  @since  1.0.0
	 */
	public static function maybe_clear_cache() {
		if ( isset( $_GET['crmservice_purgecache'] ) && \current_user_can( 'manage_options' ) ) {
			if ( \wp_verify_nonce( wp_unslash( $_GET['crmservice_nonce'] ), 'crmservice_purgecache' ) ) { // phpcs:ignore WordPress.VIP.ValidatedSanitizedInput.InputNotValidated, WordPress.VIP.ValidatedSanitizedInput.InputNotSanitized
				self::$helper->purge_cache();
				\wp_redirect( self::$helper->get_plugin_page_url( array(
					'page'								=> 'crmservice',
					'crmservice_message'	=> 'purgecache',
				) ) );
				exit;
			}
		}
	} // end maybe_do_reset

	/**
	 *  Check if url parameter and nonce says that we need to reset whole plugin.
	 *
	 *  @since  1.0.0
	 */
	public static function maybe_do_reset() {
		if ( isset( $_GET['crmservice_reset'] ) && \current_user_can( 'manage_options' ) ) {
			if ( \wp_verify_nonce( wp_unslash( $_GET['crmservice_nonce'] ), 'crmservice_reset' ) ) { // phpcs:ignore WordPress.VIP.ValidatedSanitizedInput.InputNotValidated, WordPress.VIP.ValidatedSanitizedInput.InputNotSanitized
				self::$helper->reset();
				wp_redirect( self::$helper->get_plugin_page_url( array(
					'page'								=> 'crmservice',
					'crmservice_message'	=> 'reset',
				) ) );
				exit;
			}
		}
	} // end maybe_do_reset

	/**
	 *  Do the bug report send.
	 *
	 *  @since  1.0.0
	 */
	public static function maybe_send_bugreport() {
		if ( \current_user_can( 'manage_options' ) && isset( $_POST['crmservice-sendbugreport'] ) ) :
			if ( isset( $_POST['crmservice_bugreport_send'] ) && \wp_verify_nonce( wp_unslash( $_POST['crmservice_bugreport_send'] ), 'crmservice_bugreport' ) ) : // phpcs:ignore WordPress.VIP.ValidatedSanitizedInput.InputNotValidated, WordPress.VIP.ValidatedSanitizedInput.InputNotSanitized
				global $wp_version;

				if ( ! function_exists( 'get_plugins' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				$soap_support = Helper\Helper::check_soap_support();
				$credentials_health = Helper\Helper::check_api_settings_existance();
				$api_health = Helper\Helper::check_api_credentials_health();
				$form_plugin = Helper\Helper::get_form_plugin( true );
				$form_plugin_active = Helper\Helper::check_if_form_plugin_active();
				$plugins = \get_plugins();
				$theme = \wp_get_theme();

				// phpcs:disable
				$message = 'Name: ' . \sanitize_text_field( $_POST['name'] ) . "\r\n";
				$message .= 'Email: ' . \sanitize_text_field( $_POST['email'] ) . "\r\n\r\n";
				$message .= \sanitize_textarea_field( $_POST['message'] ) . "\r\n\r\n";
				$message .= "WP: {$wp_version}\r\n";
				$message .= 'PHP: ' . phpversion() . "\r\n";
				$message .= 'Account ID: ' . \get_option( 'crmservice_api_baseurl' ) . "\r\n";
				$message .= 'SOAP support: ' . $soap_support . "\r\n";
				$message .= 'Credentials health: ' . $credentials_health . "\r\n";
				$message .= 'API health: ' . $api_health . "\r\n";
				$message .= 'Form plugin: ' . $form_plugin . ' (' . $form_plugin_active . ")\r\n";
				$message .= 'Theme: ' . $theme->get( 'Name' ) . ' (' . $theme->get( 'Version' ) . ")\r\n";
				$message .= '' . "\r\n";
				$message .= 'Plugins:' . "\r\n";
				// phpcs:enable

				foreach ( $plugins as $plugin => $data ) {
					$message .= "{$plugin}: " . $data['Version'] . "\r\n";
				}

				\wp_mail( 'info@crm-service.fi', 'WP Plugin bug report', $message );
			endif;
		endif;
	} // end maybe_send_bugreport
}

new Settings();
