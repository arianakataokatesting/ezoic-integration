<?php
namespace Ezoic_Namespace;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/admin
 */
class Ezoic_Integration_CDN_Settings {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 *
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}


	public function initialize_cdn_settings() {

		add_settings_section(
			'ezoic_cdn_settings_section',
			__( 'Ezoic CDN Settings', 'ezoic' ),
			array( $this, 'ezoic_cdn_settings_section_callback' ),
			'ezoic_cdn'
		);

		add_settings_field(
			'ezoic_cdn_api_key',
			'Ezoic API Key',
			array( $this, 'ezoic_cdn_api_key_field' ),
			'ezoic_cdn',
			'ezoic_cdn_settings_section'
		);

		add_settings_field(
			'ezoic_cdn_enabled',
			'Automatic Recaching',
			array( $this, 'ezoic_cdn_enabled_field' ),
			'ezoic_cdn',
			'ezoic_cdn_settings_section'
		);

		add_settings_field(
			'ezoic_cdn_always_home',
			'Purge Home',
			array( $this, 'ezoic_cdn_always_home_field' ),
			'ezoic_cdn',
			'ezoic_cdn_settings_section'
		);

		add_settings_field(
			'ezoic_cdn_domain',
			'Ezoic Domain',
			array( $this, 'ezoic_cdn_domain_field' ),
			'ezoic_cdn',
			'ezoic_cdn_settings_section',
			[
				'class' => 'ez_hidden'
			]
		);

		add_settings_field(
			'ezoic_cdn_verbose_mode',
			'Verbose Mode',
			array( $this, 'ezoic_cdn_verbose_field' ),
			'ezoic_cdn',
			'ezoic_cdn_settings_section',
			[
				'class' => 'ez_hidden'
			]
		);

		register_setting( 'ezoic_cdn', 'ezoic_cdn_api_key' );
		register_setting( 'ezoic_cdn', 'ezoic_cdn_enabled', [ 'default' => true ] );
		register_setting( 'ezoic_cdn', 'ezoic_cdn_always_home', [ 'default' => true ] );
		register_setting( 'ezoic_cdn', 'ezoic_cdn_domain' );
		register_setting( 'ezoic_cdn', 'ezoic_cdn_verbose_mode', [ 'default' => false ] );
	}

	/**
	 * Empty Callback for WordPress Settings
	 *
	 * @return void
	 * @since 1.0.0
	 */
	function ezoic_cdn_settings_section_callback() {

		$ping_test = "";
		$api_key   = Ezoic_Integration_CDN::ezoic_cdn_api_key();
		if ( ! empty( $api_key ) ) {
			$ping_test = Ezoic_Integration_CDN::ezoic_cdn_ping();
		}
		include_once( EZOIC__PLUGIN_DIR . 'admin/partials/ezoic-integration-admin-display-cdn.php' );

	}


	/**
	 * WordPress Settings Field for defining the Ezoic API Key
	 *
	 * @return void
	 * @since 1.0.0
	 */
	function ezoic_cdn_api_key_field() {
		?>
        <input type="text" name="ezoic_cdn_api_key" class="regular-text code"
               value="<?php echo( esc_attr( Ezoic_Integration_CDN::ezoic_cdn_api_key() ) ); ?>"/>
        <p class="description">
            You can find your <a
                    href="https://pubdash.ezoic.com/settings?scroll=api_gateway" target="_blank">API key here</a>.
        </p>
		<?php
	}

	/**
	 * WordPress Settings Field for defining the domain to purge cache for
	 *
	 * @return void
	 * @since 1.1.1
	 */
	function ezoic_cdn_domain_field() {
		?>
        <input type="text" name="ezoic_cdn_domain"
               value="<?php echo( esc_attr( Ezoic_Integration_CDN::ezoic_cdn_get_domain() ) ); ?>"/>
        <p class="description">
            Main domain only, must match domain in Ezoic, no subdomains.
        </p>
		<?php
	}

	/**
	 * WordPress Settings Field for enabling/disabling auto-purge
	 *
	 * @return void
	 * @since 1.0.0
	 */
	function ezoic_cdn_enabled_field() {
		$value = Ezoic_Integration_CDN::ezoic_cdn_is_enabled( true );

		?>
        <input type="radio" id="ezoic_cdn_enabled_on" name="ezoic_cdn_enabled" value="on"
			<?php
			if ( $value ) {
				echo( 'checked="checked"' );
			}
			?>
        />
        <label for="ezoic_cdn_enabled_on">Enabled</label>

        <input type="radio" id="ezoic_cdn_enabled_off" name="ezoic_cdn_enabled" value="off"
			<?php
			if ( ! $value ) {
				echo( 'checked="checked"' );
			}
			?>
        />
        <label for="ezoic_cdn_enabled_off">Disabled</label>
        <p class="description">
            Turn on automatic clearing of Ezoic caches when a post or page is updated.<br/><em>*Recommend enabling</em>
        </p>
		<?php
	}

	/**
	 * WordPress Settings Field for enabling/disabling verbose mode
	 *
	 * @return void
	 * @since 1.1.2
	 */
	function ezoic_cdn_always_home_field() {
		$checked = Ezoic_Integration_CDN::ezoic_cdn_always_purge_home( true );
		?>
        <input type="radio" id="ezoic_cdn_always_home_on" name="ezoic_cdn_always_home" value="on"
			<?php
			if ( $checked ) {
				echo( 'checked="checked"' );
			}
			?>
        />
        <label for="ezoic_cdn_always_home_on">Enabled</label>

        <input type="radio" id="ezoic_cdn_always_home_off" name="ezoic_cdn_always_home" value="off"
			<?php
			if ( ! $checked ) {
				echo( 'checked="checked"' );
			}
			?>
        />
        <label for="ezoic_cdn_always_home_off">Disabled</label>
        <p class="description">
            Will purge the home page whenever purging for any post (Automatic Recaching must be enabled).<br/><em>*Recommend enabling</em>
        </p>
        <p class="description" id="ez-advanced-collapse"><br/>
            [ <a href="#">show advanced options</a> ]
        </p>
		<?php
	}

	/**
	 * WordPress Settings Field for enabling/disabling verbose mode
	 *
	 * @return void
	 * @since 1.1.2
	 */
	function ezoic_cdn_verbose_field() {
		$checked = Ezoic_Integration_CDN::ezoic_cdn_verbose_mode( true );
		?>
        <input type="radio" id="ezoic_cdn_verbose_on" name="ezoic_cdn_verbose_mode" value="on"
			<?php
			if ( $checked ) {
				echo( 'checked="checked"' );
			}
			?>
        />
        <label for="ezoic_cdn_verbose_on">Enabled</label>

        <input type="radio" id="ezoic_cdn_verbose_off" name="ezoic_cdn_verbose_mode" value="off"
			<?php
			if ( ! $checked ) {
				echo( 'checked="checked"' );
			}
			?>
        />
        <label for="ezoic_cdn_verbose_off">Disabled</label>
        <p class="description" id="tagline-description">
            Outputs debug messages whenever submitting purge,
            <span style="color: red;font-weight: bold;">will slow down editing, leave disabled unless you need it</span>.
        </p>
		<?php
	}


}

?>
