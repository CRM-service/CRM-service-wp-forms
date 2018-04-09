<?php
/**
 * Form plugin integration loader and common tasks.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 12:45:59
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-04-06 11:48:16
 *
 * @package crmservice
 */

namespace CRMServiceWP\Forms\Common;
use CRMServiceWP;
use CRMServiceWP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 *  Class for form plugin integration loader and common tasks.
 *
 *  @since 0.1.0-alpha
 */
class FormsCommon extends CRMServiceWP\Plugin {
	/**
	 *  Instance of helper.
	 *
	 *  @var resource
	 */
	protected static $helper;

	/**
	 *  Form plugin selected in settings.
	 *
	 *  @var resource
	 */
	protected static $form_plugin;

	/**
	 *  Instace for form plugin class.
	 *
	 *  @var resource
	 */
	protected static $form_plugin_instance;

	/**
	 *  Fire it up!
	 *
	 *  @since 0.1.0-alpha
	 */
	function __construct() {
		// Get instance of helper.
		self::$helper = new CRMServiceWP\Helper\Helper();

		// Hooks.
		\add_action( 'init', array( __CLASS__, 'add_rest_endpoints' ) );

		// Get form plugin selected and load it's integration class.
		self::$form_plugin = self::$helper->get_form_plugin();
		if ( self::$form_plugin ) {
			$load_slug = self::$form_plugin['slug'];
			include_once CRMServiceWP\Plugin::crmservice_base_path( "classes/form-plugins/{$load_slug}.php" );

			// Load instance for form spesific plugin.
			self::$form_plugin_instance = new CRMServiceWP\Forms\WPLibreForm\FormsWPLibreForm();

			// Hook to submit.
			\add_action( self::$form_plugin['submit_hook'], array( __CLASS__, 'send_form_submission' ), 50, 4 );
		}
	} // end __construct

	/**
	 *  Add our custom WP REST API endpoints.
	 *
	 *  @since 0.1.0-alpha
	 */
	public static function add_rest_endpoints() {
		\add_action( 'rest_api_init', function () {
			\register_rest_route( 'crmservice/v1', '/form/fields', array(
				'methods'							=> \WP_REST_Server::READABLE,
				'callback'						=> array( __CLASS__, 'get_form_fields_rest' ),
				'permission_callback' => function() {
					return \current_user_can( 'edit_posts' );
				}
			) );
		} );

		\add_action( 'rest_api_init', function () {
			\register_rest_route( 'crmservice/v1', '/module/fields', array(
				'methods'							=> \WP_REST_Server::READABLE,
				'callback'						=> array( __CLASS__, 'get_module_fields_rest' ),
				'permission_callback' => function() {
					return \current_user_can( 'edit_posts' );
				}
			) );
		} );
	} // end add_rest_endpoints

	/**
	 *  Get array of forms.
	 *  Key is form ID depending on selected form plugin, value is form title.
	 *
	 *  @since  0.1.0-alpha
	 *  @return array  list of forms
	 */
	public static function get_forms_array() {
		$forms = self::$form_plugin_instance->get_forms(); // get forms from form spesifi class.
		return $forms;
	} // end get_forms

	/**
	 *  Get fields for spesified form.
	 *
	 *  @since  0.1.0-alpha
	 *  @param  integer $form_id     form id to get fields.
	 *  @param  boolean $form_plugin form plugin, befaults to active plugin.
	 *  @return mixed                false if no fields, otherwise array of fields and required fields.
	 */
	public static function get_form_fields( $form_id = 0, $form_plugin = false ) {
		$form_id = intval( $form_id );
		$fields = self::$form_plugin_instance->get_fields( $form_id ); // get forms from form spesific class.

		// No fields, bail.
		if ( ! $fields ) {
			return false;
		}

		// No fields, bail.
		if ( ! $fields['fields'] ) {
			return false;
		}

		// Check if field is required and append that information to it's name.
		if ( $fields['required'] ) {
			foreach ( $fields['fields'] as $field_key => $field ) {
				if ( in_array( $field, $fields['required'] ) ) {
					$fields['fields'][ $field_key ] .= ' (' . esc_attr__( 'required', 'crmservice' ) . ')';
				}
			}
		}

		return $fields;
	} // end get_form_fields

	/**
	 *  Function for REST API endpoint to get form fields.
	 *
	 *  @since  0.1.0-alpha
	 *  @param 	object $request WP REST API request object.
	 *  @return mixed           REST API response
	 */
	public static function get_form_fields_rest( $request ) {
		$form_id = sanitize_text_field( $request->get_param( 'form' ) );

		// No form id, bail.
		if ( ! $form_id ) {
			return false;
		}

		return self::get_form_fields( $form_id );
	} // end get_form_fields_rest

	/**
	 *  Function to get CRM module fields.
	 *
	 *  @since  0.1.0-alpha
	 *  @param 	object $request WP REST API request object.
	 *  @return mixed           REST API response
	 */
	public static function get_module_fields( $module = null ) {
		// No form id, bail.
		if ( ! $module ) {
			return false;
		}

		return CRMServiceWP\API\API::call_api( 'getfields', $module, true, 300 );
	} // end get_form_fields_rest

	/**
	 *  Function for REST API endpoint to get CRM module fields.
	 *
	 *  @since  0.1.0-alpha
	 *  @param 	object $request WP REST API request object.
	 *  @return mixed           REST API response
	 */
	public static function get_module_fields_rest( $request ) {
		$module = sanitize_text_field( $request->get_param( 'module' ) );

		// No form id, bail.
		if ( ! $module ) {
			return false;
		}

		return self::get_module_fields( $module );
	} // end get_form_fields_rest

	public static function send_form_submission( $var1 = null, $var2 = null, $var3 = null, $var4 = null ) {
		$send_data = self::$form_plugin_instance->map_fields_for_send( $var1 );
		$send_module = self::$form_plugin_instance->get_module_for_send( $var1 );

		if ( ! $send_data ) {
			return true; // bail because no data, but true for form OK.
		}

		if ( ! $send_module ) {
			return true; // bail because no module, but true for form OK.
		}

		$send_result = CRMServiceWP\API\API::call_api( 'savedata', array( 'module' => $send_module, 'data' => $send_data ) );

		return true; // form OK
	} // end function send_form_submission
} // end class FormsCommon

new FormsCommon();
