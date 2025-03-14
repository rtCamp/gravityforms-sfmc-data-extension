<?php
/**
 * File to include Registration addon functionality.
 *
 * @package gravityforms-sfmc-data-extension.
 */

GFForms::include_feed_addon_framework();

/**
 * Class Gravityforms_SFMC_Data_Extension_Addon
 */
class Gravityforms_SFMC_Data_Extension_Addon extends GFFeedAddOn {

	/**
	 * Minimum Gravityform version.
	 *
	 * @var string
	 */
	protected $_min_gravityforms_version = '1.9';

	/**
	 * Slug of addon.
	 *
	 * @var string
	 */
	protected $_slug = 'gravityforms-sfmc-data-extension';

	/**
	 * Path to main file.
	 *
	 * @var string
	 */
	protected $_path = 'gravityforms-sfmc-data-extension/gravityforms-sfmc-data-extension-addon.php';

	/**
	 * Full Path.
	 *
	 * @var string
	 */
	protected $_full_path = __FILE__;

	/**
	 * Title of the form.
	 *
	 * @var string
	 */
	protected $_title = 'Gravity Forms to SFMC Data Extension Add-On';

	/**
	 * Short title.
	 *
	 * @var string
	 */
	protected $_short_title = 'Gravity Forms to SFMC Data Extension';

	/**
	 * Instance object.
	 *
	 * @var null
	 */
	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return Gravityforms_SFMC_Data_Extension_Addon
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new Gravityforms_SFMC_Data_Extension_Addon();
		}

		return self::$_instance;
	}

	/**
	 * Handles hooks and loading of language files.
	 */
	public function init() {
		parent::init();
		add_action( 'gform_field_standard_settings', array( $this, 'create_sf_id_fields' ), 10, 2 );
		add_action( 'gform_editor_js', array( $this, 'create_sf_id_fields_script' ) );

		add_filter( 'gform_entry_detail_meta_boxes', array( $this, 'register_entry_meta_box' ), 10, 3 );
	}

	/**
	 * Add column on list view.
	 *
	 * @return array
	 */
	public function feed_list_columns() {
		return array(
			'sfmc_event_feed_id' => __( 'Journey Event', 'gravityforms-sfmc-data-extension' ),
		);
	}

	/**
	 * Customize the value of mytext before it is rendered to the list.
	 *
	 * @param array $feed Feed object.
	 *
	 * @return string
	 */
	public function get_column_value_feedName( $feed ) {
		return '<b>' . rgars( $feed, 'meta/sfmc_event_feed_id' ) . '</b>';
	}

	/**
	 * Feed settings fields.
	 *
	 * @return array
	 */
	public function feed_settings_fields() {
		return array(
			array(
				'title'  => 'SFMC Integration Settings',
				'fields' => array(
					array(
						'label'    => esc_html__( 'Feed ID', 'gravityforms-sfmc-data-extension' ),
						'type'     => 'text',
						'name'     => 'sfmc_event_feed_id',
						'tooltip'  => esc_html__( 'Feed ID just for reference', 'gravityforms-sfmc-data-extension' ),
						'class'    => 'small',
						'required' => true,
					),
					array(
						'label'    => esc_html__( 'Event Definition Key', 'gravityforms-sfmc-data-extension' ),
						'type'     => 'text',
						'name'     => 'sfmc_event_definition_key',
						'class'    => 'medium',
						'required' => true,
					),
					array(
						'label'    => esc_html__( 'Marketing Cloud Client ID', 'gravityforms-sfmc-data-extension' ),
						'type'     => 'text',
						'name'     => 'sfmc_client_id',
						'class'    => 'medium',
						'required' => true,
					),
					array(
						'label'    => esc_html__( 'Marketing Cloud Client Secret', 'gravityforms-sfmc-data-extension' ),
						'type'     => 'text',
						'name'     => 'sfmc_client_secret',
						'class'    => 'medium',
						'required' => true,
					),
					array(
						'label'    => esc_html__( 'Marketing Cloud Account ID', 'gravityforms-sfmc-data-extension' ),
						'type'     => 'text',
						'name'     => 'sfmc_account_id',
						'class'    => 'medium',
						'required' => true,
					),
					array(
						'label'    => esc_html__( 'Marketing Cloud External Key', 'gravityforms-sfmc-data-extension' ),
						'type'     => 'text',
						'name'     => 'sfmc_external_key',
						'class'    => 'medium',
						'required' => true,
					),
					array(
						'label' => esc_html__( 'Error Email Recipients', 'gravityforms-sfmc-data-extension' ),
						'type'  => 'text',
						'name'  => 'error_message_recipients',
						'class' => 'medium',
					),
					array(
						'type'           => 'feed_condition',
						'name'           => 'integration_condition',
						'label'          => __( 'Integration Condition', 'gravityforms-sfmc-data-extension' ),
						'checkbox_label' => __( 'Enable Condition', 'gravityforms-sfmc-data-extension' ),
						'instructions'   => __( 'Process the settings', 'gravityforms-sfmc-data-extension' ),
					),
				),
			),
		);
	}

	/**
	 * Process the feed.
	 *
	 * @param array $feed Feed.
	 * @param array $entry Entry.
	 * @param array $form  Form.
	 *
	 * @return array|void|null
	 */
	public function process_feed( $feed, $entry, $form ) {

		$sfmc_data         = array();
		$sfmc_data['Data'] = array();

		foreach ( $form['fields'] as $field ) {
			if ( ! $field->sfmc_field_key ) {
				continue;
			}

			$sfmc_fields       = array_map( 'trim', explode( ',', $field->sfmc_field_key ) );
			$entry_field_value = $this->get_field_value( $form, $entry, $field->id );

			foreach ( $sfmc_fields as $sfmc_field ) {

				if ( 'ContactKey' === $sfmc_field || 'SubscriberKey' === $sfmc_field || 'EmailAddress' === $sfmc_field ) {
					$sfmc_data['ContactKey']            = $entry_field_value;
					$sfmc_data['Data']['SubscriberKey'] = $entry_field_value;
					$sfmc_data['Data']['ContactKey']    = $entry_field_value;
					$sfmc_data['Data']['EmailAddress']  = $entry_field_value;
				} elseif ( 'EventDefinitionKey' === $sfmc_field ) {
					$sfmc_data['EventDefinitionKey'] = $entry_field_value;
				} elseif ( isset( $sfmc_data['Data'][ $sfmc_field ] ) ) {
					if ( strlen( $entry_field_value ) ) {
						if ( strlen( $sfmc_data['Data'][ $sfmc_field ] ) ) {
							$sfmc_data['Data'][ $sfmc_field ] .= ', ' . $entry_field_value;
						} else {
							$sfmc_data['Data'][ $sfmc_field ] .= $entry_field_value;
						}
					}
				} else {
					$sfmc_data['Data'][ $sfmc_field ] = $entry_field_value;
				}
			}
		}

		if ( ! isset( $sfmc_data['sfmc_event_definition_key'] ) ) {
			$settings = $feed['meta'];

			if ( ! $settings['sfmc_event_definition_key'] ) {
				gform_update_meta( $entry['id'], 'gf_sfmc_entry_status', 'Error' );
				gform_update_meta( $entry['id'], 'gf_sfmc_entry_error', 'EventDefinitionKey is not specified' );
				return;
			}
			$sfmc_data['EventDefinitionKey'] = $settings['sfmc_event_definition_key'];
		}

		$sfmc_data = apply_filters( 'gravityform_sfmc_journey_entry_upsert_data', $sfmc_data, $entry, $form['id'] );

		$response = Gravityforms_SFMC_Data_Extension_Upsert::get_instance()->upsert_record( $sfmc_data, true, $this->get_sf_mc_credentials( $feed['meta'], $form ) );

		if ( is_a( $response, 'WP_Error' ) ) {
			gform_update_meta( $entry['id'], 'gf_sfmc_entry_status-' . $feed['id'], 'Error' );
			gform_update_meta( $entry['id'], 'gf_sfmc_entry_error-' . $feed['id'], 'Cannot connect to Salesforce' );
			Gravityforms_SFMC_Data_Extension_Email::process( $feed, $form, $entry );
		} elseif ( 200 === $response['response']['code'] || 201 === $response['response']['code'] ) {
			gform_update_meta( $entry['id'], 'gf_sfmc_entry_status-' . $feed['id'], 'OK' );
			gform_update_meta( $entry['id'], 'gf_sfmc_entry_error-' . $feed['id'], '' );
		} else {
			gform_update_meta( $entry['id'], 'gf_sfmc_entry_status-' . $feed['id'], 'Error: ' . $response['response']['code'] . ' ' . $response['response']['message'] );
			$body = json_decode( $response['body'] );
			gform_update_meta( $entry['id'], 'gf_sfmc_entry_error-' . $feed['id'], $body->message );
			Gravityforms_SFMC_Data_Extension_Email::process( $feed, $form, $entry );
		}

		return;
	}

	/**
	 * Create Salesforce id fields.
	 *
	 * @param int $position Position of field.
	 * @param int $form_id Form id.
	 */
	public function create_sf_id_fields( $position, $form_id ) {
		$form     = GFAPI::get_form( $form_id );
		$settings = $this->get_form_settings( $form );

		// create settings on position 25 (right after Field Label).
		if ( 10 === $position ) {
			?>
			<li class="sf_mc_setting field_setting">
				<label for="field_label" class="section_label">
					SFMC Field Key
				</label>
				<input type="text" id="field_sfmc_field_key" onchange="SetFieldProperty('sfmc_field_key', jQuery(this).val());" class="fieldwidth-3" autocomplete="off" />
			</li>
			<?php
		}
	}

	/**
	 * Get Salesforce MC credentials.
	 *
	 * @param null|array $feed_settings Feed Settings.
	 * @param null|int   $form Form.
	 *
	 * @return array Credentials array.
	 */
	public function get_sf_mc_credentials( $feed_settings = null, $form = null ) {
		$creds = array();

		if ( ! empty( $feed_settings ) ) {

			$creds['client_id']     = $feed_settings['sfmc_client_id'] ? $feed_settings['sfmc_client_id'] : $creds['client_id'];
			$creds['client_secret'] = $feed_settings['sfmc_client_secret'] ? $feed_settings['sfmc_client_secret'] : $creds['client_secret'];
			$creds['account_id']    = $feed_settings['sfmc_account_id'] ? $feed_settings['sfmc_account_id'] : $creds['account_id'];
			$creds['external_key']  = $feed_settings['sfmc_external_key'] ? $feed_settings['sfmc_external_key'] : $creds['external_key'];
		}

		return $creds;
	}

	/**
	 * Create Salesforce id field script.
	 */
	public function create_sf_id_fields_script() {
		?>
		<script type='text/javascript'>
			jQuery.each(fieldSettings, function(index, value) {
				fieldSettings[index] += ", .sf_mc_setting";
			});

			jQuery(document).bind("gform_load_field_settings", function(event, field, form) {
				if (typeof field["sfmc_field_key"] !== "undefined") {
					jQuery("#field_sfmc_field_key").val(field['sfmc_field_key']);
				} else {
					jQuery("#field_sfmc_field_key").val('');
				}
			});
		</script>
		<?php
	}

	/**
	 * Register entry meta box.
	 *
	 * @param array $meta_boxes Array of metaboxes.
	 * @param array $entry      Entry of form.
	 * @param int   $form       Form.
	 *
	 * @return array Metaboxes.
	 */
	public function register_entry_meta_box( $meta_boxes, $entry, $form ) {

		$meta_boxes[ $this->_slug ] = array(
			'title'    => $this->get_short_title(),
			'callback' => array( $this, 'render_entry_meta_box' ),
			'context'  => 'side',
		);

		return $meta_boxes;
	}

	/**
	 * Render entry meta box.
	 *
	 * @param array $args Array of arguments.
	 */
	public function render_entry_meta_box( $args ) {
		$entry           = $args['entry'];
		$processed_feeds = $this->get_feeds_by_entry( $entry['id'] );
		$html            = '<strong>Salesforce MC Status</strong><br />';
		$feed_status     = 'OK';
		$action          = $this->_slug . '_process_gravityform_sfmc_journey_entry_feed';

		if ( ! empty( $processed_feeds ) ) {
			foreach ( $processed_feeds as $feed_id ) {
				$feed = $this->get_feed( $feed_id );

				if ( rgpost( 'action' ) === $action ) {
					$this->process_feed( $feed, $args['entry'], $args['form'] );
				}

				if ( 'OK' !== gform_get_meta( $entry['id'], 'gf_sfmc_entry_status-' . $feed_id ) ) {
					$feed_status = 'Error';
				}

				$html .= '<strong>' . $feed['meta']['sfmc_event_feed_id'] . ': ' . '</strong>';
				$html .= '<br />';
				$html .= gform_get_meta( $entry['id'], 'gf_sfmc_entry_status-' . $feed_id );
				$html .= '<br />';
				$html .= gform_get_meta( $entry['id'], 'gf_sfmc_entry_error-' . $feed_id );
				$html .= '<br />';
			}
		}
		$html .= '<br />';

		echo wp_kses_post( $html );

		if ( 'OK' !== $feed_status ) {
			// Add the 'Retry Registration' button.
			printf( '<input type="submit" value="%s" class="button" onclick="jQuery(\'#action\').val(\'%s\');" />', esc_attr__( 'Retry', 'gravityforms-sfmc-data-extension' ), esc_attr( $action ) );
		}
	}
}
