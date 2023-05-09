<?php
/**
 * Register custom post type to hold integrations.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 12:45:59
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2023-05-05 11:05:09
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
 *  @since 1.0.0
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
	 *  @since 1.0.0
	 */
	public function __construct() {
		// Get instance of helper.
		self::$helper = new CRMServiceWP\Helper\Helper();

		// Ignition done, give some kick.
		self::run();
	} // end __construct

	/**
	 *  Add hooks.
	 *
	 *  @since  1.0.0
	 */
	protected function run() {
		\add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		\add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes_register' ) );
		\add_action( 'save_post', array( __CLASS__, 'meta_boxes_save' ) );

    \add_filter( 'manage_edit-crmservice_form_columns', array( __CLASS__, 'custom_columns_cpt' ), 100, 1 );
    \add_action( 'manage_crmservice_form_posts_custom_column', array( __CLASS__, 'custom_columns_display_cpt' ), 10, 2 );

    \add_action( 'current_screen', array( __CLASS__, 'maybe_disable_adding_new' ) );
    \add_action( 'admin_notices', array( __CLASS__, 'maybe_show_admin_notices' ) );
	} // end run

	/**
	 *  Register custom post type holding integrations.
	 *
	 *  @since  1.0.0
	 */
	public static function register_cpt() {
		$args = array(
			'labels'              => array(
				'name'               => \__( 'CRM-service form integrations', 'crmservice' ),
				'singular_name'      => \__( 'CRM-service for integration', 'crmservice' ),
				'add_new'            => \__( 'Add new form integration', 'crmservice' ),
				'add_new_item'       => \__( 'Add new form integration', 'crmservice' ),
				'edit_item'          => \__( 'Edit form integration', 'crmservice' ),
				'new_item'           => \__( 'New form integration', 'crmservice' ),
				'view_item'          => \__( 'View form integration', 'crmservice' ),
				'search_items'       => \__( 'Search form integrations', 'crmservice' ),
				'not_found'          => \__( 'No form integrations found', 'crmservice' ),
				'not_found_in_trash' => \__( 'No form integrations found in trash', 'crmservice' ),
				'parent_item_colon'  => \__( 'Parent form integration:', 'crmservice' ),
				'menu_name'          => \__( 'CRM-service', 'crmservice' ),
			),
			'hierarchical'        => false,
			'description'         => 'CRM-service <-> Form Plugins integration data holder',
			'taxonomies'          => array(),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => false,
			'menu_position'       => 80,
			'menu_icon'           => \plugins_url( 'assets/admin/icon.png', __DIR__ ),
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
	 *  @since  1.0.0
	 */
	public static function meta_boxes_register() {
		\add_meta_box( 'crmservice-integration-settings', __( 'Integration', 'crmservice' ), array( __CLASS__, 'meta_boxes_display_integration_settings' ), 'crmservice_form' );
	} // end meta_boxes_register

	/**
	 *  Output integration settings meta box.
	 *
	 *  @since  1.0.0
	 *  @param  int $post 		current post where metabox is shown.
	 */
	public static function meta_boxes_display_integration_settings( $post ) {
		// Set defaults.
		$new_form_url = null;
		$form_fields = array();
		$conections = array();

    $screen = \get_current_screen();

    if ( 'post' === $screen->base && 'crmservice_form' === $screen->post_type && isset( $_GET['post'] ) ) {
      $form_plugin = \get_post_meta( (int) \sanitize_text_field( \wp_unslash( $_GET['post'] ) ), '_crmservice_form_plugin', true );
      if ( $form_plugin !== self::$helper->get_form_plugin( true ) ) {
        return;
      }
    }

		// Get forms for active form plugin.
		$forms = CRMServiceWP\Forms\Common\FormsCommon::get_forms_array();

		// Get available modules from CRM.
		$crm_modules = CRMServiceWP\API\API::call_api();

		// Get active form plugin.
		$form_plugin = self::$helper->get_form_plugin();
		$form_plugin_active = self::$helper->check_if_form_plugin_active();
		if ( $form_plugin ) {
			$new_form_url = \admin_url( $form_plugin['new_url'] );
		}

		$saved_form = get_post_meta( (int) $post->ID, '_crmservice_form', true );
		$saved_module = get_post_meta( (int) $post->ID, '_crmservice_module', true );

		if ( $saved_form && $saved_module ) {
			$form_fields = CRMServiceWP\Forms\Common\FormsCommon::get_form_fields( (int) $saved_form );
			$module_fields = CRMServiceWP\Forms\Common\FormsCommon::get_module_fields( $saved_module );
			$saved_conections = get_post_meta( (int) $post->ID, '_crmservice_connections', true );
			$saved_static_fields = get_post_meta( (int) $post->ID, '_crmservice_static_fields', true );
		}

		// Actually make the view.
		include_once CRMServiceWP\Plugin::crmservice_base_path( 'views/admin/metabox-integration-settings.php' );
	} // end meta_boxes_display_integration_settings

	/**
	 *  Handle saving of our CPT and it's metadata
	 *
	 *  @since  1.0.0
	 *  @param  integer  $post_id post ID which is being saved.
	 */
	public static function meta_boxes_save( $post_id ) {
		if ( isset( $_POST['post_type'] ) && 'crmservice_form' !== $_POST['post_type'] ) {
			return; // bail if post type isn't ours.
		}

		if ( ! isset( $_POST['crmservice_nonce'] ) ) {
			return; // bail if no nonce.
		}

		if ( ! \wp_verify_nonce( wp_unslash( $_POST['crmservice_nonce'] ), 'crmservice_integration_nonce' ) ) { // phpcs:ignore WordPress.VIP.ValidatedSanitizedInput.InputNotSanitized
			return; // bail if invalid nonce.
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return; // bail if is autosave.
		}

		if ( ! \current_user_can( 'edit_post', $post_id ) ) {
			return; // bail if user has no rights.
		}

		// Save current plugin version to meta.
		\update_post_meta( $post_id, '_crmservice_plugin_version', CRMSERVICEWP_VERSION );

		// Save current form plugin configuration to meta.
		if ( isset( $_POST['crmservice_form'] ) && \get_post_meta( $post_id, '_crmservice_form', true ) !== $_POST['crmservice_form'] ) {
			\update_post_meta( $post_id, '_crmservice_form_plugin', self::$helper->get_form_plugin( true ) );
		}

		// Save selected form.
    if ( isset( $_POST['crmservice_form'] ) ) {
			\update_post_meta( $post_id, '_crmservice_form', \sanitize_text_field( \wp_unslash( $_POST['crmservice_form'] ) ) );
		}

		// Save selected module.
		$module = '';
		if ( isset( $_POST['crmservice_module'] ) ) {
			\update_post_meta( $post_id, '_crmservice_module', \sanitize_text_field( \wp_unslash( $_POST['crmservice_module'] ) ) );
		}

		// Save added field connections.
		if ( isset( $_POST['crmservice_connections'] ) ) {
			\update_post_meta( $post_id, '_crmservice_connections', \wp_unslash( $_POST['crmservice_connections'] ) );
		}

		// Save added field connections.
		if ( isset( $_POST['crmservice_static_fields'] ) && isset( $_POST['crmservice_static_fields_values'] ) ) {
			$crmservice_static_fields = array_combine( \wp_unslash( $_POST['crmservice_static_fields'] ), \wp_unslash( $_POST['crmservice_static_fields_values'] ) );
			\update_post_meta( $post_id, '_crmservice_static_fields', array_filter( $crmservice_static_fields ) );
		}

		// Set post title to random int if no title otherwise.
		if ( empty( \get_the_title( $post_id ) ) ) {
			\wp_update_post( array(
				'ID'					=> (int) $post_id,
				'post_title'	=> mt_rand( 100, 999 ),
			) );
		}
	} // end meta_boxes_save

	/**
	 * Custom columns in edit.php for Forms.
	 *
	 * @since 1.0.0
	 */
  public static function custom_columns_cpt( $columns ) {
		$new_columns = array(
		'cb'					=> $columns['cb'],
		'status'			=> '',
		'title'				=> $columns['title'],
		'form'				=> \__( 'Form', 'crmservice' ),
		'module'			=> \__( 'Module', 'crmservice' ),
		'date'				=> $columns['date'],
		);

		return $new_columns;
  } // end custom_columns_cpt

  /**
   * Custom column display for Form CPT in edit.php.
   *
   * @since 1.0.0
   */
  public static function custom_columns_display_cpt( $column, $post_id ) {
  	// Status column cotent.
		if ( 'status' === $column ) {
			// Defaults.
			$status = 'ok';
			$status_message = array();

			// Get data for this integration.
			$form_plugin = \get_post_meta( $post_id, '_crmservice_form_plugin', true );
			$form = \get_post_meta( $post_id, '_crmservice_form', true );
			$module = \get_post_meta( $post_id, '_crmservice_module', true );
			$connections = \get_post_meta( $post_id, '_crmservice_connections', true );

			// Check if there is more than one integrations for this form.
			if ( ! empty( $form ) ) {
				if ( 1 < CRMServiceWP\Forms\Common\FormsCommon::get_integration_count_for_form( $form ) ) {
					$status = 'warn';
					$status_message[] = \__( 'Found multiple integrations for a same form', 'crmservice' );
				}
			}

			// Check if form plugin is same than configured.
			if ( $form_plugin !== self::$helper->get_form_plugin( true ) ) {
				$status = 'error';
				$status_message[] = \__( 'Integration uses different form plugin that is configured in settings', 'crmservice' );
			}

			// Check that the integration is configured.
			if ( empty( $form ) || empty( $module ) || empty( $connections ) ) {
				$status = 'error';
				$status_message[] = \__( 'Integration not configured', 'crmservice' );
			}

			// Select status message to show in indicator tooltip.
			if ( empty( $status_message ) ) {
				$status_message = \__( 'Integration is working correctly', 'crmservice' );
			} else {
				$status_message = ucfirst( mb_strtolower( implode( ', ', $status_message ) ) );
			}

			// Show indicator and tooltip.
			echo '<span class="status status-' . $status . '" title="' . $status_message . '"></span>';
		}

		// Form column content.
		if ( 'form' === $column ) {
			$form = \get_post_meta( $post_id, '_crmservice_form', true );
			$forms = CRMServiceWP\Forms\Common\FormsCommon::get_forms_array();

			if ( $form && isset( $forms[ $form ] ) ) {
				echo $forms[ $form ];
			}
		}

		// Module column content.
		if ( 'module' === $column ) {
			$module = \get_post_meta( $post_id, '_crmservice_module', true );

			if ( $module ) {
				echo $module;
			}
		}
  } // end custom_columns_display_cpt

  /**
   *  Disable adding new integration if form plugin not configured.
   *
   *  @since  0.1.1.-alpha
   */
  public static function maybe_disable_adding_new() {
  	$screen = \get_current_screen();
  	if ( 'add' === $screen->action && 'crmservice_form' === $screen->post_type && ! self::$helper->get_form_plugin() ) {
  		wp_safe_redirect( self::$helper->get_plugin_page_url() );
  	}
  } // end maybe_disable_adding_new

	/**
	 *  If integration uses different form plugin tha configured, show admin notice.
	 *
	 *  @since  1.0.0
	 */
	public static function maybe_show_admin_notices() {
		$screen = \get_current_screen();

		if ( 'post' === $screen->base && 'crmservice_form' === $screen->post_type && isset( $_GET['post'] ) ) {
			$form_plugin = \get_post_meta( (int) \sanitize_text_field( \wp_unslash( $_GET['post'] ) ), '_crmservice_form_plugin', true );

	    if ( $form_plugin !== self::$helper->get_form_plugin( true ) ) {
				$classes = 'notice-warning';
				$text_string = \wp_kses( 'This integration uses different form plugin that is configured in settings', 'crmservice' );

				include_once CRMServiceWP\Plugin::crmservice_base_path( 'views/admin/notice.php' );
			}
		}
	} // end maybe_show_admin_notices
} // end class CPT

new CPT();
