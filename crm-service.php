<?php
/**
 * Integration between CRM-service and WordPress.
 *
 * @package CRMServiceWP
 *
 * Plugin Name:       CRM-service WP Forms
 * Plugin URI:
 * Description:       Integration between CRM-service and WordPress.
 * Author:            crmservice
 * Author URI:        https://crm-service.fi/
 * License:           GPLv2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Version:           1.4.5
 * Requires at least: 4.9
 * Tested up to:      6.3
 *
 * Text Domain: crmservice
 * Domain Path: /languages
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-02-27 15:47:00
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2023-09-08 15:08:23
 */

namespace CRMServiceWP;

use CRMServiceWP;
use CRMServiceWP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
  exit();
}

if ( ! class_exists( 'Plugin' ) ) :
  define( 'CRMSERVICEWP_VERSION', '1.4.5' );

  /**
   *  Main class for plugin.
   */
  class Plugin {
    /**
     *  Instance of helper.
     *
     *  @var resource
     */
    protected static $helper;

    /**
     *  Wrapper function to get path of this plugin.
     *
     *  @since  1.0.0
     *  @return string  path to plugin
     */
    public static function crmservice_base_path( $file = null ) {
      $path = \untrailingslashit( \plugin_dir_path( __FILE__ ) );

      if ( $file ) {
        $path .= "/{$file}";
      }

      return $path;
    } // end crmservice_base_path

    /**
     *  Run the plugin.
     *
     *  @since  1.0.0
     */
    public static function load() {

      // Include files needed always.
      include_once self::crmservice_base_path( 'classes/class-helper.php' );
      include_once self::crmservice_base_path( 'classes/class-cpt.php' );
      include_once self::crmservice_base_path( 'classes/class-api.php' );
      include_once self::crmservice_base_path( 'classes/class-forms-common.php' );

      // Get instance of helper.
      self::$helper = new CRMServiceWP\Helper\Helper();

      // Include files and run hooks needed only in admin.
      if ( \is_admin() ) {
        include_once self::crmservice_base_path( 'classes/admin/class-settings.php' );
        include_once self::crmservice_base_path( 'classes/admin/class-notices.php' );
        include_once self::crmservice_base_path( 'classes/admin/class-site-health.php' );

        \add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );
      }

      // Add actions.
      \add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
    } // end load

    /**
     *  Load plugin textdomain
     *
     *  @since  1.0.0
     */
    public static function load_textdomain() {
      $loaded = \load_plugin_textdomain( 'crmservice', false, dirname( \plugin_basename( __FILE__ ) ) . '/language/' );
      if ( ! $loaded ) {
        $loaded = \load_muplugin_textdomain( 'crmservice', dirname( \plugin_basename( __FILE__ ) ) . '/language/' );
      }
    } // end load_textdomain

    /**
     *  Enqueue our css and js if user is in dashboard
     *
     *  @since  1.0.0
     */
    public static function enqueue_admin() {
      $screen = \get_current_screen();

      \wp_enqueue_style( 'crmservice', \plugins_url( 'assets/admin/main.css', __FILE__ ), array(), '1.1.0' );

      if ( 'post' === $screen->base || 'edit' === $screen->base ) {
        if ( 'crmservice_form' === $screen->post_type ) {
          \wp_enqueue_script( 'crmservice', \plugins_url( 'assets/admin/metabox.js', __FILE__ ), array(), null, true );
          \wp_localize_script( 'crmservice', 'crmservice', array(
            'root'      => \esc_url_raw( \rest_url() ),
            'nonce'     => \wp_create_nonce( 'wp_rest' ),
          ) );
        }
      }
    } // end enqueue_admin
  } // end class Plugin

  /**
   *  Schedule maybe resend failed submissions sends.
   *
   *  @since  1.0.0
   */
  function plugin_activation() {
    if ( ! \wp_next_scheduled( 'crmservice_maybe_resend' ) ) {
      \wp_schedule_event( time(), 'hourly', 'crmservice_maybe_resend' );
    }
  } // end plugin_activation

  /**
   *  Remove cron schedules.
   *
   *  @since  1.0.0
   */
  function plugin_deactivation() {
    \wp_clear_scheduled_hook( 'crmservice_maybe_resend' );
  } // end plugin_deactivation

  /**
   *  Start the plugin.
   *
   *  @since  1.0.0
   */
  function load_plugin() {
    $pg = new Plugin();
    $pg->load();
  } // end load_plugin
endif;

// Add actio to really start the plugin.
\add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_plugin' );

// Add plugin activation and deactivation hooks.
register_activation_hook( __FILE__, __NAMESPACE__ . '\\plugin_activation' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\plugin_deactivation' );
