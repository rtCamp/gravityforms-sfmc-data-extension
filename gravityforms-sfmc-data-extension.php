<?php
/**
 * Plugin Name: Gravity Forms to SFMC Data Extension Add-On
 * Description: Submit the Gravityform entries to Salesforce Marketing Cloud Using Journey Entry Event.
 * Version: 1.0
 * Requires at least: 5.5
 * Tested up to: 5.5
 * Author URI: https://rtcamp.com
 * Plugin URI: https://rtcamp.com
 * Author: rtCamp, kiranpotphode
 * Text Domain: gravityforms-sfmc-data-extension
 *
 * @package  gravityforms-sfmc-data-extension
 */

/**
 * Gravityforms_SFMC_Data_Extension_Bootstrap Addon Bootstrap class.
 */
class Gravityforms_SFMC_Data_Extension_Bootstrap {

	/**
	 * Plugin load method.
	 *
	 * @return void
	 */
	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once( plugin_dir_path( __FILE__ ) . '/includes/class-gravityforms-sfmc-data-extension-addon.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/includes/class-gravityforms-sfmc-data-extension-auth.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/includes/class-gravityforms-sfmc-data-extension-upsert.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/includes/class-gravityforms-sfmc-data-extension-notify-error.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/includes/class-gravityforms-sfmc-data-extension-email.php' );

		GFAddOn::register( 'Gravityforms_SFMC_Data_Extension_Addon' );
	}

}

add_action( 'gform_loaded', array( 'Gravityforms_SFMC_Data_Extension_Bootstrap', 'load' ), 5 );
