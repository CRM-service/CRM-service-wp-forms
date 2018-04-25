<?php
/**
 * Integration class for WP Libre Form.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 12:45:59
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-04-24 12:21:30
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
 *  @since 0.1.0-alpha
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
	 *  @since  0.1.0-alpha
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
	 *  @since  0.1.0-alpha
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
	 *  @since  0.1.0-alpha
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
	 *  @since  0.1.0-alpha
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
	 *  Set timestamp of succesfull crm send.
	 *
	 *  @since 0.1.1-alpha
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
	 *  @since 0.1.1-alpha
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
		$fails[] = date( 'Y-m-d H:i:s' );

		\update_post_meta( $wplf_data->submission_id, '_crmservice_send_fail', $fails );
	} // end set_send_fail
} // end class FormsWPLibreForm
