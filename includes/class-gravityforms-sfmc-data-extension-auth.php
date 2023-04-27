<?php
/**
 * Salesforce Authentication.
 *
 * @package gravityforms-sfmc-data-extension
 */

defined( 'ABSPATH' ) or die( 'Nothing to see.' );

/**
 * Class Gravityforms_SFMC_Data_Extension_Auth
 */
class Gravityforms_SFMC_Data_Extension_Auth {
	/**
	 * Instance of the class.
	 *
	 * @var null|object Instance of the class.
	 */
	private static $instance;

	/**
	 * Get Instance of the class.
	 *
	 * @return Gravityforms_SFMC_Data_Extension_Auth|object|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Gravityforms_SFMC_Data_Extension_Auth();
		}
		return self::$instance;
	}

	/**
	 * Function to acquire OAuthToken.
	 *
	 * @param bool       $retry Retry or not.
	 * @param null|array $creds Credentials details.
	 *
	 * @return bool|mixed Response for if token is aquired or not.
	 */
	public function acquire_o_auth_token( $retry = true, $creds = null ) {
		$client_id     = ( is_array( $creds ) && $creds['client_id'] ) ? $creds['client_id'] : '';
		$client_secret = ( is_array( $creds ) && $creds['client_secret'] ) ? $creds['client_secret'] : '';
		$account_id    = ( is_array( $creds ) && $creds['account_id'] ) ? $creds['account_id'] : '';
		$external_key  = ( is_array( $creds ) && $creds['external_key'] ) ? $creds['external_key'] : '';


		if ( empty( $client_secret ) || empty( $client_id ) || empty( $account_id ) ) {
			return false;
		}

		$body = [
			'grant_type'    => 'client_credentials',
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'account_id'    => $account_id,
		];

		$response = wp_safe_remote_post(
			esc_url( 'https://' . $external_key . '.auth.marketingcloudapis.com/v2/token' ),
			[
				'method'      => 'POST',
				'httpversion' => '1.1',
				'redirection' => 10,
				'timeout'     => 50,
				'headers'     => [
					'Content-Type' => 'application/json',
				],
				'body'        => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $response ) || 200 !== $response['response']['code'] ) {
			if ( $retry ) { /* if we fail to get a token, try one more time */
				return $this->acquire_o_auth_token( false, $creds );
			}
			return false;
		} else {
			$body = json_decode( $response['body'] );
			return $this->store_token( $body->access_token );
		}
	}

	/**
	 * Retrieve Token.
	 *
	 * @param bool       $force_retrieve Forcefully retrieve token or not.
	 * @param null|array $creds Credentials.
	 *
	 * @return bool|mixed|void Return token.
	 */
	public function retrieve_token( $force_retrieve = false, $creds = null ) {
		/* check to see if token is stored */
		$token = get_option( 'gravityform_sfmc_journey_entry_auth_token', null );

		$final_creds = array(
			'client_id'     => $creds && $creds['client_id'] ? $creds['client_id'] : '',
			'client_secret' => $creds && $creds['client_secret'] ? $creds['client_secret'] : '',
			'account_id'    => $creds && $creds['account_id'] ? $creds['account_id'] : '',
			'external_key'  => $creds && $creds['external_key'] ? $creds['external_key'] : '',
		);

		/* if not stored, get new token */
		if ( ! $token || $force_retrieve ) {
			$token = $this->acquire_o_auth_token( true, $final_creds );
		}

		/* check to see if token is valid */
		$response = wp_remote_post(
			'https://' . $final_creds['external_key'] . '.auth.marketingcloudapis.com/v2/userinfo',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
				),
				'method'  => 'GET',
			)
		);

		/* if not vaild, get new valid token */
		if ( is_wp_error( $response ) || '401' === $response['response']['code'] ) {
			update_option( 'gravityform_sfmc_journey_entry_auth_token', null );
			$token = $this->acquire_o_auth_token( true, $final_creds );
		}

		return $token;
	}

	/**
	 * Store the token.
	 *
	 * @param string $token Token.
	 *
	 * @return string Token.
	 */
	public function store_token( $token ) {
		update_option( 'gravityform_sfmc_journey_entry_auth_token', $token );
		return $token;
	}
}
