<?php
/**
 * Salesforce Update entry on remote.
 *
 * @package gravityforms-sfmc-data-extension
 */

defined( 'ABSPATH' ) or die( 'Nothing to see.' );

/**
 * Class Gravityforms_SFMC_Data_Extension_Upsert
 */
class Gravityforms_SFMC_Data_Extension_Upsert {
	/**
	 * Instance of the class.
	 *
	 * @var null|object Instance of the class.
	 */
	private static $instance;

	/**
	 * Get Instance of the class.
	 *
	 * @return Gravityforms_SFMC_Data_Extension_Upsert|object|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Gravityforms_SFMC_Data_Extension_Upsert();
		}
		return self::$instance;
	}

	/**
	 * Upsert the record.
	 *
	 * @param string|array $data Data record.
	 * @param bool         $retry Retry flag.
	 * @param null|array   $creds Credentials.
	 *
	 * @return array|WP_Error Response.
	 */
	public function upsert_record( $data, $retry = true, $creds = null ) {

		$token = Gravityforms_SFMC_Data_Extension_Auth::get_instance()->retrieve_token( $retry, $creds );

		$external_key = $creds && $creds['external_key'] ? $creds['external_key'] : '';

		$initial_data = $data;

		if ( ! is_array( $data ) ) {
			$data = json_decode( $data->get_body(), true );
		}

		$data['Data']['Submission_Date'] = gmdate( 'n/j/Y G:i:s' );
		$data                            = wp_json_encode( $this->validate_lengths( $data ) );



		// Send data to SF data extention.
		$response = wp_safe_remote_post(
			esc_url( 'https://' . $external_key . '.rest.marketingcloudapis.com/interaction/v1/events' ),
			array(
				'headers'     => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
				),
				'method'      => 'POST',
				'httpversion' => '1.1',
				'timeout'     => 50,
				'blocking'    => true,
				'body'        => $data,
			)
		);

		// If sfmc returns error and this is the first attempt, try one more time.
		if ( ( is_wp_error( $response ) || '401' === $response['response']['code'] ) && $retry ) {
			Gravityforms_SFMC_Data_Extension_Auth::get_instance()->store_token( null );

			// Get new token.
			$token = Gravityforms_SFMC_Data_Extension_Auth::get_instance()->retrieve_token( false, $creds );

			// Try submitting again.
			$response = $this->upsert_record( $initial_data, false, $creds );
		}

		return $response;
	}

	/**
	 * Validate lenghts of data.
	 *
	 * @param array $data Data records.
	 *
	 * @return array Array of data.
	 */
	private function validate_lengths( $data ) {
		$lengths = array();

		foreach ( $lengths as $key => $value ) {
			if ( strlen( $data[ $key ] ) > $value ) {
				$data[ $key ] = substr( $data[ $key ], 0, $value );
			}
		}

		return $data;
	}
}
