<?php
/**
 * Helper functions for plugin.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 12:45:59
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-04-06 10:55:17
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
 *  @since 0.1.0-alpha
 */
class Helper extends CRMServiceWP\Plugin {
	/**
	 *  Check if API settings are saved to databse
	 *
	 *  @since  0.1.0-alpha
	 *  @return boolean  true if settings exist, false otherwise
	 */
	public static function check_api_settings_existance() {
		$api_account_id = \get_option( 'crmservice_api_baseurl' );
		$api_key = \get_option( 'crmservice_api_key' );

		if ( empty( $api_account_id ) || empty( $api_key ) ) {
			return false;
		}

		return true;
	} // end check_api_settings_existance

	/**
	 *  Get list of supported form plugins.
	 *
	 *  @since  0.1.0-alpha
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
			),
		);

		foreach ( $plugins as $key => $plugin ) {
			if ( \is_plugin_active( $plugin['dirfile'] ) ) {
				$plugins[ $key ]['active'] = true;
			} else {
				$plugins[ $key ]['name'] = $plugin['name'] . ' (' . \esc_attr__( 'deactivated', 'crmservice' ) . ')';
			}
		}

		return $plugins;
	} // end get_supported_form_plugins

	public function get_form_plugin() {
		$form_plugin = \get_option( 'crmservice_form_plugin' );
		$supported_plugins = self::get_supported_form_plugins();

		if ( empty( $form_plugin ) || ! array_key_exists( $form_plugin, $supported_plugins ) ) {
			return false;
		}

		return $supported_plugins[ $form_plugin ];
	} // end get_form_plugin

	/**
	 *  Add scheme to url if does not have already.
	 *
	 *  @since	0.1.0-alpha
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
	 *  @since  0.1.0-alpha
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
		 *  @param string  $key        key/name for transient
		 *  @param mixed   $value      value to save
		 *  @param integer $expiration how long this transient should exist, in seconds
		 */
		public static function set_transient( $key = null, $value = null, $expiration = 900 ) {
			add_option( 'crmservice_transient_keys', '', '', 'no' ); // in the name of not autoloading.

			$transient_keys = get_option( 'crmservice_transient_keys' );

			if ( set_transient( $key, $value, $expiration ) ) {
				$transient_keys[ $key ] = true;
				update_option( 'crmservice_transient_keys', $transient_keys );
				return true;
			}

			return false;
		} // end set_transient

		/**
		 *  Remove endpoint response from transient cache
		 *
		 *  @since 0.0.1-alpha
		 *  @param string  $key        key/name for transient
		 */
		public static function delete_transient( $key = null ) {
			$transient_keys = get_option( 'crmservice_transient_keys' );

			$delete = delete_transient( $key );
			if ( ! $delete ) {
				delete_transient( 'crmservice_api_response_' . md5( $key ) );
			}

			if ( $delete ) {
				unset( $transient_keys[ $key ] );
				update_option( 'crmservice_transient_keys', $transient_keys );
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
			$transient_keys = get_option( 'crmservice_transient_keys', array() );

			foreach ( $transient_keys as $transient_key => $value ) {
				$deleted = delete_transient( $transient_key );

				if ( $deleted ) {
					unset( $transient_keys[ $transient_key ] );
				}
			}

			update_option( 'crmservice_transient_keys', $transient_keys );
		} // end purge_cache
} // end class Helper
