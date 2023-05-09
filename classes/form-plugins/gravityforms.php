<?php
/**
 * Integration class for Gravity Forms.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 12:45:59
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2023-05-05 11:07:16
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
 *  @since 1.0.0
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
	 *  @since  1.0.0
	 *  @return array  list of forms
	 */
	public static function get_forms() {
    if ( ! class_exists( 'GFAPI' ) ) {
      return [];
    }

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
	 *  @since  1.0.0
	 *  @param  integer $form_id for which form get fields.
	 *  @return mixed            array of fields or false if no form id or no fields
	 */
	public static function get_fields( $form_id = 0 ) {
		if ( ! $form_id ) {
			return;
		}

    if ( ! class_exists( 'GFAPI' ) ) {
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
				// skip sections
				if ( 'section' === $field['type'] ) {
					continue;
				}

				// if field has sub-fields, loop those
				if ( isset( $field['inputs'] ) && is_array( $field['inputs'] ) ) {
					foreach ( $field['inputs'] as $input_id => $input ) {
						// if sub-field is hidden (=not in use), skip it
						if ( isset( $input['isHidden'] ) && true === $input['isHidden'] ) {
							continue;
						}

						// add sub-field to fields, but prepend with main label
						$fields[ $input['id'] ] = array(
							'label' => $field->label . ': ' . $input['label'],
							'order'	=> $field_id . '.' . $input_id, // see few lines below for usort and it's comment
						);

						// if main is reauired, so is this sub-field
						if ( $field->isRequired ) {
							$required_fields[ $input['id'] ] = $field->label . ': ' . $input['label'];
						}
					}
				} else {
					// field has no sub-fields, add to array
					$fields[ (string) $field->id ] = array(
							'label' => $field->label,
							'order'	=> $field_id, // see few lines below for usort and it's comment
						);

					if ( $field->isRequired ) {
						$required_fields[ (string) $field->id ] = $field->label;
					}
				}
			}
		}

		/**
		 *  Sort fields in the order they appear in GF.
		 *  Field ID is just identifier and not trustworthy for setting order, so we need to
		 *  use the order that GF provided fields in.
		 */
		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field_id => $field ) {
				$fields[ $field_id ] = $field['label'];
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
	 *  @param  array $entry submission data.
	 *  @return mixed             array with mapped data or false
	 */
	public static function map_fields_for_send( $entry = null ) {
		if ( ! $entry ) {
			return false;
		}

		// Get submission ID for getting submission fields.
		$submission_id = (int)$entry['id'];
		$form_id = (int)$entry['form_id'];

		// Get integration connections.
		$form_field_connections = CRMServiceWP\Forms\Common\FormsCommon::get_integration_field_connections( $form_id );

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
	 *  @since  1.0.0
	 *  @param  array $entry submission data.
	 *  @return mixed             module name for send, false if not configured.
	 */
	public static function get_module_for_send( $entry = null, $form = null ) {
		if ( ! $entry ) {
			return false;
		}

		$form_id = (int)$entry['form_id'];

		return CRMServiceWP\Forms\Common\FormsCommon::get_module_for_send( $form_id );
	} // end get_module_for_send

	/**
	 *  Get pre-filled fields to send with form data.
	 *
	 *  @since  1.1.0
	 *  @param  object $contact_form CF/ form object
	 *  @param  array $result result of send
	 *  @return mixed             module name for send, false if not configured.
	 */
	public static function get_prefilled_fields_for_send( $entry = null, $form = null ) {
		if ( ! $entry ) {
			return false;
		}

		$form_id = (int)$entry['form_id'];

		return CRMServiceWP\Forms\Common\FormsCommon::get_prefilled_fields_for_send( $form_id );
	} // end get_prefilled_fields_for_send

	/**
	 *  Set timestamp of succesfull crm send.
	 *
	 *  @since 1.0.0
	 *  @param  array $entry submission data.
	 */
	public static function set_send_ok( $entry ) {
		\gform_update_meta( (int)$entry['id'], '_crmservice_send', date( 'Y-m-d H:i:s' ) );
	} // end set_send_ok

	/**
	 *  Save timestamp of failed crm send.
	 *
	 *  @since 1.0.0
	 *  @param  array $entry submission data.
	 */
	public static function set_send_fail( $entry ) {
		// Get old fails if one.
		$fails = \gform_get_meta( (int)$entry['id'], '_crmservice_send_fail' );
		if ( ! is_array( $fails ) ) {
			$fails = array();
		}

		$fails[] = date( 'Y-m-d H:i:s' );

		\gform_update_meta( (int)$entry['id'], '_crmservice_send_fail', $fails );
	} // end set_send_fail

	/**
	 *  Get submissions where CRM send failed.
	 *
	 *  @since  1.0.0
	 *  @return mixed  false or array with submission id as key and try times array as value
	 */
	public static function get_failed_submissions() {
    if ( ! class_exists( 'GFAPI' ) ) {
      return [];
    }

		$submissions = array();

		// Args for GF submission get.
		$search_criteria = array(
			'status'				=> 'active',
			'field_filters'	=> array(
				array(
					'key'				=> '_crmservice_send_fail',
					'value'			=> '',
					'operator'	=> 'isnot',
				),
        array(
          'key'       => '_crmservice_send',
          'value'     => '',
          'operator'  => 'is',
        )
			)
		);

		$paging	= array(
			'offset' 		=> 0,
			'page_size'	=> 999999999999, // Big int for getting all submissions, GF call is basically SQL query.
		);

		// Get failed submissions.
		$gf_submissions = \GFAPI::get_entries( 0, $search_criteria, array(), $paging ); // first var zero for all forms

		if ( \is_wp_error( $gf_submissions ) || empty( $gf_submissions ) ) {
			return false; // Can not get submissions, bail.
		}

		// Loop submissions.
		foreach ( $gf_submissions as $submission ) {
			$submission_id = (int)$submission['id'];

			// Get fail times and add to submission array.
			$fails = \gform_get_meta( $submission_id, '_crmservice_send_fail' );
			$submissions[ $submission_id ] = $fails;
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
		if ( ! $submission_id ) {
			return false; // No submission id, bail.
		}

		// Get submission.
		$submission = \GFAPI::get_entry( $submission_id );

		if ( \is_wp_error( $submission ) ) {
			return false; // Can not get submission, bail.
		}

   	return array(
   		$submission,
   	);
	} // end get_submission
} // end class GravityForms
