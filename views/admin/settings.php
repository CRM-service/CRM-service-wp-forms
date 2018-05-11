<?php
/**
 * Settings tab.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 14:10:28
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-04-18 11:22:14
 *
 * @package crmservice
 */

namespace CRMServiceWP;

use CRMServiceWP;
use CRMServiceWP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$purgecache_url = \wp_nonce_url( Helper\Helper::get_plugin_page_url(  array(
	'page'										=> 'crmservice',
	'crmservice_purgecache' 	=> 'true',
) ), 'crmservice_purgecache', 'crmservice_nonce' );

$reset_url = \wp_nonce_url( Helper\Helper::get_plugin_page_url( array(
	'page'							=> 'crmservice',
	'crmservice_reset' 	=> 'true',
) ), 'crmservice_reset', 'crmservice_nonce' );

$settings_tab_url = Helper\Helper::get_plugin_page_url( array(
	'page'	=> 'crmservice',
	'tab'		=> 'settings',
) );

$bugreport_tab_url = Helper\Helper::get_plugin_page_url( array(
	'page'	=> 'crmservice',
	'tab'		=> 'bugreport',
) );

$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'settings'; // @codingStandardsIgnoreLine ?>

<div class="wrap crmservice-settings">

	<br />
  <?php echo file_get_contents( Plugin::crmservice_base_path( 'assets/admin/logo.svg' ) ); ?>
  <br /><br />

  <h2 class="nav-tab-wrapper">
		<a href="<?php echo $settings_tab_url; ?>" class="nav-tab <?php echo ( 'settings' === $active_tab ) ? 'nav-tab-active' : ''; ?>"><?php \esc_attr_e( 'Settings', 'crmservice' ); ?></a>

		<a href="<?php echo $bugreport_tab_url; ?>" class="nav-tab <?php echo ( 'bugreport' === $active_tab ) ? 'nav-tab-active' : ''; ?>"><?php \esc_attr_e( 'Send bug report', 'crmservice' ); ?></a>
	</h2>

	<?php if ( 'settings' === $active_tab ) : ?>
		<form method="post" action="options.php">
			<?php \settings_fields( 'crmservice_settings' );
			\do_settings_sections( 'crmservice_settings_api' );
			\do_settings_sections( 'crmservice_settings_form_plugin' );
			\submit_button(); ?>
		</form>
		<p class="clear-cache">
			<a href="<?php echo $purgecache_url; ?>" class="button"><?php \esc_attr_e( 'Clear cache', 'crmservice' ); ?></a>
		</p>
		<p class="reset">
			<a href="<?php echo $reset_url; ?>"><?php \esc_attr_e( 'Reset plugin', 'crmservice' ); ?></a>
		</p>
	<?php elseif ( 'bugreport' === $active_tab ) :
		include_once CRMServiceWP\Plugin::crmservice_base_path( 'views/admin/bugreport.php' );
	endif; ?>
</div>
