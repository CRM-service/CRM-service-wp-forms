<?php
/**
 * Register custom post type to hold integrations.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 12:45:59
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-04-06 10:47:20
 *
 * @package crmservice
 */

namespace CRMServiceWP\CPT;
use CRMServiceWP;
use CRMServiceWP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 *  Class for registering CPT where all integrations and settings for those are saved.
 *
 *  @since 0.1.0-alpha
 */
class CPT extends CRMServiceWP\Plugin {
	/**
	 *  Instance of helper.
	 *
	 *  @var resource
	 */
	protected static $helper;

	/**
	 *  Fire it up!
	 *
	 *  @since 0.1.0-alpha
	 */
	function __construct() {
		// Get instance of helper.
		self::$helper = new CRMServiceWP\Helper\Helper();

		// Ignition done, give some kick.
		self::run();
	} // end __construct

	/**
	 *  Add hooks.
	 *
	 *  @since  0.1.0-alpha
	 */
	protected function run() {
		\add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		\add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes_register' ) );
		\add_action( 'save_post', array( __CLASS__, 'meta_boxes_save' ) );
	} // end run

	/**
	 *  Register custom post type holding integrations.
	 *
	 *  @since  0.1.0-alpha
	 */
	public static function register_cpt() {
		$args = array(
			'labels'              => array(
				'name'               => \__( 'CRM-Service form integrations', 'crmservice' ),
				'singular_name'      => \__( 'CRM-Service for integration', 'crmservice' ),
				'add_new'            => \__( 'Add new form integration', 'crmservice' ),
				'add_new_item'       => \__( 'Add new form integration', 'crmservice' ),
				'edit_item'          => \__( 'Edit form integration', 'crmservice' ),
				'new_item'           => \__( 'New form integration', 'crmservice' ),
				'view_item'          => \__( 'View form integration', 'crmservice' ),
				'search_items'       => \__( 'Search form integrations', 'crmservice' ),
				'not_found'          => \__( 'No form integrations found', 'crmservice' ),
				'not_found_in_trash' => \__( 'No form integrations found in trash', 'crmservice' ),
				'parent_item_colon'  => \__( 'Parent form integration:', 'crmservice' ),
				'menu_name'          => \__( 'CRM-Service', 'crmservice' ),
			),
			'hierarchical'        => false,
			'description'         => 'CRM-Service <-> Form Plugins integration data holder',
			'taxonomies'          => array(),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => false,
			'menu_position'       => 80,
			'menu_icon'           => null,
			'show_in_nav_menus'   => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'query_var'           => false,
			'can_export'          => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'supports'            => array(
				'title',
				'revisions',
			),
		);

		\register_post_type( 'crmservice_form', $args );
	} // end register_cpt

	/**
	 *  Add our custom meta boxes for integration cpt.
	 *
	 *  @since  0.1.0-alpha
	 */
	public static function meta_boxes_register() {
		\add_meta_box( 'crmservice-integration-settings', __( 'Integration', 'crmservice' ), array( __CLASS__, 'meta_boxes_display_integration_settings' ), 'crmservice_form' );
	} // end meta_boxes_register

	/**
	 *  Output integration settings meta box.
	 *
	 *  @since  0.1.0-alpha
	 *  @param  int $post 		current post where metabox is shown
	 */
	public static function meta_boxes_display_integration_settings( $post ) {
		// Set defaults.
		$new_form_url = null;
		$form_fields = array();
		$conections = array();

		// Get forms for active form plugin.
		$forms = CRMServiceWP\Forms\Common\FormsCommon::get_forms_array();

		// Get available modules from CRM.
		$crm_modules = CRMServiceWP\API\API::call_api();

		// Get active form plugin.
		$form_plugin = self::$helper->get_form_plugin();
		if ( $form_plugin ) {
			$new_form_url = \admin_url( $form_plugin['new_url'] );
		}

		$saved_form = get_post_meta( (int)$post->ID, '_crmservice_form', true );
		$saved_module = get_post_meta( (int)$post->ID, '_crmservice_module', true );

		if ( $saved_form && $saved_module ) {
			$form_fields = CRMServiceWP\Forms\Common\FormsCommon::get_form_fields( (int)$saved_form );
			$module_fields = CRMServiceWP\Forms\Common\FormsCommon::get_module_fields( $saved_module );
			$saved_conections = get_post_meta( (int)$post->ID, '_crmservice_connections', true );
		}

		// Actually make the view.
		include_once CRMServiceWP\Plugin::crmservice_base_path( 'views/admin/metabox-integration-settings.php' );
	} // end meta_boxes_display_integration_settings

	public static function meta_boxes_save( $post_id ) {
		if ( ! isset( $_POST['crmservice_nonce'] ) ) {
			return;
		}

		if ( ! \wp_verify_nonce( $_POST['crmservice_nonce'], 'crmservice_integration_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! \current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['post_type'] ) && 'crmservice_form' !== $_POST['post_type'] ) {
			return;
		}

    if ( isset( $_POST['crmservice_form'] ) ) {
			update_post_meta( $post_id, '_crmservice_form', $_POST['crmservice_form'] );
		}

		$module = '';
		if ( isset( $_POST['crmservice_module'] ) ) {
			update_post_meta( $post_id, '_crmservice_module', $_POST['crmservice_module'] );
			$module = $_POST['crmservice_module']; // for the title.
		}

		if ( isset( $_POST['crmservice_connections'] ) ) {
			update_post_meta( $post_id, '_crmservice_connections', $_POST['crmservice_connections'] );
		}

		if ( empty( get_the_title( $post_id ) ) && isset( $_POST['crmservice_form'] ) ) {
			$forms = CRMServiceWP\Forms\Common\FormsCommon::get_forms_array();

			$form = '';
			if ( isset( $forms[ $_POST['crmservice_form'] ] ) ) {
				$form = $forms[ $_POST['crmservice_form'] ];
			}

			$post_title = $form;

			if ( $module ) {
				$post_title .= " to {$module}";
			}

			wp_update_post( array(
				'ID'					=> (int)$post_id,
				'post_title'	=> $post_title,
			) );
		}
	} // end meta_boxes_save
} // end class CPT

new CPT();
