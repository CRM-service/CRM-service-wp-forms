<?php

/**
 * @Author: Timi Wahalahti
 * @Date:   2018-04-25 17:08:45
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2023-05-05 11:44:10
 */

namespace CRMServiceWP\Admin\Notices;

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
class Notices extends CRMServiceWP\Plugin {
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
		\add_action( 'admin_init', array( __CLASS__, 'maybe_show_admin_notices' ) );
	} // end run

	/**
	 *  Check if we need to show some admin notices
	 *
	 *  @since  1.0.0
	 */
	public static function maybe_show_admin_notices() {
		// Maybe show notice from url.
		if ( \current_user_can( 'edit_posts' ) && isset( $_GET['crmservice_message'] ) ) {
			\add_action( 'admin_notices', array( __CLASS__, 'notice_from_url' ) );
		}

		// Maybe show SOAP compatibility error.
		if ( \current_user_can( 'edit_posts' ) && ! self::$helper->check_soap_support() ) {
			\add_action( 'admin_notices', array( __CLASS__, 'notice_soap_support' ) );
			return;
		}

		// Maybe show onboarding process.
		if ( current_user_can( 'edit_posts' ) && ! self::$helper->check_api_settings_existance() ) {
			add_action( 'admin_notices', array( __CLASS__, 'maybe_show_onboarding' ) );
			return;
		}

		// Maybe show API connectivity issue warning.
		if ( \current_user_can( 'edit_posts' ) && ! self::$helper->check_api_credentials_health() ) {
			\add_action( 'admin_notices', array( __CLASS__, 'notice_no_api_connection' ) );
		}

		// Maybe show selected form plugin not active warning
		if ( \current_user_can( 'edit_posts' ) && ! self::$helper->check_if_form_plugin_active() ) {
			\add_action( 'admin_notices', array( __CLASS__, 'notice_form_plugin_not_active' ) );
		}

		// Maybe show form plugin setting missing warning.
		if ( \current_user_can( 'edit_posts' ) && ! self::$helper->get_form_plugin() ) {
			\add_action( 'admin_notices', array( __CLASS__, 'notice_form_plugin_not_configured' ) );
		}

		// Maybe show form plugin setting missing warning.
		if ( \current_user_can( 'edit_posts' ) && 'contact-form-7' === self::$helper->get_form_plugin( true ) && ! self::$helper->check_contact_form_7_flamingo() ) {
			\add_action( 'admin_notices', array( __CLASS__, 'notice_contact_form_7_flamingo_support' ) );
		}
	} // end maybe_show_admin_notices

	public static function maybe_show_onboarding() {
		$screen = \get_current_screen();

		if ( 'crmservice_form_page_crmservice' !== $screen->id ) {
			include_once CRMServiceWP\Plugin::crmservice_base_path( 'views/admin/onboarding.php' );
		}
	} // end maybe_show_onboarding

	/**
	 *  Show warnings from url parameter.
	 *
	 *  @since  1.0.0
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
	 *  @since  1.0.0
	 */
	public static function notice_soap_support() {
		$classes = 'notice-error';
		$text_string = \wp_kses( '<b>CThe RM-service plugin can\'t work!</b> Your server does not have SOAP-client support which is required. Contact your website administrator or hosting provider to enable SOAP ', 'crmservice' );

		include_once CRMServiceWP\Plugin::crmservice_base_path( 'views/admin/notice.php' );
	} // end notice_no_api_connection

	/**
	 *  Show API connectivity issue warning
	 *
	 *  @since  1.0.0
	 */
	public static function notice_no_api_connection() {
		$classes = 'notice-error';
		$text_string = \wp_kses( '<b>The CRM-service plugin can\'t connect to CRM-service!</b>', 'crmservice' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			$text_string .= ' ' . \wp_kses( 'Please contact your website administrator to fix this issue.', 'crmservice' );
		} else {
			// Translators: %s is link to settings page.
	    $text_string .= ' ' . \wp_sprintf( wp_kses( 'You might have added wrong <a href="%s">API credentials</a>. Please check your API credntials. With correct API credentials, there might be a temporary problem with our API. If this problem persist, please <a href="%s">contact our support</a>.', 'crmservice' ), self::$helper->get_plugin_page_url( array( 'page' => 'crmservice' ) ), self::$helper->get_plugin_page_url( array( 'page' => 'crmservice', 'tab'	=> 'bugreport' ) ) );
		}

		include_once CRMServiceWP\Plugin::crmservice_base_path( 'views/admin/notice.php' );
	} // end notice_no_api_connection

	/**
	 *  Show configuration issue warning
	 *
	 *  @since  1.0.0
	 */
	public static function notice_form_plugin_not_configured() {
		$classes = 'notice-warning';
		$text_string = \wp_kses( '<b>The CRM-service plugin can\'t work!</b>', 'crmservice' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			$text_string .= ' ' . \wp_kses( 'Please contact your website administrator to fix this issue.', 'crmservice' );
		} else {
			$text_string .= ' ' . \wp_sprintf( \wp_kses( 'In <a href="%s">settings page</a>, select a form plugin you want to integrate to.', 'crmservice' ), self::$helper->get_plugin_page_url( array( 'page' => 'crmservice' ) ) );
		}

		include_once CRMServiceWP\Plugin::crmservice_base_path( 'views/admin/notice.php' );
	} // end notice_form_plugin_not_configured

	/**
	 *  Show form plugin issue warning
	 *
	 *  @since  1.0.0
	 */
	public static function notice_form_plugin_not_active() {
		$classes = 'notice-warning';
		$text_string = \wp_kses( '<b>The CRM-service plugin can\'t work!</b>', 'crmservice' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			$text_string .= ' ' . \wp_kses( 'Please contact your website administrator to fix this issue.', 'crmservice' );
		} else {
			$text_string .= ' ' . \wp_sprintf( \wp_kses( 'The form plugin you have selected in settings, is not active.', 'crmservice' ), self::$helper->get_plugin_page_url( array( 'page' => 'crmservice' ) ) );
		}

		include_once CRMServiceWP\Plugin::crmservice_base_path( 'views/admin/notice.php' );
	} // end notice_form_plugin_not_active

	public static function notice_contact_form_7_flamingo_support() {
		$classes = 'notice-warning is-dismissible';

		if ( ! \current_user_can( 'manage_options' ) ) {
			$text_string = \wp_sprintf( \wp_kses( 'The CRM-service plugin will try to resend form submissions to the CRM-service, if the first submission fails for some reason. In order to this feature to work with the Contact Form 7, your website administrator needs to install the <a href="%s">Flamingo</a> -plugin. It will also save the form submisison directly to WordPress, which is considered as a good practice in general.', 'crmservice' ), 'https://wordpress.org/plugins/flamingo/' );
		} else {
			$text_string = \wp_sprintf( \wp_kses( 'The CRM-service plugin will try to resend form submissions to the CRM-service, if the first submission fails for some reason. In order to this feature to work with the Contact Form 7, you need to install the <a href="%s">Flamingo</a> -plugin. It will also save the form submisison directly to WordPress, which is considered as a good practice in general.', 'crmservice' ), \admin_url( 'plugin-install.php?tab=plugin-information&plugin=flamingo' ) );
		}

		include_once CRMServiceWP\Plugin::crmservice_base_path( 'views/admin/notice.php' );
	} // end notice_contact_form_7_flamingo_support
}

new Notices();
