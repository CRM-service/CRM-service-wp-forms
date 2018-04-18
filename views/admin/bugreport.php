<?php
/**
 * Bug report tab.
 *
 * @Author: Timi Wahalahti
 * @Date:   2018-04-10 13:15:58
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-04-18 11:25:33
 *
 * @package crmservice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<h2><?php \esc_attr_e( 'Send bug report', 'crmservice' ); ?></h2>

<p><b><?php \esc_attr_e( 'Oh snap! Found a bug?', 'crmservice' ); ?></b><br />
<?php \esc_attr_e( 'Please report it to use so we can give it a look, fix issues and improve this plugin.', 'crmservice' ); ?></p>

<form method="post">
	<label for="start"><b><?php \esc_attr_e( 'Your name', 'crmservice' ); ?></b></label><br />
	<input type="text" name="name" placeholder="<?php \esc_attr_e( 'Your name', 'crmservice' ); ?>" size="50" /><br /><br />

	<label for="start"><b><?php \esc_attr_e( 'Your email address', 'crmservice' ); ?></b></label><br />
	<input type="email" name="email" placeholder="<?php \esc_attr_e( 'Your email address', 'crmservice' ); ?>" size="50" /><br /><br />

	<label for="start"><b><?php \esc_attr_e( 'Bug report / free message', 'crmservice' ); ?></b></label><br />
	<textarea name="message" placeholder="<?php \esc_attr_e( 'Bug report / free message', 'crmservice' ); ?>" rows="6" style="width:50%;"></textarea><br />

	<input type="hidden" name="crmservice-sendbugreport" />
	<?php \wp_nonce_field( 'crmservice_bugreport', 'crmservice_bugreport_send' ); ?>
	<button class="button button-primary"><?php \esc_attr_e( 'Send', 'crmservice' ); ?></button>
	<p><i><?php \esc_attr_e( 'To help solving your problem, we will send also some data about your WordPress install and server.', 'crmservice' ); ?></i></p>
</form>
