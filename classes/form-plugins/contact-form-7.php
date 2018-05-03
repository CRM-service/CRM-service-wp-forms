<?php
/**
 * Integration class for Contact Form 7.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 12:45:59
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-04-30 14:41:28
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
 *  @since 1.1.0-beta
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
	 *  @since  1.1.0-beta
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
	 *  @since  1.1.0-beta
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
	 *  @since  1.1.0-beta
	 *  @param  object $contact_form CF/ form object
	 *  @param  array $result result of send
	 *  @return mixed             array with mapped data or false
	 */
	public static function map_fields_for_send( $result = null ) {
		if ( ! $result ) {
			return false;
		}

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
	 *  @since  1.1.0-beta
	 *  @param  object $contact_form CF/ form object
	 *  @param  array $result result of send
	 *  @return mixed             module name for send, false if not configured.
	 */
	public static function get_module_for_send( $result = null ) {
		if ( ! $result ) {
			return false;
		}

		return CRMServiceWP\Forms\Common\FormsCommon::get_module_for_send( $result['contact_form_id'] );
	} // end get_module_for_send

	/**
	 *  Set timestamp of succesfull crm send.
	 *
	 *  @since 1.1.0-beta
	 */
	public static function set_send_ok( $result = null ) {
		if ( ! $result ) {
			return;
		}

		if ( ! isset( $result['flamingo_contact_id'] ) ) {
			return;
		}

		\update_post_meta( $result['flamingo_contact_id'], '_crmservice_send', date( 'Y-m-d H:i:s' ) );
	} // end set_send_ok

	/**
	 *  Save timestamp of failed crm send.
	 *
	 *  @since 1.1.0-beta
	 */
	public static function set_send_fail( $result = null ) {
		if ( ! $result ) {
			return;
		}

		if ( ! isset( $result['flamingo_contact_id'] ) ) {
			return;
		}

		// Get old fails if one.
		$fails = \get_post_meta( $result['flamingo_contact_id'], '_crmservice_send_fail', true );
		$fails[] = date( 'Y-m-d H:i:s' );

		\update_post_meta( $result['flamingo_contact_id'], '_crmservice_send_fail', $fails );
	} // end set_send_fail
} // end class GravityForms
