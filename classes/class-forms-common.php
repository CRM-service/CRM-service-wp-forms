<?php
/**
 * Form plugin integration loader and common tasks.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 12:45:59
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-04-20 11:20:41
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
	public function __construct() {
		// Get instance of helper.
		self::$helper = new CRMServiceWP\Helper\Helper();

		// Hooks.
		\add_action( 'init', array( __CLASS__, 'add_rest_endpoints' ) );

		// Get form plugin selected and load it's integration class.
		self::$form_plugin = self::$helper->get_form_plugin();
		if ( self::$form_plugin && self::$helper->check_if_form_plugin_active() ) {
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
				},
			) );
		} );

		\add_action( 'rest_api_init', function () {
			\register_rest_route( 'crmservice/v1', '/module/fields', array(
				'methods'							=> \WP_REST_Server::READABLE,
				'callback'						=> array( __CLASS__, 'get_module_fields_rest' ),
				'permission_callback' => function() {
					return \current_user_can( 'edit_posts' );
				},
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
		$form_id = \sanitize_text_field( $request->get_param( 'form' ) );

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
	 *  @param 	object $module WP REST API request object.
	 *  @return mixed           REST API response
	 */
	public static function get_module_fields( $module = null ) {
		// No form id, bail.
		if ( ! $module ) {
			return false;
		}

		return CRMServiceWP\API\API::call_api( 'getfields', $module, true, 300 );
	} // end get_form_fields_rest

	public static function get_integration_count_for_form( $form_id = null ) {
		return self::$form_plugin_instance->get_integration_count_for_form( $form_id );
	} // end get_integration_count_for_form

	/**
	 *  Function for REST API endpoint to get CRM module fields.
	 *
	 *  @since  0.1.0-alpha
	 *  @param 	object $request WP REST API request object.
	 *  @return mixed           REST API response
	 */
	public static function get_module_fields_rest( $request ) {
		$module = \sanitize_text_field( $request->get_param( 'module' ) );

		// No form id, bail.
		if ( ! $module ) {
			return false;
		}

		return self::get_module_fields( $module );
	} // end get_form_fields_rest

	/**
	 *  CRM-Service expects some fields in correct format, so do some formatting
	 *  if needed.
	 *
	 *  @since  0.1.1-alpha
	 *  @param  boolean $type  crm field type.
	 *  @param  string  $value field value.
	 *  @return mixed         formatted field value
	 */
	public static function maybe_format_field_data_for_crm( $type = false, $value = '' ) {
		if ( 'Date' === $type ) {
			if ( $time = strtotime( $value ) ) {
				return date( 'Y-m-d', $time );
			}
		} else if ( 'DateTime' === $type ) {
			if ( $time = strtotime( $value ) ) {
				return date( 'Y-m-d H:i:s', $time );
			}
		} else if ( 'Time' === $type ) {
			if ( $time = strtotime( $value ) ) {
				return date( 'H:i:s', $time );
			}
		} else if ( 'Checkbox' === $type ) {
			if ( empty( $value ) || 'false' === $value ) {
				return false;
			} else {
				return true;
			}
		}

		return $value;
	} // end maybe_format_field_data_for_crm

	/**
	 *  Send form submission to crm.
	 *
	 *  @since  0.1.1-alpha
	 *  @param  mixed  $var1 variable from form plugin hook.
	 *  @param  mixed  $var2 variable from form plugin hook.
	 *  @param  mixed  $var3 variable from form plugin hook.
	 *  @param  mixed  $var4 variable from form plugin hook.
	 *  @return boolean       always true to allow form plugins to continue
	 */
	public static function send_form_submission( $var1 = null, $var2 = null, $var3 = null, $var4 = null ) {
		$bail = false;
		$send_data = self::$form_plugin_instance->map_fields_for_send( $var1 );
		$send_module = self::$form_plugin_instance->get_module_for_send( $var1 );

		if ( ! $send_data ) {
			$bail = true; // bail because no data, but true for form OK.
		}

		if ( ! $send_module ) {
			$bail = true; // bail because no module, but true for form OK.
		}

		$module_fields = self::get_module_fields( $send_module );

		if ( ! $module_fields ) {
			$bail = true; // bail because no module fields, but true for form OK.
		}

		/**
		 *  Need to bail? Then do it and add timestamp of failed attempt to form meta.
		 */
		if ( $bail ) {
			self::$form_plugin_instance->set_send_fail( $var1 );
			return true;
		}

		// Maybe format field if needed.
		foreach ( $send_data as $key => $value ) {
			foreach ( $module_fields as $module_field_key => $module_field ) {
				if ( $key !== $module_field->name ) {
					continue;
				}

				$send_data[ $key ] = self::maybe_format_field_data_for_crm( $module_field->type, $value );
			}
		}

		// Do the send.
		$send_result = CRMServiceWP\API\API::call_api( 'savedata', array(
			'module'	=> $send_module,
			'data'		=> $send_data,
		) );

		// Send failed, add timestamp of failed attempt.
		if ( ! $send_result ) {
			self::$form_plugin_instance->set_send_fail( $var1 );
		}

		// Add timestamp of succesfull send.
		self::$form_plugin_instance->set_send_ok( $var1 );

		return true; // form OK
	} // end function send_form_submission
} // end class FormsCommon

new FormsCommon();
