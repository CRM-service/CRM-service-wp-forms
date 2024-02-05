<?php
/**
 * Form plugin integration loader and common tasks.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 12:45:59
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2023-05-09 11:12:55
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
 *  @since 1.0.0
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
	 *  @since 1.0.0
	 */
	public function __construct() {
		// Get instance of helper.
		self::$helper = new CRMServiceWP\Helper\Helper();

		// Hooks.
		\add_action( 'init', array( __CLASS__, 'add_rest_endpoints' ) );

		// Get form plugin selected and load it's integration class.
		self::$form_plugin = self::$helper->get_form_plugin();
		$form_plugin_active = self::$helper->check_if_form_plugin_active();

		if ( \is_admin() && isset( $_GET['action'] ) && isset( $_GET['post'] ) ) {
			if ( ! empty( $_GET['post'] ) && 'edit' === $_GET['action'] ) {
				$form_plugin = \get_post_meta( $_GET['post'], '_crmservice_form_plugin', true );

				if ( ! $form_plugin ) {
					return;
				}

				$form_plugins = self::$helper->get_supported_form_plugins();
				self::$form_plugin = $form_plugins[ $form_plugin ];
				$form_plugin_active = true;
			}
		}

		if ( self::$form_plugin && $form_plugin_active ) {
			$load_slug = self::$form_plugin['slug'];
			$file_path = CRMServiceWP\Plugin::crmservice_base_path( "classes/form-plugins/{$load_slug}.php" );

			if ( file_exists( $file_path ) ) {
				include_once CRMServiceWP\Plugin::crmservice_base_path( "classes/form-plugins/{$load_slug}.php" );

				// Load instance for form spesific plugin.
				self::$form_plugin_instance = new self::$form_plugin['class'];

				// Hook to submit.
				\add_action( self::$form_plugin['submit_hook'], array( __CLASS__, 'send_form_submission' ), 50, 4 );
				\add_action( 'crmservice_maybe_resend', array( __CLASS__, 'resend_failed_submissions' ) );
				\add_action( 'init', array( __CLASS__, 'resend_failed_submissions' ) );
			}
		}
	} // end __construct

	/**
	 *  Add our custom WP REST API endpoints.
	 *
	 *  @since 1.0.0
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
	 *  @since  1.0.0
	 *  @return array  list of forms
	 */
	public static function get_forms_array() {
    if ( ! self::$form_plugin ) {
      return [];
    }

		$forms = self::$form_plugin_instance->get_forms(); // get forms from form spesifi class.
		return $forms;
	} // end get_forms

	/**
	 *  Get fields for spesified form.
	 *
	 *  @since  1.0.0
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
	 *  @since  1.0.0
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
	 *  @since  1.0.0
	 *  @param 	object $module WP REST API request object.
	 *  @return mixed           REST API response
	 */
	public static function get_module_fields( $module = null ) {
		// No form id, bail.
		if ( ! $module ) {
			return false;
		}

		$fields = CRMServiceWP\API\API::call_api( 'getfields', $module, true, 300 );

		// alphabetic order by label
		usort( $fields, function( $a, $b ) {
			return strcmp( $a->label, $b->label );
		} );

		return $fields;
	} // end get_form_fields_rest

	public static function get_integration_count_for_form( $form_id = null ) {
		if ( ! $form_id ) {
			return false;
		}

		$query = new \WP_Query( array(
			'post_type'   						=> 'crmservice_form',
			'post_status' 						=> 'publish',
			'posts_per_page'         	=> 1,
			'meta_query'							=> array(
				'relation'	=> 'AND',
				array(
					'key'		=> '_crmservice_form',
					'value'	=> $form_id,
				),
				array(
					'key'			=> '_crmservice_module',
					'value'		=> '0',
					'compare'	=> '!=',
				),
			),
			'no_found_rows'          	=> false,
			'cache_results'          	=> true,
			'update_post_term_cache' 	=> false,
			'update_post_meta_cache' 	=> true,
		) );

		return $query->found_posts;
	} // end get_integration_count_for_form

	/**
	 *  Function for REST API endpoint to get CRM module fields.
	 *
	 *  @since  1.0.0
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
	 *  CRM-service expects some fields in correct format, so do some formatting
	 *  if needed.
	 *
	 *  @since  1.0.0
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
      // If field value is array and has only one checkbox value, let's use that as a value
      if ( is_array( $value ) && 1 === count( $value ) ) {
        $value = reset( $value );
      }

			if ( empty( $value ) || 'false' === $value ) {
				return false;
			} else {
				return true;
			}
		} else if ( 'Select' === $type ) {
      if ( is_array( $value ) ) {
        return reset( $value );
      }
    }

		return $value;
	} // end maybe_format_field_data_for_crm

	public static function get_module_for_send( $form_id = 0 ) {
		if ( ! $form_id ) {
			return false;
		}

		$query = new \WP_Query( array(
			'post_type'   						=> 'crmservice_form',
			'post_status' 						=> 'publish',
			'posts_per_page'         	=> 1,
			'meta_query'							=> array(
				'relation'	=> 'AND',
				array(
					'key'		=> '_crmservice_form',
					'value'	=> $form_id,
				),
				array(
					'key'			=> '_crmservice_module',
					'value'		=> '0',
					'compare'	=> '!=',
				),
			),
			'no_found_rows'          	=> true,
			'cache_results'          	=> true,
			'update_post_term_cache' 	=> false,
			'update_post_meta_cache' 	=> true,
		) );

		$module = false;
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) { $query->the_post();
				$module = \get_post_meta( get_the_id(), '_crmservice_module', true );
			}
		}

		return $module;
	} // end get_module_for_send

	public static function get_integration_field_connections( $form_id = 0 ) {
		if ( ! $form_id ) {
			return false;
		}

		$query = new \WP_Query( array(
			'post_type'   						=> 'crmservice_form',
			'post_status' 						=> 'publish',
			'posts_per_page'         	=> 1,
			'meta_query'							=> array(
				'relation'	=> 'AND',
				array(
					'key'		=> '_crmservice_form',
					'value'	=> $form_id,
				),
				array(
					'key'			=> '_crmservice_module',
					'value'		=> '0',
					'compare'	=> '!=',
				),
			),
			'no_found_rows'          	=> true,
			'cache_results'          	=> true,
			'update_post_term_cache' 	=> false,
			'update_post_meta_cache' 	=> true,
		) );

		$form_field_connections = false;
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) { $query->the_post();
				$form_field_connections = \get_post_meta( get_the_id(), '_crmservice_connections', true );
			}
		}

		return $form_field_connections;
	} // end get_integration_field_connections

	public static function get_prefilled_fields_for_send( $form_id = 0 ) {
		if ( ! $form_id ) {
			return false;
		}

		$query = new \WP_Query( array(
			'post_type'   						=> 'crmservice_form',
			'post_status' 						=> 'publish',
			'posts_per_page'         	=> 1,
			'meta_query'							=> array(
				'relation'	=> 'AND',
				array(
					'key'		=> '_crmservice_form',
					'value'	=> $form_id,
				),
				array(
					'key'			=> '_crmservice_module',
					'value'		=> '0',
					'compare'	=> '!=',
				),
			),
			'no_found_rows'          	=> true,
			'cache_results'          	=> true,
			'update_post_term_cache' 	=> false,
			'update_post_meta_cache' 	=> true,
		) );

		$fields = false;
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) { $query->the_post();
				$fields = \get_post_meta( get_the_id(), '_crmservice_static_fields', true );
			}
		}

		return $fields;
	} // end get_prefilled_fields_for_send

	/**
	 *  Send form submission to crm.
	 *
	 *  Vars per form plugin:
	 *
	 *  WP Libre From, see https://github.com/libreform/wp-libre-form/blob/master/inc/wplf-ajax.php#L95
	 *  var1 is object containing all submission data
	 *
	 *  Gravity Forms, see https://docs.gravityforms.com/gform_after_submission/
	 *  var1 is object containing submission entry
	 *  var2 is current form
	 *
	 *  Contact Form 7, see https://plugins.svn.wordpress.org/contact-form-7/trunk/modules/flamingo.php
	 *  var1 is array result of send
	 *
	 *  @since  1.0.0
	 *  @param  mixed  $var1 variable from form plugin hook.
	 *  @param  mixed  $var2 variable from form plugin hook.
	 *  @param  mixed  $var3 variable from form plugin hook.
	 *  @param  mixed  $var4 variable from form plugin hook.
	 *  @return boolean       always true to allow form plugins to continue
	 */
	public static function send_form_submission( $var1 = null, $var2 = null, $var3 = null, $var4 = null ) {
		$send_data = self::$form_plugin_instance->map_fields_for_send( $var1, $var2 );
		$send_module = self::$form_plugin_instance->get_module_for_send( $var1, $var2 );
		$prefilled_fields = self::$form_plugin_instance->get_prefilled_fields_for_send( $var1, $var2 );

		if ( ! $send_data ) {
			return true; // need to return true for form plugin to show ok
		}

		if ( ! $send_module ) {
			return true; // need to return true for form plugin to show ok
		}

		$module_fields = self::get_module_fields( $send_module );

		if ( ! $module_fields ) {
			return true; // need to return true for form plugin to show ok
		}

		// Maybe combine from data and prefilled fields
		if ( ! empty( $prefilled_fields ) ) {
			$send_data = array_merge( $prefilled_fields, $send_data );
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
 
		if ( ! $send_result ) {
			// Send failed, add timestamp of failed attempt.
			self::$form_plugin_instance->set_send_fail( $var1 );
		} else {
			// Add timestamp of succesfull send.
			self::$form_plugin_instance->set_send_ok( $var1 );
		}

		return true; // form OK
	} // end function send_form_submission

	/**
	 *  Try to resend failed submission sends to CRM.
	 *
	 *  @since  1.0.0
	 */
	public static function resend_failed_submissions() {
    if ( ! apply_filters( 'crmservice_forms_resend_failed_submissions', true ) ) {
      return; // resend disabled, bail.
    }

		// Get failed submissions.
		$failed_submissions = self::$form_plugin_instance->get_failed_submissions();

		if ( empty( $failed_submissions ) ) {
			return; // no failed ones, bail.
		}

		foreach ( $failed_submissions as $submission_id => $times ) {
			if ( 3 < count( $times ) ) {
				continue; // continue to next submission if failed more than three times.
			}

			// Get single submission for sending.
			$submission = self::$form_plugin_instance->get_submission( $submission_id );

			if ( ! $submission ) {
				continue; // can not get single submission, bail.
			}

			/**
			 *  Ensure that every single submission get has four array items.
			 *  Therse items are passed to send_form_submission function. By
			 *  doing this, we don't have to manage variable amount in every
			 *  form plugin integration class. Single place trick.
			 */
			$i = count( $submission );
			do {
			  $submission[] = null;
			  $i++;
			} while ( 4 > $i );

			// Aaaaand finally try to resend submission.
			self::send_form_submission( $submission[0], $submission[1], $submission[2], $submission[3] );
		}
	} // end resend_failed_submissions
} // end class FormsCommon

new FormsCommon();
