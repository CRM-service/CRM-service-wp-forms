<?php
/**
 * Integration class for Contact Form 7.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 12:45:59
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2021-12-09 15:04:05
 *
 * @package crmservice
 */

namespace CRMServiceWP\Forms\ContactForm7;

use CRMServiceWP;
use CRMServiceWP\Helper;
use CRMServiceWP\Forms\Common;

// connector
use CRMservice;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 *  Integration class for Contact Form 7.
 *
 *  @since 1.0.0
 */
class FormsContactForm7 extends CRMServiceWP\Plugin {
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
	 *  Get forms from Contact Form 7.
	 *
	 *  @since  1.0.0
	 *  @return array  list of forms
	 */
	public static function get_forms() {
		$forms = array();
		$cf7forms = \WPCF7_ContactForm::find();

		if ( ! empty( $cf7forms ) ) {
			foreach ( $cf7forms as $form ) {
				$forms[ $form->id() ] = $form->title();
			}
		}

		return $forms;
	} // end function get_forms

	/**
	 *  Get fields for specified form.
	 *
	 *  @since  1.0.0
	 *  @param  integer $form_id for which form get fields.
	 *  @return mixed            array of fields or false if no form id or no fields
	 */
	public static function get_fields( $form_id = 0 ) {
		if ( ! $form_id ) {
			return false;
		}

		// Defaults.
		$fields = array();
		$required_fields = array();

		// Get form and scan it for tags (=fields).
		$contact_form = \WPCF7_ContactForm::get_instance( $form_id );
		$contact_form_fields = $contact_form->scan_form_tags();

		if ( ! empty( $contact_form_fields ) ) {
			foreach ( $contact_form_fields as $contact_form_field ) {
				if ( empty( $contact_form_field->name ) ) {
					continue; // field has no name, continue to next.
				}

				// Add field to return.
				$fields[ $contact_form_field->name ] = $contact_form_field->name;

				// Check if field is required and add it to required list.
				$field = new \WPCF7_FormTag( $contact_form_field );
				if ( $field->is_required() ) {
					$required_fields[ $contact_form_field->name ] = $contact_form_field->name;
				}
			}
		}

		return array(
			'fields'		=> $fields,
			'required'	=> $required_fields,
		);
	} // end get_fields

	/**
	 *  Map submission data to selected fields for sending to CRM.
	 *
	 *  @since  1.0.0
	 *  @param  object $contact_form CF/ form object
	 *  @param  array $result result of send
	 *  @return mixed             array with mapped data or false
	 */
	public static function map_fields_for_send( $var1 = false, $var2 = falsel ) {
		$result = self::_get_result_var( $var1, $var2 );
		if ( ! $result ) {
			return false;
		}

		// Check if this is submission resend call.
		if ( ! isset( $result['resend'] ) ) {
			// Filter cases where we should proceed with sending data to CRM.
			$cases = (array) \apply_filters( 'wpcf7_flamingo_submit_if', array( 'mail_sent', 'mail_failed' ) );
			$cases = (array) \apply_filters( 'wpcf7_crmservice_submit_if', array( 'mail_sent', 'mail_failed' ) );

			if ( empty( $result['status'] ) || ! in_array( $result['status'], $cases ) ) {
				return; // submit status is not in proceed with send list, bail.
			}

			// Get submission.
			$submission = \WPCF7_Submission::get_instance();

			if ( ! $submission || ! $posted_data = $submission->get_posted_data() ) {
				return; // something odd with submission, bail.
			}
		} else {
			// Okay, was a resend call. Get the data for handling.
			$result = $result['data'][0];

			// Get fields data.
			$posted_data = $result->fields;

			// Get form.
			$form = \WPCF7_ContactForm::find( array( 'name' => $result->channel ) );
			if ( empty( $form ) ) {
				return; // Can not get form, bail.
			}

			// Make fake result.
			$result = array();
			$result['contact_form_id'] = $form[0]->id();
		}

		// Get integration connections.
		$form_field_connections = CRMServiceWP\Forms\Common\FormsCommon::get_integration_field_connections( $result['contact_form_id'] );

		if ( ! $form_field_connections ) {
			return false; // no connections, bail.
		}

		// Defaults.
		$send = array();

		// Loop connections and connect the fieds.
		foreach ( $form_field_connections as $connection ) {
			if ( ! isset( $connection['module_field'] ) ) {
				continue; // no module field spesified, continue to next field.
			}

			if ( ! isset( $posted_data[ $connection['form_field'] ] ) ) {
				continue; // form field does not exist in submission, continue to next field.
			}

			// Connect fields.
			$send[ $connection['module_field'] ] = $posted_data[ $connection['form_field'] ];
		}

		return $send;
	} // end map_fields_for_send

	/**
	 *  Get module which we will use for send.
	 *
	 *  @since  1.0.0
	 *  @param  object $contact_form CF/ form object
	 *  @param  array $result result of send
	 *  @return mixed             module name for send, false if not configured.
	 */
	public static function get_module_for_send( $var1 = false, $var2 = false ) {
		$result = self::_get_result_var( $var1, $var2 );
		if ( ! $result ) {
			return false;
		}

		// If is resend call.
		if ( isset( $result['resend'] ) ) {
			// Okay, was a resend call. Get the data for handling.
			$result = $result['data'][0];

			// Get form.
			$form = \WPCF7_ContactForm::find( array( 'name' => $result->channel ) );
			if ( empty( $form ) ) {
				return; // Can not get form, bail.
			}

			// Make fake result.
			$result = array();
			$result['contact_form_id'] = $form[0]->id();
		}

		return CRMServiceWP\Forms\Common\FormsCommon::get_module_for_send( $result['contact_form_id'] );
	} // end get_module_for_send

	/**
	 *  Get pre-filled fields to send with form data.
	 *
	 *  @since  1.1.0
	 *  @param  object $contact_form CF/ form object
	 *  @param  array $result result of send
	 *  @return mixed             module name for send, false if not configured.
	 */
	public static function get_prefilled_fields_for_send( $var1 = false, $var2 = false ) {
		$result = self::_get_result_var( $var1, $var2 );
		if ( ! $result ) {
			return false;
		}

		// If is resend call.
		if ( isset( $result['resend'] ) ) {
			// Okay, was a resend call. Get the data for handling.
			$result = $result['data'][0];

			// Get form.
			$form = \WPCF7_ContactForm::find( array( 'name' => $result->channel ) );
			if ( empty( $form ) ) {
				return; // Can not get form, bail.
			}

			// Make fake result.
			$result = array();
			$result['contact_form_id'] = $form[0]->id();
		}

		return CRMServiceWP\Forms\Common\FormsCommon::get_prefilled_fields_for_send( $result['contact_form_id'] );
	} // end get_prefilled_fields_for_send

	/**
	 *  Set timestamp of succesfull crm send.
	 *
	 *  @since 1.0.0
	 */
	public static function set_send_ok( $var1 = false, $var2 = false ) {
		$result = self::_get_result_var( $var1, $var2 );
		if ( ! $result ) {
			return;
		}

		// If is resend call.
		if ( isset( $result['resend'] ) ) {
			// Okay, was a resend call. Get the data for handling.
			$result = $result['data'][0];

			// Make feke result.
			$new_result = array();
			$new_result['flamingo_inbound_id'] = $result->id;
			$result = $new_result;
		}

		if ( ! isset( $result['flamingo_inbound_id'] ) ) {
			return;
		}

		\update_post_meta( $result['flamingo_inbound_id'], '_crmservice_send', date( 'Y-m-d H:i:s' ) );
	} // end set_send_ok

	/**
	 *  Save timestamp of failed crm send.
	 *
	 *  @since 1.0.0
	 */
	public static function set_send_fail( $var1 = false, $var2 = false ) {
		$result = self::_get_result_var( $var1, $var2 );
		if ( ! $result ) {
			return;
		}

		// If is resend call.
		if ( isset( $result['resend'] ) ) {
			// Okay, was a resend call. Get the data for handling.
			$result = $result['data'][0];

			// Make feke result.
			$new_result = array();
			$new_result['flamingo_inbound_id'] = $result->id;
			$result = $new_result;
		}

		// Get old fails if one.
		$fails = \get_post_meta( $result['flamingo_inbound_id'], '_crmservice_send_fail', true );
		if ( ! is_array( $fails ) ) {
			$fails = array();
		}

		$fails[] = date( 'Y-m-d H:i:s' );

		\update_post_meta( $result['flamingo_inbound_id'], '_crmservice_send_fail', $fails );
	} // end set_send_fail

	/**
	 *  Get submissions where CRM send failed.
	 *
	 *  @since  1.0.0
	 *  @return mixed  false or array with submission id as key and try times array as value
	 */
	public static function get_failed_submissions() {
		if ( ! class_exists( 'Flamingo_Contact' ) || ! class_exists( 'Flamingo_Inbound_Message' ) ) {
			return false; // No flamingo, bail.
		}

		// Defaults.
		$submissions = array();

		// Get failed submissions.
		$cf7_submissions = \Flamingo_Inbound_Message::find( array(
			'posts_per_page'					=> 500,
      'meta_key'                => '_crmservice_send_fail',
			'meta_query'							=> array(
				'relation'	=> 'AND',
				array(
					'key'			=> '_crmservice_send_fail',
				),
				array(
					'key'			=> '_crmservice_send',
					'compare'	=> 'NOT EXISTS',
				),
			),
		) );

		// Loop failed and get try times.
		foreach ( $cf7_submissions as $submission ) {
			$failed_submissions = \get_post_meta( $submission->id(), '_crmservice_send_fail', true );
			$submissions[ $submission->id() ] = $failed_submissions;
		}

		return $submissions;
	} // end get_failed_submissions

	/**
	 *  Get single submission.
	 *
	 *  @since  1.0.0
	 *  @param  integer $submission_id submission id to get
	 *  @return mixed                  false of array containing resend nag and submission data
	 */
	public static function get_submission( $submission_id = null ) {
		if ( ! class_exists( 'Flamingo_Contact' ) || ! class_exists( 'Flamingo_Inbound_Message' ) ) {
			return false; // No flamingo, bail.
		}

		if ( ! $submission_id ) {
			return false; // No submission id, bail.
		}

		// Get submission.
   	$submission = \Flamingo_Inbound_Message::find( array(
   		'post_id'	=> $submission_id,
   	) );

   	if ( empty( $submission ) ) {
   		return false; // Can not get submission, bail.
   	}

   	// Return array containing info that is resend and submission data.
   	return array(
   		array(
   			'resend'	=> true,
   			'data'		=> $submission,
   		),
   	);
	} // end get_submission

	/**
	 *  Get CF7 / Flamingo result for submission handling,
	 *  veriable depends on submit hook which depends on
	 *  if Flamingo is active.
	 *
	 *  If Flamingo is active, var1 is $result. Without
	 *  Flamingo, var2 is $result. $result is what we want
	 *  to pass for handling.
	 *
	 *  @since  1.0.0
	 */
	private static function _get_result_var( $var1 = false, $var2 = false ) {
		if ( ! class_exists( 'Flamingo_Contact' ) || ! class_exists( 'Flamingo_Inbound_Message' ) ) {
			return $var2;
		}

		return $var1;
	} // end _get_result_var
} // end class GravityForms
