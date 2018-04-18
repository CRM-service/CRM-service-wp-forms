<?php
/**
 * Settings tab.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 14:10:28
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-04-18 11:24:56
 *
 * @package crmservice
 */

namespace CRMServiceWP;

use CRMServiceWP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

\wp_nonce_field( 'crmservice_integration_nonce', 'crmservice_nonce' );
// save form plugin! ?>

<div class="crmservice-metabox-settings-wrap">
	<div class="row row-setting">
		<div class="col col-form">
			<p class="label"><?php \esc_attr_e( 'Form', 'crmservice' ); ?></p>

			<?php if ( empty( $forms ) ) : ?>
				<p>
          <?php printf( \wp_kses( \__( 'You do not have any forms yet. Create a <a href="%s">new form</a>.', 'crmservice' ), array(
						'a' => array(
						'href' => array(),
					), ) ), \esc_url( $new_form_url ) ) ?>
				</p>
			<?php else : ?>
				<select name="crmservice_form" class="crmservice-form">
					<option value="0"><?php \esc_attr_e( 'Select', 'crmservice' ); ?></option>
					<?php foreach ( $forms as $form_id => $form ) : ?>
						<option value="<?php echo $form_id; ?>"<?php if ( (int) $form_id === (int) $saved_form ) { echo ' selected'; } ?>><?php echo $form; ?></option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>
		</div>

		<div class="col col-module">
			<p class="label"><?php \esc_attr_e( 'CRM Module', 'crmservice' ); ?></p>

			<?php if ( empty( $crm_modules ) ) : ?>
				<p><?php \esc_attr_e( 'No modules found, please contact your CRM manager.', 'crmservice' ); ?></p>
			<?php else : ?>
				<select name="crmservice_module">
					<option value="0"><?php \esc_attr_e( 'Select', 'crmservice' ); ?></option>
					<?php foreach ( $crm_modules as $module_id => $module ) : ?>
						<option value="<?php echo $module; ?>"<?php if ( $module === $saved_module ) { echo ' selected'; } ?>><?php echo $module; ?></option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="crmservice-metabox-connections-wrap">
	<div class="row row-head">
		<div class="col col-form">
			<p><?php \esc_attr_e( 'Form field', 'crmservice' ); ?></p>
		</div>
		<div class="col col-module">
			<p><?php \esc_attr_e( 'Module field', 'crmservice' ); ?></p>
		</div>
	</div>

	<div id="form-row-0" class="row row-field row-field-base" style="display:none;">
		<div class="col col-form">
			<p></p>
			<input type="hidden" name="form_field" value="" />
		</div>
		<div class="col col-module">
			<select name="module_field">
				<option value="0"><?php \esc_attr_e( 'Select', 'crmservice' ); ?></option>
			</select>
			<input type="hidden" name="module_field" value="0" />
			<p class="select-options"><?php \esc_attr_e( 'Possible values:', 'crmservice' ); ?> <span></span></p>
		</div>
	</div>

	<?php if ( ! empty( $saved_conections ) ) :
		foreach ( $saved_conections as $connection_id => $connection ) : ?>
			<div id="form-row-<?php echo $connection_id; ?>" class="row row-field">
				<div class="col col-form">
					<p><?php echo $form_fields['fields'][ $connection['form_field'] ]; ?></p>
					<input type="hidden" name="crmservice_connections[<?php echo $connection_id; ?>][form_field]" value="<?php echo $connection['form_field']; ?>" />
				</div>
				<div class="col col-module">
					<select name="crmservice_connections[<?php echo $connection_id; ?>][module_field]">
						<option value="0"><?php \esc_attr_e( 'Select', 'crmservice' ); ?></option>

						<?php foreach ( $module_fields as $module_field_id => $module_field ) : ?>
							<option value="<?php echo $module_field->name; ?>" data-type="<?php echo $module_field->type; ?>" <?php if ( isset( $connection['module_field'] ) && $connection['module_field'] === $module_field->name ) { echo ' selected'; } ?>><?php echo $module_field->label; ?> (<?php echo $module_field->type; ?>)</option>
						<?php endforeach; ?>
					</select>
					<input type="hidden" name="crmservice_connections[<?php echo $connection_id; ?>][module_field]" value="<?php if ( isset( $connection['module_field'] ) ) { echo $connection['module_field']; } ?>" />

					<p class="select-options"><?php \esc_attr_e( 'Possible values:', 'crmservice' ); ?> <span></span></p>
				</div>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
