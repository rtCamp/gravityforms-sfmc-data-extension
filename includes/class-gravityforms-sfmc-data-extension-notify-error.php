<?php
/**
 * Notify about Salesforce errors.
 *
 * @package gravityforms-sfmc-data-extension
 */

defined( 'ABSPATH' ) or die( 'Nothing to see.' );

/**
 * Class Gravityforms_SFMC_Data_Extension_Notify_Error
 */
class Gravityforms_SFMC_Data_Extension_Notify_Error {
	/**
	 * Send email about error.
	 *
	 * @param string $post_url Post url.
	 * @param int    $status_code Status code.
	 * @param string $error_message Error message.
	 * @param array  $recipients List of recipients.
	 */
	static public function send_error_email( $post_url, $status_code, $error_message, $recipients ) {
		if ( $post_url ) {
			$subject = 'Alert: Salesforce API failure for ' . get_bloginfo( 'name' );
			$message = Gravityforms_SFMC_Data_Extension_Notify_Error::get_error_email_template( $post_url, $status_code, $error_message );

			// Set mail type to text/html.
			add_filter( 'wp_mail_content_type', array( 'Gravityforms_SFMC_Data_Extension_Notify_Error', 'set_html_email' ) );

			foreach ( $recipients as $recipient ) {
				wp_mail( $recipient, $subject, $message );
			}

			// remove the filter.
			remove_filter( 'wp_mail_content_type', array( 'Gravityforms_SFMC_Data_Extension_Notify_Error', 'set_html_email' ) );
		}
	}

	/**
	 * Set Email format to html.
	 *
	 * @return string Email format.
	 */
	static public function set_html_email() {
		return 'text/html';
	}

	/**
	 * Get error email template.
	 *
	 * @param string $post_url Post URL.
	 * @param int    $status_code Status code.
	 * @param string $error_message Error message.
	 *
	 * @return string Email template.
	 */
	static public function get_error_email_template( $post_url, $status_code, $error_message ) {
		$date = gmdate( 'F j, Y H:i:s' );

		// =====
		// Begin email template
		// =====

		return <<<EMAIL
        <html>
        <body>
            <h1>Salesforce API Error Notification</h1>
            <br />
            <table border="0" width="600">
                <tr>
                    <td colspan="2" style="background-color: #c0c0c0; padding: 5px;">
                        <b>Error Details</b>
                    </td>
                </tr>
                <tr>
                    <td align="right" style="padding: 3px;">Date:</td>
                    <td style="padding: 3px;">{$date}</td>
                </tr>
                <tr>
                    <td align="right" style="padding: 3px;" valign="top">Errors:</td>
                    <td style="padding: 3px;">
                        Status: {$status_code}
                        <hr>
                        Message: {$error_message}
                    </td>
                </tr>
                <tr>
                    <td align="right" style="padding: 3px;">Submission:</td>
                    <td style="padding: 3px;"><a href="{$post_url}">View</a></td>
                </tr>
            </table>
        </body>
        </html>
EMAIL;
	}
}
