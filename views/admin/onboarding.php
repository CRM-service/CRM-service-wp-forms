<?php

/**
 * @Author: Timi Wahalahti
 * @Date:   2018-04-18 16:33:03
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-05-07 15:58:15
 */

namespace CRMServiceWP;

use CRMServiceWP;
use CRMServiceWP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// Defaults.
$plugins_list = array();
$plugin_page_url = CRMServiceWP\Helper\Helper::get_plugin_page_url( array( 'page' => 'crmservice' ) );
$new_integration_url = admin_url( 'post-new.php?post_type=crmservice_form' );
$first_step = array(
	'title'		=> esc_attr__( 'Install form plugin', 'crmservice' ),
	'desc'		=> '',
	'bttn'		=> array(
		'text'		=> esc_attr__( 'Go to plugins', 'crmservice' ),
		'url'			=> admin_url( 'plugins.php' ),
	),
);

// Get supported from plugins and if one is active, show different first step.
$form_plugins = CRMServiceWP\Helper\Helper::get_supported_form_plugins();
foreach  ( $form_plugins as $plugin ) {
	if ( $plugin['active'] ) {
		$first_step['title'] = esc_attr__( 'Form plugin activated!', 'crmservice' );
		$first_step['desc'] = sprintf( esc_attr__( "Looks like you already have the %s installed and active, that's awesome!", 'crmservice' ), $plugin['name'] );
		$first_step['desc'] .= '<br/><br/>' . esc_attr__( "You are only two steps away from sending data to the CRM-service.", 'crmservice' );
		unset( $first_step['bttn'] );
		continue;
	}

	$plugins_list[] = '<a href="' . $plugin['plugin_url'] . '">' . str_replace( ' (' . \esc_attr__( 'deactivated', 'crmservice' ) . ')', '', $plugin['name'] ) . '</a>';
}

// Make first step message if form plugin not active.
if ( empty( $first_step['desc'] ) ) {
	$first_step['desc'] = sprintf( esc_attr__( "Install or activate one of the supported form plugins: %s and make your first form to get started.", 'crmservice' ), implode( ', ', $plugins_list ) );
}

?>

<div class="notice crmservice-onboarding is-dismissible">
	<div class="top-bar">
		<?php echo file_get_contents( Plugin::crmservice_base_path( 'assets/admin/logo.svg' ) ); ?>
		<p><?php esc_attr_e( 'Please follow the next steps to start using the plugin.', 'crmservice' ) ?></p>
	</div>
	<div class="steps">
		<ol>
			<li>
				<div class="step">
					<div class="icon">
						<?php echo file_get_contents( Plugin::crmservice_base_path( 'assets/admin/onboarding-1.svg' ) ) ?>
					</div>
					<div class="content">
						<h3><?php echo $first_step['title'] ?></h3>
						<p><?php echo $first_step['desc'] ?></p>

						<?php if ( isset( $first_step['bttn'] )  ) : ?>
							<p><a href="<?php echo $first_step['bttn']['url'] ?>" target="_blank" class="button"><?php echo $first_step['bttn']['text'] ?></a></p>
						<?php endif; ?>
					</div>
				</div>
			</li>
			<li>
				<div class="step">
					<div class="icon">
						<?php echo file_get_contents( Plugin::crmservice_base_path( 'assets/admin/onboarding-2.svg' ) ) ?>
					</div>
					<div class="content">
						<h3><?php esc_attr_e( 'Configure the plugin', 'crmservice' ) ?></h3>
						<p><?php echo wp_kses( 'Configuring the plugin is easy! Just enter your CRM-service instance url, API key and select the supported form plugin of your choice', 'crmservice' ) ?>.</p>
						<p><a href="<?php echo $plugin_page_url ?>" class="button"><?php esc_attr_e( 'Go to settings', 'crmservice' ) ?></a></p>
					</div>
				</div>
			</li>
			<li>
				<div class="step">
					<div class="icon">
						<?php echo file_get_contents( Plugin::crmservice_base_path( 'assets/admin/onboarding-3.svg' ) ) ?>
					</div>
					<div class="content">
						<h3><?php esc_attr_e( 'Make your first integration', 'crmservice' ) ?></h3>
						<p><?php esc_attr_e( "Use our simple and intuitive tool to map form fields to the CRM-service module fields. After that you're all set and form submissions will go directly to the CRM-service!", 'crmservice' ) ?></p>
						<p><a href="<?php echo $new_integration_url ?>" class="button"><?php esc_attr_e( 'Add integration', 'crmservice' ) ?></a></p>
					</div>
				</div>
			</li>
		<p>
	</p>
	</div>
</div>
