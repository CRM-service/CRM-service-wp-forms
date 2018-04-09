<?php
/**
 * Settings tab.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 14:10:28
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-03-30 17:23:11
 *
 * @package crmservice
 */

namespace CRMServiceWP;
use CRMServiceWP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'settings'; // @codingStandardsIgnoreLine ?>

<div class="wrap">

	<br />
  <?php echo file_get_contents( Plugin::crmservice_base_path( 'assets/admin/logo.svg' ) ) ?>
  <br /><br />

  <h2 class="nav-tab-wrapper">
		<a href="?page=crmservice&tab=settings" class="nav-tab <?php echo ( 'settings' === $active_tab ) ? 'nav-tab-active' : ''; ?>"><?php \esc_attr_e( 'Settings', 'crmservice' ) ?></a>

		<a href="?page=crmservice&tab=bugreport" class="nav-tab <?php echo ( 'bugreport' === $active_tab ) ? 'nav-tab-active' : ''; ?>"><?php \esc_attr_e( 'Send bug report', 'crmservice' ) ?></a>
	</h2>

	<?php if ( 'settings' === $active_tab ) : ?>
		<form method="post" action="options.php">
			<?php \settings_fields( 'crmservice_settings' );
			\do_settings_sections( 'crmservice_settings_api' );
			\do_settings_sections( 'crmservice_settings_form_plugin' );
			\submit_button(); ?>
		</form>
		<p>
			<a href="?page=crmservice&crmservice_reset_credentials"><?php \esc_attr_e( 'Reset API Credentials', 'crmservice' ) ?></a>
		</p>
	<?php endif; ?>
</div>
