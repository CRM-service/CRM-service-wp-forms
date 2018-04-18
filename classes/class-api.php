<?php
/**
 * API connector wrapper for plugin.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 12:45:59
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-04-18 11:42:13
 *
 * @package crmservice
 */

namespace CRMServiceWP\API;

use CRMServiceWP;
use CRMServiceWP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 *  Class for conector wrap.
 *
 *  @since 0.1.0-alpha
 */
class API extends CRMServiceWP\Plugin {

	/**
	 *  Instance of helper.
	 *
	 *  @var resource
	 */
	protected static $helper;

	/**
	 *  Base url for API.
	 *
	 *  @var resource
	 */
	private static $api_base_url;

	/**
	 *  Key for API.
	 *
	 *  @var resource
	 */
	private static $api_key;

	/**
	 *  Set variables
	 *
	 *  @since 0.0.1-alpha
	 */
	public function __construct() {
		// Get instance of helper.
		self::$helper = new CRMServiceWP\Helper\Helper();

		// Get API options.
		self::$api_base_url = get_option( 'crmservice_api_baseurl' );
		self::$api_key = get_option( 'crmservice_api_key' );
	} // end __construct

	/**
	 *  Load CRM SOAP connector.
	 *
	 *  @since  0.1.1-alpha
	 */
	private static function load_connector() {
		include_once CRMServiceWP\Plugin::crmservice_base_path( 'inc/connector.php' );
	} // end load_connector

	/**
	 *  TODO: remove this.
	 */
	public static function check_credentials_health() {
		return true;
	} // end check_credentials_health

	/**
	 *  Call CRM SOAP API.
	 *
	 *  @since  0.1.1-alpha
	 *  @param  string  $endpoint       [description]
	 *  @param  [type]  $data           [description]
	 *  @param  boolean $cache          [description]
	 *  @param  integer $cache_lifetime [description]
	 *  @param  string  $method         [description]
	 *  @return [type]                  [description]
	 */
	public static function call_api( $endpoint = 'getmodules', $data = null, $cache = true, $cache_lifetime = 900, $method = 'GET' ) {
		// Defaults.
		$cache_key = $endpoint;
		$api = null;

		// No cache for these methods.
		if ( 'POST' === $method || 'DELETE' === $method ) {
			$cache = false;
		}

		// No cache for these endpoints.
		if ( 'savedata' === $endpoint ) {
			$cache = false;
		}

		// Change cache key when there is parameters incuded in call.
		if ( 'getfields' === $endpoint ) {
			$cache_key = $endpoint . $data;
		}

		// Check that there's no response cached for this endpoint.
		if ( $cache ) {
			$data_from_cache = self::get_api_response_cache( $cache_key );

			if ( false !== $data_from_cache ) {
				return $data_from_cache;
			}
		}

		// Load API connector.
		self::load_connector();

		// Handle errors.
		try {
			$api = new \CRMservice\CRMserviceConnector( self::$api_base_url, self::$api_key );
		} catch ( \CRMservice\CRMserviceConnectorException $e ) {
			return false;
		}

		// Make request to get available modules.
		if ( 'savedata' === $endpoint ) {
			$data = $api->saveData( $data['module'], $data['data'] );
		} else if ( 'getfields' === $endpoint ) {
			$data = $api->getFieldsFor( $data, 'fi_fi' );
		} else {
			$data = $api->getModules();
		}

		// If response should be cached, cache it.
		if ( $cache ) {
			self::set_api_response_cache( $cache_key, $data, $cache_lifetime );
		}

		return $data;
	} // end call_api

	/**
	 *  Get cached API response data.
	 *
	 *  @since  0.1.1-alpha
	 *  @param  [type]  $cache_key [description]
	 *  @return [type]             [description]
	 */
	private static function get_api_response_cache( $cache_key ) {
		return get_transient( 'crmservice_api_response_' . md5( $cache_key ) );
	} // end get_api_response_cache

	/**
	 *  Save response for endpoint to cache
	 *
	 *  @since 0.0.1-alpha
	 *  @param string  $cache_key For what endpoint to cache response.
	 *  @param string  $data     Response data to cache.
	 *  @param integer $lifetime How long the response should be cached, defaults to 15 minutes.
	 */
	private static function set_api_response_cache( $cache_key = null, $data = null, $lifetime = 900 ) {
		if ( ! $cache_key || ! $data ) {
			return;
		}

		self::$helper->set_transient( 'crmservice_api_response_' . md5( $cache_key ), $data, $lifetime );
	} // end set_api_response_cache
} // end class API

new API();
