<?php
/**
 * Bug report tab.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-04-10 13:15:58
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-04-30 12:45:26
 *
 * @package crmservice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<h2><?php \esc_attr_e( 'Send bug report', 'crmservice' ); ?></h2>

<form method="post">
	<label for="start"><b><?php \esc_attr_e( 'Your name', 'crmservice' ); ?></b></label><br />
	<input type="text" name="name" placeholder="<?php \esc_attr_e( 'Your name', 'crmservice' ); ?>" size="50" /><br /><br />

	<label for="start"><b><?php \esc_attr_e( 'Your email address', 'crmservice' ); ?></b></label><br />
	<input type="email" name="email" placeholder="<?php \esc_attr_e( 'Your email address', 'crmservice' ); ?>" size="50" /><br /><br />

	<label for="start"><b><?php \esc_attr_e( 'Bug report / free message', 'crmservice' ); ?></b></label><br />
	<textarea name="message" placeholder="<?php \esc_attr_e( 'Bug report / free message', 'crmservice' ); ?>" rows="6" style="width:50%;"></textarea><br />

	<p><i><?php \esc_attr_e( 'To help solving your problem, we will also send some data about your WordPress installation and server.', 'crmservice' ); ?></i></p>

	<input type="hidden" name="crmservice-sendbugreport" />
	<?php \wp_nonce_field( 'crmservice_bugreport', 'crmservice_bugreport_send' ); ?>
	<button class="button button-primary"><?php \esc_attr_e( 'Send', 'crmservice' ); ?></button>
</form>
