<?php
/**
 * Integration class for Gravity Forms.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 12:45:59
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-04-25 15:09:51
 *
 * @package crmservice
 */

namespace CRMServiceWP\Forms\GravityForms;

use CRMServiceWP;
use CRMServiceWP\Helper;
use CRMServiceWP\Forms\Common;

// connector
use CRMservice;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 *  Integration class for Gravity Forms.
 *
 *  @since 1.1.0-beta
 */
class FormsGravityForms extends CRMServiceWP\Plugin {
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
	 *  Get forms from Gravity Forms.
	 *
	 *  @since  1.1.0-beta
	 *  @return array  list of forms
	 */
	public static function get_forms() {
		$forms = array();
		$gforms = \GFAPI::get_forms();

		if ( ! empty( $gforms ) ) {
			foreach ( $gforms as $form ) {
				$forms[ $form['id'] ] = $form['title'];
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
			return;
		}

		// Defaults.
		$fields = array();
		$required_fields = array();

		// Get form object, it contains fields also.
		$form = \GFAPI::get_form( $form_id );

		if ( \is_wp_error( $form ) ) {
			return; // no form, bail.
		}

		if ( ! empty( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field_id => $field ) {
				$fields[ $field->id ] = $field->label;

				if ( $field->isRequired ) {
					$required_fields[ $field->id ] = $field->label;
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
	 *  @param  array $entry submission data.
	 *  @param  array $form form data.
	 *  @return mixed             array with mapped data or false
	 */
	public static function map_fields_for_send( $entry = null, $form = null ) {
		if ( ! $entry ) {
			return false;
		}

		if ( ! $form ) {
			return false;
		}

		// Get submission ID for getting submission fields.
		$submission_id = (int)$entry['id'];

		// Get integration connections.
		$form_field_connections = CRMServiceWP\Forms\Common\FormsCommon::get_integration_field_connections( $form['id'] );

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

			if ( ! isset( $entry[ $connection['form_field'] ] ) ) {
				continue; // form field does not exist in submission, continue to next field.
			}

			// Connect fields.
			$send[ $connection['module_field'] ] = $entry[ $connection['form_field'] ];
		}

		return $send;
	} // end map_fields_for_send

	/**
	 *  Get module which we will use for send.
	 *
	 *  @since  1.1.0-beta
	 *  @param  array $entry submission data.
	 *  @param  array $form form data.
	 *  @return mixed             module name for send, false if not configured.
	 */
	public static function get_module_for_send( $entry = null, $form = null ) {
		if ( ! $form ) {
			return false;
		}

		return CRMServiceWP\Forms\Common\FormsCommon::get_module_for_send( $form['id'] );
	} // end get_module_for_send

	/**
	 *  Set timestamp of succesfull crm send.
	 *
	 *  @since 1.1.0-beta
	 *  @param  array $entry submission data.
	 */
	public static function set_send_ok( $entry ) {
		\gform_update_meta( (int)$entry['id'], '_crmservice_send', date( 'Y-m-d H:i:s' ) );
	} // end set_send_ok

	/**
	 *  Save timestamp of failed crm send.
	 *
	 *  @since 1.1.0-beta
	 *  @param  array $entry submission data.
	 */
	public static function set_send_fail( $entry ) {
		// Get old fails if one.
		$fails = \gform_get_meta( (int)$entry['id'], '_crmservice_send_fail' );
		$fails[] = date( 'Y-m-d H:i:s' );

		\gform_update_meta( (int)$entry['id'], '_crmservice_send_fail', $fails );
	} // end set_send_fail
} // end class GravityForms
