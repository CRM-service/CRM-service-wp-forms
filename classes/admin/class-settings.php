<?php
/**
 * Handle settings page for this plugin.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 12:45:59
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-03-30 17:23:15
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
	 *  @since 0.1.0-alpha
	 */
	function __construct() {
		// Get instance of helper.
		self::$helper = new CRMServiceWP\Helper\Helper();

		// Ignition done, give some kick.
		self::run();
	} // end __construct

	/**
	 *  Add hooks.
	 *
	 *  @since  0.1.0-alpha
	 */
	protected function run() {
		// Add link to settings on plugin list.
		\add_filter( 'plugin_action_links_crm-service/crm-service.php', array( __CLASS__, 'add_settings_link_to_plugin_list' ) );

		// Add our settings page to menu and make the actual page.
		\add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
		\add_action( 'admin_init', array( __CLASS__, 'add_setting_sections_and_fields' ) );
	} // end run

	/**
	 *  Add link to settings on plugin list
	 *
	 *  @since 0.0.1-alpha
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
	 *  @since 0.0.1-alpha
	 */
	public static function add_menu_page() {
		\add_submenu_page(
			self::$helper->get_plugin_page_url( null, true ),
			\esc_attr__( 'CRM-Service settings', 'crmservice' ),
			\esc_attr__( 'Settings', 'crmservice' ),
			'manage_options',
			'crmservice',
			array( __CLASS__, 'page_output' )
		);
	} // end add_menu_page

	/**
	 *  Output the settings page
	 *
	 *  @since  0.0.1-alpha
	 */
	public static function page_output() {
		include_once CRMServiceWP\Plugin::crmservice_base_path( 'views/admin/settings.php' );
	} // end page_output

	/**
	 *  Register setting sections and fields
	 *
	 *  @since 0.0.1-alpha
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
	 *  So, there's really no good reason to document all these field callbacks
	 *
	 *  @codingStandardsIgnoreStart
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
		$value = getenv( 'CRMSERVICE_API_KEY' );
		if ( empty( $value ) ) {
			$value = \get_option( 'crmservice_api_key' );
		}

		$value = ( ! empty( $value ) ) ? $value : '';
		$readonly = ( ! empty( $value ) ) ? ' readonly' : '';

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

	// @codingStandardsIgnoreEnd
}

new Settings();
