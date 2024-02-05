<?php
/**
 * Settings tab.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-03-30 14:10:28
 * @Last Modified by:   sippis
 * @Last Modified time: 2023-09-08 15:00:06
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
          <?php printf( \wp_kses( \__( 'You don\'t have any forms yet. Create a <a href="%s">new form</a>.', 'crmservice' ), array(
            'a' => array(
            'href' => array(),
          ), ) ), \esc_url( $new_form_url ) ) ?>
        </p>
      <?php else : ?>
        <div class="select-wrapper">
          <select name="crmservice_form" class="crmservice-form">
            <option value="0"><?php \esc_attr_e( 'Select', 'crmservice' ); ?></option>
            <?php foreach ( $forms as $form_id => $form ) : ?>
              <option value="<?php echo $form_id; ?>"<?php if ( (int) $form_id === (int) $saved_form ) { echo ' selected'; } ?>><?php echo $form; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
    </div>

    <div class="col col-module">
      <p class="label"><?php \esc_attr_e( 'CRM Module', 'crmservice' ); ?></p>

      <?php if ( empty( $crm_modules ) ) : ?>
        <p><?php \esc_attr_e( 'No modules found, please contact your CRM-service provider.', 'crmservice' ); ?></p>
      <?php else : ?>
        <div class="select-wrapper">
          <select name="crmservice_module">
            <option value="0"><?php \esc_attr_e( 'Select', 'crmservice' ); ?></option>
            <?php foreach ( $crm_modules as $module_id => $module ) : ?>
              <option value="<?php echo $module; ?>"<?php if ( $module === $saved_module ) { echo ' selected'; } ?>><?php echo $module; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="crmservice-metabox-connections-wrap" <?php if ( empty( $saved_conections ) ) : ?>style="display:none;"<?php endif; ?>>
  <h3><?php \esc_attr_e( 'Field mapping', 'crmservice' ); ?></h3>

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
      <div class="select-wrapper">
        <select name="module_field">
          <option value="0" data-type="0" data-uitype="0"><?php \esc_attr_e( 'Select', 'crmservice' ); ?></option>
        </select>
      </div>
      <input type="hidden" name="module_field" value="0" />
      <p class="select-options"><?php \esc_attr_e( 'Possible values:', 'crmservice' ); ?> <span></span></p>
      <p class="uitype-user-relation"><?php \esc_attr_e( 'Field is for user relation and value needs to be valid user ID.', 'crmservice' ); ?></p>
    </div>
  </div>

  <?php if ( ! empty( $saved_conections ) ) :
    $x = 1;
    foreach ( $form_fields['fields'] as $form_field_key => $form_field ) :
      $field_connection = null;

      foreach ( $saved_conections as $connection ) {
        if ( strval( $form_field_key ) !== strval( $connection['form_field'] ) ) {
          continue;
        }

        $field_connection = $connection;
      }

      ?>
      <div id="form-row-<?php echo $x; ?>" class="row row-field">
        <div class="col col-form">
          <p><?php echo $form_field; ?></p>
          <input type="hidden" name="crmservice_connections[<?php echo $x; ?>][form_field]" value="<?php echo $form_field_key; ?>" />
        </div>
        <div class="col col-module">
          <?php if ( ! empty( $module_fields ) ) : ?>
            <div class="select-wrapper">
              <select name="crmservice_connections[<?php echo $x; ?>][module_field]">
                <option value="0" data-type="0" data-uitype="0"><?php \esc_attr_e( 'Select', 'crmservice' ); ?></option>

                <?php foreach ( $module_fields as $module_field_id => $module_field ) :
                   ?>
                  <option value="<?php echo $module_field->name; ?>" data-type="<?php echo $module_field->type; ?>" data-uitype="<?php echo $module_field->uitype; ?>" <?php if ( $field_connection && $field_connection['module_field'] === $module_field->name ) { echo ' selected'; } ?>><?php echo $module_field->label; ?> (<?php echo $module_field->type; ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <input type="hidden" name="crmservice_connections[<?php echo $x; ?>][module_field]" value="<?php if ( isset( $field_connection['module_field'] ) ) { echo $field_connection['module_field']; } ?>" />

            <p class="select-options"><?php \esc_attr_e( 'Possible values:', 'crmservice' ); ?> <span></span></p>
            <p class="uitype-user-relation"><?php \esc_attr_e( 'Field is for user relation and value needs to be valid user ID.', 'crmservice' ); ?></p>
          <?php endif; ?>
        </div>
      </div>
    <?php $x++; endforeach; ?>
  <?php endif; ?>
</div>

<div class="crmservice-metabox-static-fields-wrap" <?php if ( empty( $saved_conections ) ) : ?>style="display:none;"<?php endif; ?>>
  <h3><?php \esc_attr_e( 'Pre-filled module fields', 'crmservice' ); ?></h3>

  <div class="row row-head" <?php if ( empty( $saved_static_fields ) ) : ?>style="display:none;"<?php endif; ?>>
    <div class="col col-module">
      <p><?php \esc_attr_e( 'Module field', 'crmservice' ); ?></p>
    </div>
    <div class="col col-value">
      <p><?php \esc_attr_e( 'Value', 'crmservice' ); ?></p>
    </div>
  </div>

  <div id="form-row-0" class="row row-field row-field-base" style="display:none;">
    <div class="col col-module">
      <div class="select-wrapper">
        <?php if ( ! empty( $module_fields ) ) : ?>
          <select name="crmservice_static_fields[0]">
            <option value="0" data-type="0" data-uitype="0"><?php \esc_attr_e( 'Select', 'crmservice' ); ?></option>

            <?php foreach ( $module_fields as $module_field_id => $module_field ) : ?>
              <option value="<?php echo $module_field->name; ?>" data-type="<?php echo $module_field->type; ?>"  data-uitype="<?php echo $module_field->uitype; ?>" ><?php echo $module_field->label; ?> (<?php echo $module_field->type; ?>)</option>
            <?php endforeach; ?>
          </select>
        <?php else : ?>
          <select name="crmservice_static_fields[0]">
            <option value="0" data-type="0" data-uitype="0"><?php \esc_attr_e( 'Select', 'crmservice' ); ?></option>
          </select>
        <?php endif; ?>
        <input type="hidden" name="crmservice_static_fields[0]" value="" />
      </div>
      <p class="select-options"><?php \esc_attr_e( 'Possible values:', 'crmservice' ); ?> <span></span></p>
      <p class="uitype-user-relation"><?php \esc_attr_e( 'Field is for user relation and value needs to be valid user ID.', 'crmservice' ); ?></p>
    </div>
    <div class="col col-value">
      <input type="text" name="crmservice_static_fields_values[0]" value="" />
      <button class="delete"><span class="dashicons dashicons-no-alt"></span></button>
    </div>
  </div>

  <?php if ( ! empty( $saved_static_fields ) ) :
    foreach ( $saved_static_fields as $field_id => $field_value ) : ?>

      <div id="form-row-<?php echo $field_id; ?>" class="row row-field">
        <div class="col col-module">
          <?php if ( ! empty( $module_fields ) ) : ?>
            <div class="select-wrapper">
              <select name="crmservice_static_fields[<?php echo $field_id; ?>]">
                <option value="0" data-type="0" data-uitype="0"><?php \esc_attr_e( 'Select', 'crmservice' ); ?></option>

                <?php foreach ( $module_fields as $module_field_id => $module_field ) : ?>
                  <option value="<?php echo $module_field->name; ?>" data-type="<?php echo $module_field->type; ?>"  data-uitype="<?php echo $module_field->uitype; ?>" <?php if ( $field_id === $module_field->name ) { echo ' selected'; } ?>><?php echo $module_field->label; ?> (<?php echo $module_field->type; ?>)</option>
                <?php endforeach; ?>
              </select>
              <input type="hidden" name="crmservice_static_fields[<?php echo $field_id; ?>]" value="<?php echo $field_id; ?>" />
            </div>
            <p class="select-options"><?php \esc_attr_e( 'Possible values:', 'crmservice' ); ?> <span></span></p>
            <p class="uitype-user-relation"><?php \esc_attr_e( 'Field is for user relation and value needs to be valid user ID.', 'crmservice' ); ?></p>
          <?php endif; ?>
        </div>
        <div class="col col-value">
          <input type="text" name="crmservice_static_fields_values[<?php echo $field_id; ?>]" value="<?php echo $field_value; ?>" />
          <button class="delete"><span class="dashicons dashicons-no-alt"></span></button>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <div class="row row-footer">
    <button class="button button-medium"><?php \esc_attr_e( 'Add new pre-filled field', 'crmservice' ); ?></button>
  </div>
</div>
