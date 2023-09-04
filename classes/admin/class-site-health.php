<?php

/**
 * @Author: Timi Wahalahti
 * @Date:   2018-04-25 17:08:45
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2023-05-25 09:51:16
 */

namespace CRMServiceWP\Admin\SiteHealth;

use CRMServiceWP;
use CRMServiceWP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 *  Class for plugin settings.
 *
 *  @since 1.0.0
 */
class SiteHealth extends CRMServiceWP\Plugin {
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

    // Get form plugin selected and load it's integration class.
    self::$form_plugin = self::$helper->get_form_plugin();
    $form_plugin_active = self::$helper->check_if_form_plugin_active();

    if ( self::$form_plugin && $form_plugin_active ) {
      $load_slug = self::$form_plugin['slug'];
      $file_path = CRMServiceWP\Plugin::crmservice_base_path( "classes/form-plugins/{$load_slug}.php" );

      if ( file_exists( $file_path ) ) {
        include_once CRMServiceWP\Plugin::crmservice_base_path( "classes/form-plugins/{$load_slug}.php" );

        // Load instance for form spesific plugin.
        self::$form_plugin_instance = new self::$form_plugin['class'];
      }
    }

		// Ignition done, give some kick.
		self::run();
	} // end __construct

	/**
	 *  Add hooks.
	 *
	 *  @since  1.0.0
	 */
	protected function run() {
		// Tests
    add_filter( 'site_status_tests',            array( __CLASS__, 'status_tests' ) );
    add_filter( 'site_status_test_php_modules', array( __CLASS__, 'site_status_test_php_modules' ) ); // require soap
	} // end run

  public static function status_tests( $tests ) {
    $tests['direct']['crmservice_api_health'] = array(
      'label' => \wp_kses( 'CRM-service API connection.', 'crmservice' ),
      'test'  => array( __CLASS__, 'test_crmservice_api_health' ),
    );

    $tests['direct']['crmservice_form_plugin'] = array(
      'label' => \wp_kses( 'CRM-service form plugin.', 'crmservice' ),
      'test'  => array( __CLASS__, 'test_crmservice_form_plugin' ),
    );

    $tests['direct']['crmservice_failed_submissions'] = array(
      'label' => \wp_kses( 'CRM-service failed submissions.', 'crmservice' ),
      'test'  => array( __CLASS__, 'test_crmservice_failed_submissions' ),
    );

    return $tests;
  } // end status_tests

  public static function site_status_test_php_modules( $modules ) {
    $modules['soap'] = array(
      'extension' => 'soap',
      'required'  => true,
    );

    return $modules;
  } // end site_status_test_php_modules

  public static function test_crmservice_api_health() {
    $result = array(
      'label'       => \wp_kses( 'API connection is healthy', 'crmservice' ),
      'status'      => 'good',
      'badge'       => array(
        'label' => 'CRM-service',
        'color' => 'blue',
      ),
      'description' => \wp_kses( 'API base url and key have been set, and site can communicate with CRM-service.', 'crmservice' ),
      'actions'     => '',
      'test'        => 'crmservice_api_health',
    );

    $api_credentials = self::$helper->check_api_settings_existance();
    $api_credentials_health = self::$helper->check_api_credentials_health();

    if ( ! $api_credentials ) {
      $result['status']      = 'critical';
      $result['label']       = \wp_kses( 'API base url and key not set', 'crmservice' );
      $result['description'] = \wp_sprintf( wp_kses( 'Setting up <a href="%s">API credentials</a> is needed. If you don\'t know what those are, please contact our support.', 'crmservice' ), self::$helper->get_plugin_page_url( array( 'page' => 'crmservice' ) ) );
    } elseif ( ! $api_credentials_health ) {
      $result['status']      = 'critical';
      $result['label']       = \wp_kses( 'Can\'t connect to CRM-service', 'crmservice' );
      $result['description'] = \wp_sprintf( wp_kses( 'You might have added wrong <a href="%s">API credentials</a>. Please check your API credntials. With correct API credentials, there might be a temporary problem with our API. If this problem persist, please <a href="%s">contact our support</a>.', 'crmservice' ), self::$helper->get_plugin_page_url( array( 'page' => 'crmservice' ) ), self::$helper->get_plugin_page_url( array( 'page' => 'crmservice', 'tab' => 'bugreport' ) ) );
    }

    return $result;
  } // end test_crmservice_api_health

  public static function test_crmservice_form_plugin() {
    $result = array(
      'label'       => \wp_kses( 'Form plugin configured and active', 'crmservice' ),
      'status'      => 'good',
      'badge'       => array(
        'label' => 'CRM-service',
        'color' => 'blue',
      ),
      'description' => \wp_kses( 'Form plugin to use with CRM-service is configured and selected plugin is active.', 'crmservice' ),
      'actions'     => '',
      'test'        => 'crmservice_form_plugin',
    );

    $form_plugin = self::$helper->get_form_plugin();
    $form_plugin_active = self::$helper->check_if_form_plugin_active();

    if ( ! $form_plugin ) {
      $result['status']      = 'critical';
      $result['label']       = \wp_kses( 'Form plugin not configured', 'crmservice' );
      $result['description'] = \wp_sprintf( \wp_kses( 'In <a href="%s">settings page</a>, select a form plugin you want to integrate to.', 'crmservice' ), self::$helper->get_plugin_page_url( array( 'page' => 'crmservice' ) ) );
    } elseif ( ! $form_plugin_active ) {
      $result['status']      = 'critical';
      $result['label']       = \wp_kses( 'Form plugin is not active', 'crmservice' );
      $result['description'] = \wp_sprintf( \wp_kses( 'The form plugin (%s) you have selected in settings, is not active.', 'crmservice' ), $form_plugin['name'] );
    }

    return $result;
  } // end test_crmservice_form_plugin

  public static function test_crmservice_failed_submissions() {
    $result = array(
      'label'       => \wp_kses( 'No failed submissions to be resent', 'crmservice' ),
      'status'      => 'good',
      'badge'       => array(
        'label' => 'CRM-service',
        'color' => 'blue',
      ),
      'description' => \wp_kses( 'All configured form submissions have been sent succesfully to CRM-service.', 'crmservice' ),
      'actions'     => '',
      'test'        => 'crmservice_failed_submissions',
    );

    if ( ! self::$helper->check_if_form_plugin_active() ) {
      return $result;
    }

    $form_plugin_slug = self::$helper->get_form_plugin( true );
    $failed_submissions = self::$form_plugin_instance->get_failed_submissions();

    if ( ! apply_filters( 'crmservice_forms_resend_failed_submissions', true ) ) {
      $result['status']      = 'recommended';
      $result['label']       = \wp_kses( 'Resending failed submissions is not active', 'crmservice' );
      $result['description'] = \wp_kses( 'Resending failed submissions to CRM-service has been disabled with <code>crmservice_forms_resend_failed_submissions</code> filter.', 'crmservice' );
    }  elseif ( 'contact-form-7' === $form_plugin_slug && ! self::$helper->check_contact_form_7_flamingo() ) {
      $result['status']      = 'recommended';
      $result['label']       = \wp_kses( 'Resending failed submissions will not work', 'crmservice' );
      $result['description'] =  \wp_sprintf( \wp_kses( 'The CRM-service plugin will try to resend form submissions to the CRM-service, if the first submission fails for some reason. In order to this feature to work with the Contact Form 7, you need to install the <a href="%s">Flamingo</a> -plugin. It will also save the form submisison directly to WordPress, which is considered as a good practice in general.', 'crmservice' ), \admin_url( 'plugin-install.php?tab=plugin-information&plugin=flamingo' ) );
    } elseif ( is_array( $failed_submissions ) && ! empty( $failed_submissions ) ) {
      $failed_tmp = 0;
      $failed_perm = 0;

      foreach ( $failed_submissions as $submission_id => $times ) {
        if ( 3 < count( $times ) ) {
          $failed_perm++;
          continue; // continue to next submission if failed more than three times.
        }

        $failed_tmp++;
      }

      if ( ! $failed_perm ) {
        $result['status']      = 'recommended';
        $result['label']       = \wp_kses( 'Some form submissions have failed', 'crmservice' );
        $result['description'] = \wp_sprintf( \wp_kses( '%s submissions have failed to be sent to CRM-service. Plugin will still try to resend these submissions to the CRM-service later today.', 'crmservice' ), $failed_tmp );
      } else {
        $result['status']      = 'critical';
        $result['label']       = \wp_kses( 'Some form submissions have failed', 'crmservice' );
        $result['description'] = \wp_sprintf( \wp_kses( '%s submissions have failed to be sent to CRM-service too many times. Plugin will not try to resend these submissions anymore.', 'crmservice' ), $failed_perm );
      }
    }

    return $result;
  } // end test_crmservice_failed_submissions
}

new SiteHealth();
