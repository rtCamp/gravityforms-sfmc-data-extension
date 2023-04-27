<?php
/**
 * GravityForms Email Actions.
 *
 * @package gravityforms-sfmc-data-extension
 */

defined( 'ABSPATH' ) or die( 'Nothing to see.' );

/**
 * Class Gravityforms_SFMC_Data_Extension_Email
 */
class Gravityforms_SFMC_Data_Extension_Email {
	/**
	 * Process the form.
	 *
	 * @param mixed $feed      Feed settings.
	 * @param mixed $form      Gravity form.
	 * @param array $entry     Form entry.
	 */
	public static function process( $feed, $form, $entry ) {

		$sf_status = gform_get_meta( $entry['id'], 'gf_sfmc_entry_status-' . $feed['id'] );
		$sf_error  = gform_get_meta( $entry['id'], 'gf_sfmc_entry_error-' . $feed['id'] );

		$recipients = '';
		$recipients = $feed['meta']['error_message_recipients'] ? $feed['meta']['error_message_recipients'] : $recipients;
		$recipients = explode( ',', preg_replace( '/\s+/', '', $recipients ) );

		$url = admin_url( 'admin.php?page=gf_entries&view=entry&id=' . $form['id'] . '&lid=' . $entry['id'] );

		if ( isset( $sf_status ) && 'OK' !== $sf_status && $recipients ) {
			Gravityforms_SFMC_Data_Extension_Notify_Error::send_error_email( $url, $sf_status, $sf_error, $recipients );
		}
	}
}
