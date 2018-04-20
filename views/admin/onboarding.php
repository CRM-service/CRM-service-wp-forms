<?php

/**
 * @Author: Timi Wahalahti
 * @Date:   2018-04-18 16:33:03
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-04-20 11:04:58
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
		$first_step['desc'] = sprintf( esc_attr__( "Looks like you already have %s installed and active, that's awesome!", 'crmservice' ), $plugin['name'] );
		$first_step['desc'] .= '<br/><br/>' . esc_attr__( "You are only two steps away from sending data to CRM-Service.", 'crmservice' );
		unset( $first_step['bttn'] );
		continue;
	}

	$plugins_list[] = '<a href="' . $plugin['plugin_url'] . '">' . $plugin['name'] . '</a>';
}

// Make first step message if form plugin not active.
if ( empty( $first_step['desc'] ) ) {
	$first_step['desc'] = sprintf( esc_attr__( "Install one of the supported form plugins: %s and make your first form to get started.", 'crmservice' ), implode( ',', $plugins_list ) );
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
						<svg xmlns="http://www.w3.org/2000/svg" widht="90" height="50" fill="#04537b" viewBox="0 0 612 344"><path d="M204 133.8h-76.5V57.2h-51v76.5H0v51h76.5v76.5h51v-76.5H204v-50.9zm255 25.4c43.4 0 76.5-33.1 76.5-76.5S502.4 6.2 459 6.2c-7.6 0-15.3 2.6-23 2.6 15.3 22.9 23 45.9 23 73.9s-7.6 51-23 74c7.7 0 15.4 2.5 23 2.5zm-127.5 0c43.4 0 76.5-33.1 76.5-76.5S374.9 6.2 331.5 6.2 255 39.4 255 82.8s33.1 76.4 76.5 76.4zm168.3 56.2c20.4 17.9 35.7 43.4 35.7 71.4v51H612v-51c0-38.3-61.2-63.8-112.2-71.4zm-168.3-5.2c-51 0-153 25.5-153 76.5v51h306v-51c0-50.9-102-76.5-153-76.5z"/></svg>
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
						<svg width="44" height="44" fill="#00517b" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 516.4 516.4"><path d="M353.8 0c-89.9 0-162.6 72.7-162.6 162.6 0 19.1 3.8 38.2 9.6 57.4L0 420.8v95.6h95.6V459H153v-57.4h57.4l86.1-86.1c17.2 5.7 36.3 9.6 57.4 9.6 89.9 0 162.6-72.7 162.6-162.6S443.7 0 353.8 0zm47.8 172.1c-32.5 0-57.4-24.9-57.4-57.4s24.9-57.4 57.4-57.4S459 82.2 459 114.8s-24.9 57.3-57.4 57.3z"/></svg>
					</div>
					<div class="content">
						<h3><?php esc_attr_e( 'Configure the plugin', 'crmservice' ) ?></h3>
						<p><?php echo wp_kses( 'Configuring the plugin is easy! Just enter your CRM-Service instance url, API key and select the supported form plugin of your choise', 'crmservice' ) ?>.</p>
						<p><a href="<?php echo $plugin_page_url ?>" class="button"><?php esc_attr_e( 'Go to settings', 'crmservice' ) ?></a></p>
					</div>
				</div>
			</li>
			<li>
				<div class="step">
					<div class="icon">
						<svg width="44" height="44" fill="#00517b" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 932.2 932.2"><path d="M61.2 341.5c4.9 16.8 11.7 33 20.3 48.2L57 420.6c-8 10.1-7.1 24.5 1.9 33.6l42.2 42.2c9.1 9.1 23.5 9.9 33.6 1.9l30.7-24.3c15.8 9.1 32.6 16.2 50.1 21.2l4.6 39.5c1.5 12.8 12.3 22.4 25.1 22.4h59.7c12.8 0 23.6-9.6 25.1-22.4l4.4-38.1c18.8-4.9 36.8-12.2 53.7-21.7l29.7 23.5c10.1 8 24.5 7.1 33.6-1.9l42.2-42.2c9.1-9.1 9.9-23.5 1.9-33.6l-23.1-29.3c9.6-16.6 17.1-34.3 22.1-52.8l35.6-4.1c12.8-1.5 22.4-12.3 22.4-25.1v-59.7c0-12.8-9.6-23.6-22.4-25.1l-35.1-4.1c-4.8-18.3-12-35.8-21.2-52.2l21.6-27.3c8-10.1 7.1-24.5-1.9-33.6l-42.1-42.1c-9.1-9.1-23.5-9.9-33.6-1.9l-26.5 21c-17.2-10.1-35.6-17.8-54.9-23l-4-34.3c-1.5-12.8-12.3-22.4-25.1-22.4h-59.7c-12.8 0-23.6 9.6-25.1 22.4l-4 34.3c-19.8 5.3-38.7 13.3-56.3 23.8l-27.5-21.8c-10.1-8-24.5-7.1-33.6 1.9l-42.2 42.2c-9.1 9.1-9.9 23.5-1.9 33.6l23 29.1c-9.2 16.6-16.2 34.3-20.8 52.7l-36.8 4.2C9.6 228.6 0 239.4 0 252.2v59.7c0 12.8 9.6 23.6 22.4 25.1l38.8 4.5zM277.5 180c54.4 0 98.7 44.3 98.7 98.7s-44.3 98.7-98.7 98.7c-54.4 0-98.7-44.3-98.7-98.7s44.3-98.7 98.7-98.7z"/><path d="M867.7 356.2l-31.5-26.6c-9.7-8.2-24-7.8-33.2.9l-17.4 16.3c-14.7-7.1-30.3-12.1-46.4-15l-4.9-24c-2.5-12.4-14-21-26.6-20l-41.1 3.5c-12.6 1.1-22.5 11.4-22.9 24.1l-.8 24.4c-15.8 5.7-30.7 13.5-44.3 23.3l-20.8-13.8c-10.6-7-24.7-5-32.9 4.7l-26.6 31.7c-8.2 9.7-7.8 24 .9 33.2l18.2 19.4c-6.3 14.2-10.8 29.1-13.4 44.4l-26 5.3c-12.4 2.5-21 14-20 26.6l3.5 41.1c1.1 12.6 11.4 22.5 24.1 22.9l28.1.9c5.1 13.4 11.8 26.1 19.9 38l-15.7 23.7c-7 10.6-5 24.7 4.7 32.9l31.5 26.6c9.7 8.2 24 7.8 33.2-.9l20.6-19.3c13.5 6.3 27.7 11 42.3 13.8l5.7 28.2c2.5 12.4 14 21 26.6 20l41.1-3.5c12.6-1.1 22.5-11.4 22.9-24.1l.9-27.6c15-5.3 29.2-12.5 42.3-21.4l22.7 15c10.6 7 24.7 5 32.9-4.7l26.6-31.5c8.2-9.7 7.8-24-.9-33.2l-18.3-19.4c6.7-14.2 11.6-29.2 14.4-44.6l25-5.1c12.4-2.5 21-14 20-26.6l-3.5-41.1c-1.1-12.6-11.4-22.5-24.1-22.9l-25.1-.8c-5.2-14.6-12.2-28.4-20.9-41.2l13.7-20.6c7.2-10.6 5.2-24.8-4.5-33zM712.8 593.8c-44.4 3.8-83.6-29.3-87.3-73.7-3.8-44.4 29.3-83.6 73.7-87.3 44.4-3.8 83.6 29.3 87.3 73.7 3.8 44.4-29.3 83.6-73.7 87.3zM205 704.4c-12.6 1.3-22.3 11.9-22.4 24.6l-.3 25.3c-.2 12.7 9.2 23.5 21.8 25.1l18.6 2.4c3.1 11.3 7.5 22.1 13.2 32.3l-12 14.8c-8 9.9-7.4 24.1 1.5 33.2l17.7 18.1c8.9 9.1 23.1 10.1 33.2 2.3l14.9-11.5c10.5 6.2 21.6 11.1 33.2 14.5l2 19.2c1.3 12.6 11.9 22.3 24.6 22.4l25.3.3c12.7.2 23.5-9.2 25.1-21.8l2.3-18.2c12.6-3.1 24.6-7.8 36-14l14 11.3c9.9 8 24.1 7.4 33.2-1.5l18.1-17.7c9.1-8.9 10.1-23.1 2.3-33.2l-10.7-13.9c6.6-11 11.7-22.7 15.2-35l16.6-1.7c12.6-1.3 22.3-11.9 22.4-24.6l.3-25.3c.2-12.7-9.2-23.5-21.8-25.1l-16.2-2.1c-3.1-12.2-7.7-24-13.7-35l10.1-12.4c8-9.9 7.4-24.1-1.5-33.2l-17.7-18.1c-8.9-9.1-23.1-10.1-33.2-2.3l-12.1 9.3c-11.4-6.9-23.6-12.2-36.4-15.8l-1.6-15.7c-1.3-12.6-11.9-22.3-24.6-22.4l-25.3-.3c-12.7-.2-23.5 9.2-25.1 21.8l-2 15.6c-13.2 3.4-25.9 8.6-37.7 15.4l-12.5-10.2c-9.9-8-24.1-7.4-33.2 1.5l-18.2 17.8c-9.1 8.9-10.1 23.1-2.3 33.2l10.7 13.8c-6.2 11-11.1 22.7-14.3 35l-17.5 1.8zm163.3-28.6c36.3.4 65.4 30.3 65 66.6-.4 36.3-30.3 65.4-66.6 65-36.3-.4-65.4-30.3-65-66.6.4-36.3 30.3-65.4 66.6-65z"/></svg>
					</div>
					<div class="content">
						<h3><?php esc_attr_e( 'Make your first integration', 'crmservice' ) ?></h3>
						<p><?php esc_attr_e( "Use our simple and intuitive tool to map form fields to CRM-Service module fields. After that you're all set and form submissions will go to CRM-Service!", 'crmservice' ) ?></p>
						<p><a href="<?php echo $new_integration_url ?>" class="button"><?php esc_attr_e( 'Add integration', 'crmservice' ) ?></a></p>
					</div>
				</div>
			</li>
		<p>
	</p>
	</div>
</div>
