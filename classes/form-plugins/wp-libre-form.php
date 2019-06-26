<?php
/**
 * Integration class for WP Libre Form.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 12:45:59
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2019-05-27 12:42:40
 *
 * @package crmservice
 */

namespace CRMServiceWP\Forms\WPLibreForm;

use CRMServiceWP;
use CRMServiceWP\Helper;
use CRMServiceWP\Forms\Common;

// connector
use CRMservice;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 *  Integration class for WP Libre Form.
 *
 *  @since 1.0.0
 */
class FormsWPLibreForm extends CRMServiceWP\Plugin {
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
	 *  Get forms from WP Libre Form.
	 *
	 *  @since  1.0.0
	 *  @return array  list of forms
	 */
	public static function get_forms() {
		$forms = array();

		// WPLF forms are posts, so make query.
		$query_args = array(
			'post_type'								=> 'wplf-form',
			'post_status'							=> 'any',
			'order'										=> 'ASC',
			'orderby'             		=> 'title',
			'posts_per_page'					=> 100,
			'no_found_rows'						=> true,
			'cache_results'						=> true,
			'update_post_term_cache'	=> false,
			'update_post_meta_cache'	=> false,
		);

		$query = new \WP_Query( $query_args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) { $query->the_post();
				$form_title = \get_the_title();
				$form_id = \get_the_id();

				// If form has no title, use ID.
				$forms[ $form_id ] = ( ! empty( $form_title ) ) ? $form_title : sprintf( \esc_attr__( 'Form ID: %d', 'crmservice' ), $form_id );
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

		// Get form fields array, WPLF stores those in one string.
		$fields = \get_post_meta( $form_id, '_wplf_fields', true );
		$fields = explode( ',', $fields );

		// In WPLF field key and name is the same.
		foreach ( $fields as $field_key => $field ) {
			$fields[ $field ] = $field;
			unset( $fields[ $field_key ] );
		}

		// Get required fields.
		$required_fields = \get_post_meta( $form_id, '_wplf_required', true );

		return array(
			'fields'		=> $fields,
			'required'	=> explode( ',', $required_fields ),
		);
	} // end get_fields

	/**
	 *  Map submission data to selected fields for sending to CRM.
	 *
	 *  @since  1.0.0
	 *  @param  object $wplf_data WPLF submission data.
	 *  @return mixed             array with mapped data or false
	 */
	public static function map_fields_for_send( $wplf_data = null ) {
		if ( ! $wplf_data ) {
			return false; // no wplf data for some reason, bail.
		}

		if ( ! $wplf_data->ok ) {
			return false; // wplf send was not ok so we don't want to send either, bail.
		}

		// Get submission ID for getting submission fields.
		$submission_id = $wplf_data->submission_id;

		// Get integration connections.
		$form_field_connections = CRMServiceWP\Forms\Common\FormsCommon::get_integration_field_connections( $wplf_data->form_id );

		if ( ! $form_field_connections ) {
			return false; // no connections, bail.
		}

		// Get submission meta.
		$submission_meta = \get_post_meta( (int) $submission_id );
		$send = array();

		// Loop connections and connect the fieds.
		foreach ( $form_field_connections as $connection ) {
			if ( ! isset( $connection['module_field'] ) ) {
				continue; // no module field spesified, continue to next field.
			}

			if ( ! isset( $submission_meta[ $connection['form_field'] ] ) ) {
				continue; // form field does not exist in submission, continue to next field.
			}

			// Connect fields.
			$send[ $connection['module_field'] ] = $submission_meta[ $connection['form_field'] ][0];
		}

		return $send;
	} // end map_fields_for_send

	/**
	 *  Get module which we will use for send.
	 *
	 *  @since  1.0.0
	 *  @param  object $wplf_data WPLF submission data.
	 *  @return mixed             module name for send, false if not configured.
	 */
	public static function get_module_for_send( $wplf_data = null ) {
		if ( ! $wplf_data ) {
			return false; // no wplf data for some reason, bail.
		}

		if ( ! $wplf_data->ok ) {
			return false; // wplf send was not ok so we don't want to send either, bail.
		}

		// Get and return module.
		return CRMServiceWP\Forms\Common\FormsCommon::get_module_for_send( $wplf_data->form_id );
	} // end get_module_for_send

	/**
	 *  Get pre-filled fields to send with form data.
	 *
	 *  @since  1.1.0
	 *  @param  object $contact_form CF/ form object
	 *  @param  array $result result of send
	 *  @return mixed             module name for send, false if not configured.
	 */
	public static function get_prefilled_fields_for_send( $wplf_data = null ) {
		if ( ! $wplf_data ) {
			return false; // no wplf data for some reason, bail.
		}

		if ( ! $wplf_data->ok ) {
			return false; // wplf send was not ok so we don't want to send either, bail.
		}

		// Get and return module.
		return CRMServiceWP\Forms\Common\FormsCommon::get_prefilled_fields_for_send( $wplf_data->form_id );
	} // end get_prefilled_fields_for_send

	/**
	 *  Set timestamp of succesfull crm send.
	 *
	 *  @since 1.0.0
	 *  @param object $wplf_data WPLF submission data.
	 */
	public static function set_send_ok( $wplf_data = null ) {
		if ( ! $wplf_data ) {
			return false; // no wplf data for some reason, bail.
		}

		if ( ! $wplf_data->ok ) {
			return false; // wplf send was not ok so we don't want to send either, bail.
		}

		\update_post_meta( $wplf_data->submission_id, '_crmservice_send', date( 'Y-m-d H:i:s' ) );
	} // end set_send_ok

	/**
	 *  Save timestamp of failed crm send.
	 *
	 *  @since 1.0.0
	 *  @param object $wplf_data WPLF submission data.
	 */
	public static function set_send_fail( $wplf_data = null ) {
		if ( ! $wplf_data ) {
			return false; // no wplf data for some reason, bail.
		}

		if ( ! $wplf_data->ok ) {
			return false; // wplf send was not ok so we don't want to send either, bail.
		}

		// Get old fails if one.
		$fails = \get_post_meta( $wplf_data->submission_id, '_crmservice_send_fail', true );
		if ( ! is_array( $fails ) ) {
			$fails = array();
		}

		$fails[] = date( 'Y-m-d H:i:s' );

		\update_post_meta( $wplf_data->submission_id, '_crmservice_send_fail', $fails );
	} // end set_send_fail

	/**
	 *  Get submissions where CRM send failed.
	 *
	 *  @since  1.0.0
	 *  @return mixed  false or array with submission id as key and try times array as value
	 */
	public static function get_failed_submissions() {
		$submissions = array();

		// WPLF forms are posts, so make query.
		$query_args = array(
			'post_type'								=> 'wplf-submission',
			'post_status'							=> 'publish',
			'posts_per_page'					=> -1,
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
			'no_found_rows'						=> true,
			'cache_results'						=> true,
			'update_post_term_cache'	=> false,
			'update_post_meta_cache'	=> false,
		);

		$query = new \WP_Query( $query_args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) { $query->the_post();
				$submission_id = \get_the_id();

				// Get fail times and add to submission array.
				$failed_submissions = \get_post_meta( $submission_id, '_crmservice_send_fail', true );
				$submissions[ $submission_id ] = $failed_submissions;
			}
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

		// Make fake submission.
		$submission = new \stdClass();
   	$submission->ok = 1;
   	$submission->submission_id = $submission_id;
   	$submission->form_id = (int) \get_post_meta( $submission_id, '_form_id', true );

   	return array(
   		$submission,
   	);
	} // end get_submission
} // end class FormsWPLibreForm
