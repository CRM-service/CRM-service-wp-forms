<?php
/**
 * Helper functions for plugin.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 12:45:59
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2023-05-05 11:04:45
 *
 * @package crmservice
 */

namespace CRMServiceWP\Helper;

use CRMServiceWP;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 *  Class for helper functions used across plugin.
 *
 *  @since 1.0.0
 */
class Helper extends CRMServiceWP\Plugin {

	/**
	 *  Check if server is capable to handle SOAP calls.
	 *
	 *  @since  1.0.0
	 *  @return boolean  true if can handle soap, false otherwise
	 */
	public static function check_soap_support() {
		if ( extension_loaded( 'soap' ) ) {
		  return true;
		}

		return false;
	} // end function check_soap_support

	/**
	 *  Check if API settings are saved to databse
	 *
	 *  @since  1.0.0
	 *  @return boolean  true if settings exist, false otherwise
	 */
	public static function check_api_settings_existance() {
		$api_account_id = \get_option( 'crmservice_api_baseurl' );
		$api_key = self::get_api_key();

		if ( empty( $api_account_id ) || empty( $api_key ) ) {
			return false;
		}

		return true;
	} // end check_api_settings_existance

	/**
	 *  Check that API connection is working.
	 *
	 *  @since  1.0.0
	 *  @return boolean  true if connection is working, false if not
	 */
	public static function check_api_credentials_health() {
		if ( ! self::check_api_settings_existance() ) {
			return false;
		}

		// Try to get modules. API connection is not working if false.
		if ( ! CRMServiceWP\API\API::call_api() ) {
			return false;
		}

		return true;
	} // end check_api_credentials_health

	/**
	 *  Check that selected form plugin is active.
	 *
	 *  @since  1.0.0
	 *  @return boolean  true if plugin is active, false it not
	 */
	public static function check_if_form_plugin_active() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$form_plugin = self::get_form_plugin();

    if ( ! isset( $form_plugin['dirfile'] ) ) {
      return false;
    }

		return \is_plugin_active( $form_plugin['dirfile'] );
	} // end check_form_plugin_active

	/**
	 *  Get list of supported form plugins.
	 *
	 *  @since  1.0.0
	 *  @return array  array of supported plugins
	 */
	public static function get_supported_form_plugins() {
		if ( ! \is_admin() || ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$plugins = array(
			'wp-libre-form'	=> array(
				'active'				=> false,
				'name'					=> 'WP Libre Form',
				'slug'					=> 'wp-libre-form',
				'dirfile'				=> 'wp-libre-form/wp-libre-form.php',
				'new_url'				=> 'edit.php?post_type=wplf-form',
				'submit_hook'		=> 'wplf_post_validate_submission',
				'plugin_url'		=> 'https://wordpress.org/plugins/wp-libre-form/',
				'class'					=> 'CRMServiceWP\Forms\WPLibreForm\FormsWPLibreForm',
			),
			'gravityforms'	=> array(
				'active'				=> false,
				'name'					=> 'Gravity Forms',
				'slug'					=> 'gravityforms',
				'dirfile'				=> 'gravityforms/gravityforms.php',
				'new_url'				=> 'admin.php?page=gf_edit_forms',
				'submit_hook'		=> 'gform_after_submission',
				'plugin_url'		=> 'https://www.gravityforms.com/',
				'class'					=> 'CRMServiceWP\Forms\GravityForms\FormsGravityForms',
			),
			'contact-form-7'	=> array(
				'active'				=> false,
				'name'					=> 'Contact Form 7',
				'slug'					=> 'contact-form-7',
				'dirfile'				=> 'contact-form-7/wp-contact-form-7.php',
				'new_url'				=> 'admin.php?page=wpcf7',
				'submit_hook'		=> 'wpcf7_after_flamingo',
				'plugin_url'		=> 'https://wordpress.org/plugins/contact-form-7/',
				'class'					=> 'CRMServiceWP\Forms\ContactForm7\FormsContactForm7',
			),
		);

		foreach ( $plugins as $key => $plugin ) {
			if ( \is_plugin_active( $plugin['dirfile'] ) ) {
				$plugins[ $key ]['active'] = true;
			} else {
				$plugins[ $key ]['name'] = $plugin['name'] . ' (' . \esc_attr__( 'deactivated', 'crmservice' ) . ')';
			}
		}

		// Check contact form 7 submit hook based on flamingo existance.
		if ( ! class_exists( 'Flamingo_Contact' ) || ! class_exists( 'Flamingo_Inbound_Message' ) ) {
			$plugins['contact-form-7']['submit_hook'] = 'wpcf7_submit';
		}

		return $plugins;
	} // end get_supported_form_plugins

	/**
	 *  Get selected form plugin.
	 *
	 *  @since  1.0.0
	 *  @param  boolean $only_name if return only plugin name.
	 *  @return mixed             plugin name or all details of plugin
	 */
	public static function get_form_plugin( $only_name = false ) {
		$form_plugin = \get_option( 'crmservice_form_plugin' );
		$supported_plugins = self::get_supported_form_plugins();

		if ( empty( $form_plugin ) || ! array_key_exists( $form_plugin, $supported_plugins ) ) {
			return false;
		}

		if ( $only_name ) {
			return $form_plugin;
		}

		return $supported_plugins[ $form_plugin ];
	} // end get_form_plugin

	/**
	 *  Get API key.
	 *
	 *  @since  1.0.0
	 *  @return mixed  string of API key if configured, false otherwise
	 */
	public static function get_api_key() {
		if ( $api_key = getenv( 'CRMSERVICE_API_KEY' ) ) {
			return $api_key;
		}

		return get_option( 'crmservice_api_key' );
	} // end get_api_key

	/**
	 *  Add scheme to url if does not have already.
	 *
	 *  @since	1.0.0
	 *  @param 	string $url    URL to check for scheme.
	 *  @param 	string $scheme what scheme to use.
	 *  @return string				 URL with scheme
	 */
	public static function url_add_scheme( $url, $scheme = 'https://' ) {
  	return parse_url( $url, PHP_URL_SCHEME ) === null ? $scheme . $url : $url;
	} // end url_add_scheme

	/**
	 *  Get plugin admin page full url or just base.
	 *
	 *  @since  1.0.0
	 *  @param  array   $args      GET parameters add to url.
	 *  @param  boolean $only_base return just base if true.
	 *  @return string             plugin admin page url or base
	 */
	public static function get_plugin_page_url( $args = array(), $only_base = false ) {
		$base = 'edit.php?post_type=crmservice_form';

		if ( $only_base ) {
			return $base;
		}

		return \add_query_arg( $args, \get_admin_url( null, $base ) );
	} // end get_plugin_page_url

	/**
	 *  Save endpoint response to transient and store transient name to option,
	 *  because we need those when resetting plugin
	 *
	 *  @since 0.0.1-alpha
	 *  @param string  $key        key/name for transient.
	 *  @param mixed   $value      value to save.
	 *  @param integer $expiration how long this transient should exist, in seconds.
	 */
	public static function set_transient( $key = null, $value = null, $expiration = 900 ) {
		\add_option( 'crmservice_transient_keys', array(), '', 'no' ); // in the name of not autoloading.

		$transient_keys = \get_option( 'crmservice_transient_keys', array() );

		if ( \set_transient( $key, $value, $expiration ) ) {
			$transient_keys[ $key ] = true;
			\update_option( 'crmservice_transient_keys', $transient_keys );
			return true;
			}

		return false;
	} // end set_transient

	/**
	 *  Remove endpoint response from transient cache
	 *
	 *  @since 1.0.0
	 *  @param string $key        key/name for transient.
	 */
	public static function delete_transient( $key = null ) {
		$transient_keys = \get_option( 'crmservice_transient_keys', array() );

		$delete = \delete_transient( $key );
		if ( ! $delete ) {
			\delete_transient( 'crmservice_api_response_' . md5( $key ) );
		}

		if ( $delete ) {
			unset( $transient_keys[ $key ] );
			\update_option( 'crmservice_transient_keys', $transient_keys );
			return true;
		}

		return false;
	} // end delete_transient

	/**
	 *  Purge our transient/endpoint response cache
	 *
	 *  @since  1.0.0
	 */
	public static function purge_cache() {
		$transient_keys = \get_option( 'crmservice_transient_keys', array() );

		foreach ( $transient_keys as $transient_key => $value ) {
			$deleted = \delete_transient( $transient_key );

			if ( $deleted ) {
				unset( $transient_keys[ $transient_key ] );
			}
		}

		\update_option( 'crmservice_transient_keys', $transient_keys );
	} // end purge_cache

	/**
	 *  Do full plugin reset.
	 *
	 *  @since  1.0.0
	 */
	public static function reset() {
		self::purge_cache();

		\delete_option( 'crmservice_api_baseurl' );
		\delete_option( 'crmservice_api_key' );
		\delete_option( 'crmservice_form_plugin' );

		$query = new \WP_Query( array(
			'post_type'   => 'crmservice_form',
			'post_status' => 'any',
			'posts_per_page'         => -1,
			'no_found_rows'          => true,
			'cache_results'          => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		) );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				\wp_delete_post( \get_the_id(), true );
			}
		}
	} // end reset

	public static function get_site_locale() {
		$locale = \get_locale();

		if ( 'fi' === $locale ) {
			$locale = 'fi_fi';
		}

		return $locale;
	} // end get_site_locale

	public static function check_contact_form_7_flamingo() {
		if ( ! class_exists( 'Flamingo_Contact' ) || ! class_exists( 'Flamingo_Inbound_Message' ) ) {
			return false;
		}

		return true;
	} // end check_contact_form_7_flamingo
} // end class Helper
