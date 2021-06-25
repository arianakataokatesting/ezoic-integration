<?php

namespace Ezoic_Namespace;

/**
 * The settings of the plugin.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/admin
 */
include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-compatibility-check.php';
include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-cache-integrator.php';
include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-cache.php';

/**
 * Class Ezoic_Integration_Admin_Settings
 * @package Ezoic_Namespace
 */
class Ezoic_Integration_Admin_Settings {

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

	private $cacheType;

	private $cacheIdentity;

	private $cacheIntegrator;

	private $cache;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param      string $plugin_name The name of this plugin.
	 * @param      string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->cacheIntegrator = new Ezoic_Integration_Cache_Integrator;
		$this->cache = new Ezoic_Integration_Cache;

	}

	/**
	 * This function introduces the theme options into the 'Appearance' menu and into a top-level menu.
	 */
	public function setup_plugin_options_menu() {

		// Check for incompatible plugins with Ezoic
		$incompatible_plugins = Ezoic_Integration_Compatibility_Check::getActiveIncompatiblePlugins();

		$badge_count = count( $incompatible_plugins );
		if ( function_exists( 'is_wpe' ) ) {
			if ( is_wpe() && Ezoic_Integration_Admin::IsCloudIntegrated() == false ) {
				$badge_count ++;
			}
		}

		$incompatible_count   = '';
		if ( $badge_count > 0 ) {
			$incompatible_count = ' <span class="awaiting-mod">' . $badge_count . '</span>';
		}

		// Add the menu to the Plugins set of menu items
		add_options_page(
			EZOIC__PLUGIN_NAME,
			EZOIC__PLUGIN_NAME . $incompatible_count,
			'manage_options',
			EZOIC__PLUGIN_SLUG,
			array(
				$this,
				'render_settings_page_content',
			)
		);

	}

	/**
	 * Provides default values for the Display Options.
	 *
	 * @return array
	 */
	public function default_display_options() {

		$defaults = array(
			'is_integrated' => false,
			'check_time'    => '',
		);

		return $defaults;

	}

	/**
	 * Provide default values for the Social Options.
	 *
	 * @return array
	 */
	public function default_advanced_options() {

		$defaults = array(
			'verify_ssl' => true,
			'caching' => false,
		);

		return $defaults;

	}

	/**
	 * Renders a settings page
	 *
	 * @param string $active_tab
	 */
	public function render_settings_page_content( $active_tab = '' ) {

		$cdn_warning = "";
		$api_key     = Ezoic_Integration_CDN::ezoic_cdn_api_key();
		if ( ! empty( $api_key ) ) {
			$ping_test = Ezoic_Integration_CDN::ezoic_cdn_ping();
			if ( ! empty( $ping_test ) && is_array( $ping_test ) && $ping_test[0] == false ) {
				$cdn_warning = "<span class='dashicons dashicons-warning ez_error'></span>";
			} elseif ( ! empty( $api_key ) && get_option( 'ezoic_cdn_enabled' ) !== 'on' ) {
				$cdn_warning = "<span class='dashicons dashicons-warning ez_warning'></span>";
			}
		}

		?>
        <div class="wrap" id="ez_integration">
			<?php

				// Handles post requests for cache clearing. Displays an alert message upon success.
				if (empty($_POST)===false) {
					if ($_POST['action'] == 'clear_cache') {
						$this->handle_clear_cache();
			?>
						<div id="message" class="updated notice is-dismissible"><p><strong><?php _e( 'Cache successfully cleared!' ); ?></strong></p></div>
			<?php

					}
				}
			?>

            <p><img src="<?php echo plugins_url( '/admin/img', EZOIC__PLUGIN_FILE ); ?>/ezoic-logo.png" width="190"
                    height="40" alt="Ezoic"/></p>

			<?php if ( isset( $_GET['tab'] ) ) {
				$active_tab = $_GET['tab'];
			} elseif ( $active_tab == 'advanced_options' ) {
				$active_tab = 'advanced_options';
			} elseif ( $active_tab == 'cdn_settings' ) {
				$active_tab = 'cdn_settings';
			} else {
				$active_tab = 'integration_status';
			} // end if/else ?>

            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo EZOIC__PLUGIN_SLUG; ?>&tab=integration_status"
                   class="nav-tab <?php echo $active_tab == 'integration_status' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Integration Status',
						'ezoic' ); ?></a>
                <a href="?page=<?php echo EZOIC__PLUGIN_SLUG; ?>&tab=cdn_settings"
                   class="nav-tab <?php echo $active_tab == 'cdn_settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'CDN Settings',
			            'ezoic' ); ?> <?php echo $cdn_warning; ?></a>
                <a href="?page=<?php echo EZOIC__PLUGIN_SLUG; ?>&tab=advanced_options"
                   class="nav-tab <?php echo $active_tab == 'advanced_options' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Advanced Settings',
						'ezoic' ); ?></a>
				<?php if ( 'Ezoic' === EZOIC__SITE_NAME ) { ?>
                    <a href="https://support.ezoic.com/" target="_blank" class="nav-tab" id="help-tab">
						<?php _e( 'Help Center', 'ezoic' ); ?>
                    </a>
                    <a href="<?php echo EZOIC__SITE_LOGIN; ?>" target="_blank" class="nav-tab" id="pubdash-tab">
						<?php _e( 'Publisher Dashboard', 'ezoic' ); ?>
                    </a>
				<?php } ?>
            </h2>

            <form method="post" action="options.php" id="ezoic_settings">
				<?php

				if ( $active_tab == 'advanced_options' ) {
					settings_fields( 'ezoic_integration_options' );
					do_settings_sections( 'ezoic_integration_settings' );
					submit_button( 'Save Settings' );
				} elseif ( $active_tab == 'cdn_settings' ) {
					settings_fields( 'ezoic_cdn' );
					do_settings_sections( 'ezoic_cdn' );
					submit_button( 'Save Settings' );
				} else {
					settings_fields( 'ezoic_integration_status' );
					do_settings_sections( 'ezoic_integration_status' );
				} // end if/else

				?>
            </form>

        </div><!-- /.wrap -->
		<?php
	}

	public function general_options_callback() {
		$options = \get_option( 'ezoic_integration_status' );

		echo '<hr/>';
		self::display_notice( $options );

	} // end general_options_callback

	public function advanced_options_callback() {
		$options = \get_option( 'ezoic_integration_options' );

		echo '<p>' . __( 'These settings can be used to enhance your default WordPress integration. They should only be used if you are an advanced user and know what you are doing. If you have any questions, feel free to reach out to <a href="https://support.ezoic.com/" target="_blank" rel="noreferrer noopener">our support</a>.', 'ezoic' ) . '</p>';
		echo '<hr/>';
	} // end advanced_options_callback

	/**
	 * Initializes options page by registering the Sections, Fields, and Settings.
	 *
	 * This function is registered with the 'admin_init' hook.
	 */
	public function initialize_display_options() {

		// If the plugin options don't exist, create them.
		if ( false == \get_option( 'ezoic_integration_status' ) ) {
			$default_array = $this->default_display_options();
			add_option( 'ezoic_integration_status', $default_array );
		}

		add_settings_section(
			'general_settings_section',
			__( 'Integration Status', 'ezoic' ),
			array( $this, 'general_options_callback' ),
			'ezoic_integration_status'
		);

		add_settings_field(
			'is_integrated',
			__( 'Integration Status', 'ezoic' ),
			array( $this, 'is_integrated_callback' ),
			'ezoic_integration_status',
			'general_settings_section',
			array(
				//__( 'Activate this setting to display the header.', 'ezoic' ),
				//'class' => 'hidden',
			)
		);

        // Detect and display any incompatible or potentially incompatible plugins
		$hostingIssue = false;
        $incompatiblePlugins = Ezoic_Integration_Compatibility_Check::getActiveIncompatiblePlugins();
        $compatiblePlugins = Ezoic_Integration_Compatibility_Check::getCompatiblePluginsWithRecommendations();

		if ( function_exists( 'is_wpe' ) ) {
			if ( is_wpe() && Ezoic_Integration_Admin::IsCloudIntegrated() == false ) {
				$hostingIssue = true;
			}
		}

		if ( count( $incompatiblePlugins ) > 0 || count( $compatiblePlugins ) > 0 || $hostingIssue == true ) {
			add_settings_field(
				'plugin_compatibility',
				__( 'Compatibility Warning', 'ezoic' ),
				array( $this, 'plugin_compatibility_callback' ),
				'ezoic_integration_status',
				'general_settings_section',
				array( $incompatiblePlugins, $compatiblePlugins )
			);
		}

		add_settings_field(
			'check_time',
			__( 'Last Checked', 'ezoic' ),
			array( $this, 'check_time_callback' ),
			'ezoic_integration_status',
			'general_settings_section',
			array(//'class' => 'last_checked'
			)
		);


		register_setting(
			'ezoic_integration_status',
			'ezoic_integration_status'
		);

	} // end initialize_display_options

	public function handle_update_ezoic_integration_options($old_value, $new_value, $option ) {

		// Return if the caching value has not changed. This occurs when
		// another setting is updated and caching is left alone.
		if ($old_value['caching'] == $new_value['caching']) {
			return;
		}

		// Clear the cache just in case there are old files in it.
		$this->cache->Clear();

		// Remove the WP_CACHE define from wp-config.php.
		if ($this->cacheIntegrator->CleanWPConfig() === false) {
			$this->handle_caching_update_error($new_value, 'Unable to clean the wp-config.php file. Please make sure the file exists and has write-able permissions.');
			return;
		}

		// Remove the advanced cache file.
		if ($this->cacheIntegrator->RemoveAdvancedCache() === false) {
			$this->handle_caching_update_error($new_value, 'Unable to remove the advanced-cache.php file. Please make sure the file exists and has write-able permissions.');
			return;
		};

		// Only perform these steps if caching was just turned on.
		if ($new_value['caching'] == '1') {

			// Define WP_CACHE in wp-config.php.
			if ($this->cacheIntegrator->ConfigureWPConfig() === false) {
				$this->handle_caching_update_error($new_value, 'Unable to update the wp-config.php file. Please make sure the file exists and has write-able permissions.');
				return;
			}

			// Insert the advanced cache file.
			if ($this->cacheIntegrator->InsertAdvancedCache() === false) {
				$this->handle_caching_update_error($new_value, 'Unable to insert the advanced-cache.php file. Please make sure the /wp-content directory has write-able permissions.');
				return;
			}
		}
	}

	/**
	 *  If the site is cloud integrated and has caching enabled, disable caching and clean up any
	 *  files created because of it.
	 *
	 */
	public function handle_cloud_integrated_with_caching($plugin_admin) {
		if (!is_admin() || !$plugin_admin->IsCloudIntegrated()) {
			return;
		}

		$old_options = \get_option( 'ezoic_integration_options' );
		if (!isset($old_options['caching']) || $old_options['caching'] == 0) {
			return;
		}

		$new_options = $old_options;
		$new_options['caching'] = 0;
		\update_option( 'ezoic_integration_options', $new_options);
		$this->handle_update_ezoic_integration_options($old_options, $new_options, '');
	}

	public function handle_caching_update_error($options, $message) {

		// Handle errors while trying to turn on caching.
		add_settings_error('caching', 'caching-error', "Error while configuring Ezoic Caching: $message");
		$options['caching'] = '0';
		\update_option('ezoic_integration_options', $options);

	}

	/**
	 *  Clears the cache when ezoic caching is enabled.
	 *
	 *  This function is registered with the 'post_updated' and 'comment_post' hooks.
	 */
	public function handle_clear_cache() {
		if (defined('EZOIC_CACHE') && EZOIC_CACHE) {
			$this->cache->Clear();
		}
	}

	/**
	 * Initializes the advanced options by registering the Sections, Fields, and Settings.
	 *
	 * This function is registered with the 'admin_init' hook.
	 */
	public function initialize_advanced_options() {

		//delete_option( 'ezoic_integration_options' );
		if ( false == \get_option( 'ezoic_integration_options' ) ) {
			$default_array = $this->default_advanced_options();
			update_option( 'ezoic_integration_options', $default_array );
		} // end if

		add_settings_section(
			'advanced_settings_section',
			__( 'Advanced Settings', 'ezoic' ),
			array( $this, 'advanced_options_callback' ),
			'ezoic_integration_settings'
		);

		add_settings_field(
			'caching',
			'WordPress Caching (In Beta)',
			array( $this, 'caching_callback' ),
			'ezoic_integration_settings',
			'advanced_settings_section',
			array()
		);

		add_settings_field(
			'verify_ssl',
			'Verify SSL',
			array( $this, 'verify_ssl_callback' ),
			'ezoic_integration_settings',
			'advanced_settings_section',
			array(
				__( 'Turns off SSL verification. Recommended to Yes. Only disable if experiencing SSL errors.', 'ezoic' ),
			)
		);

		register_setting(
			'ezoic_integration_options',
			'ezoic_integration_options'
		);

	}

	public function is_integrated_callback( $args ) {

		$options = \get_option( 'ezoic_integration_status' );

		$html = '<input type="hidden" id="is_integrated" name="ezoic_integration_status[is_integrated]" value="1" ' . checked( 1,
				isset( $options['is_integrated'] ) ? $options['is_integrated'] : 0, false ) . '/>';

		$html .= '<div>';
		if ( $options['is_integrated'] ) {

			$html .= '<p class="text-success"><strong>Active';
			if ( Ezoic_Integration_Admin::IsCloudIntegrated() ) {
				$html .= ' &nbsp;<span class="dashicons dashicons-cloud text-success" title="Cloud Integrated"></span>';
			} else {
				$html .= ' &nbsp;<span class="dashicons dashicons-wordpress-alt text-success" title="WordPress Integrated"></span>';
			}
			$html .= '</strong></p>';

		} else {
			$html .= '<p class="text-danger"><strong>Inactive</strong></p>';
		}
		$html .= '</div>';

		echo $html;

	} // end is_integrated_callback

	public function check_time_callback() {

		$options = \get_option( 'ezoic_integration_status' );

		$html = '<input type="hidden" id="check_time" name="ezoic_integration_status[check_time]" value="' . $options['check_time'] . '"/>';
		$html .= '<div>' . date( 'm/d/Y H:i:s',
				$options['check_time'] ) . ' &nbsp; [<a href="?page=' . EZOIC__PLUGIN_SLUG . '&tab=integration_status&recheck=1">recheck</a>]</div>';

		echo $html;

	} // end check_time_callback


	public function verify_ssl_callback( $args ) {

		$options = \get_option( 'ezoic_integration_options' );

		$html = '<select id="verify_ssl" name="ezoic_integration_options[verify_ssl]">';
		$html .= '<option value="1" ' . selected( $options['verify_ssl'], 1, false ) . '>' . __( 'Yes',
				'ezoic' ) . '</option>';
		$html .= '<option value="0" ' . selected( $options['verify_ssl'], 0, false ) . '>' . __( 'No',
				'ezoic' ) . '</option>';
		$html .= '</select>';
		$html .= '<td><p>' . $args[0] . '</p></td>';

		echo $html;

	} // end verify_ssl_callback

	public function caching_callback( $args ) {

		$options = \get_option( 'ezoic_integration_options' );
		$disabled_text = '';
		$warning_text = '<td><p>Caches your site\'s pages directly on your WordPress server in order to decrease response time for your users.</p>';

		// If caching is currently turned off, make sure there is no advanced-cache.php file.
		// If there is one, it means that another caching plugin is in use and that we should
		// not allow the user to use Ezoic caching.
		if (!$options['caching']) {

			if (!$this->cacheIntegrator->HasValidSetup()) {
				$disabled_text = 'disabled';
				$warning_text .= '<br><br/><b>Ezoic\'s WordPress Caching cannot be turned on for the following reason(s):</b><ul>';


				// If the pub is cloud integrated, they do not need to use Ezoic WordPress Caching. Only show that message and disregard any of the other issues because they are not important.
				if (Ezoic_Integration_Admin::IsCloudIntegrated()) {
					$warning_text .= "<li>Your site is integrated through an Ezoic Cloud Integration which already handles caching for you. You do not need to use Ezoic's WordPress Caching.</li>";
				} else {
					if ($this->cacheIntegrator->HasAdvancedCache()) {
						$warning_text .= '<li>Ezoic\'s WordPress Caching does not work with other caching plugins. To use caching, please first deactivate your other <a href="' . get_admin_url( null, 'plugins.php' ) . '">caching plugins</a>, and then remove the advanced-cache.php file in the wp-content directory if it still exists.</li>';
					}

					if (!$this->cacheIntegrator->HasFancyPermalinks()) {
						$warning_text .= '<li>Ezoic\'s WordPress Caching does not work with the WordPress \'Plain\' permalink structure. To use caching, please change to a different <a href="' . get_admin_url( null, 'options-permalink.php' ) . '">permalink URL structure</a> (such as \'Post name\').</li>';
					}

					if (!$this->cacheIntegrator->HasWriteableWPConfig()) {
						$warning_text .= "<li>The wp-config.php file is not write-able. Please update the permissions by running: <b>chmod 777 " . $this->cacheIntegrator->config_path . "</b> on your server.</li>";
					}

					if (!$this->cacheIntegrator->HasWriteableWPContent()) {
						$warning_text .= "<li>The /wp-content directory is not write-able. Please update the permissions by running: <b>chmod 777 " . WP_CONTENT_DIR . "</b> on your server.</li>";
					}
				}

				$warning_text .= '</ul>';
			}
		}

		$warning_text .= '</td>';
		$html = '<select id="caching" name="ezoic_integration_options[caching]">';
		$html .= '<option value="0" ' . selected( $options['caching'], 0, false ) . '>' . __( 'Off',
				'ezoic' ) . '</option>';
		$html .= '<option value="1" ' . selected( $options['caching'], 1, false ) . $disabled_text .'>' . __( 'On',
				'ezoic' ) . '</option>';
		$html .= '</select>';
		$html .= $warning_text;

		// If caching is enabled, create a button that will allow the user to clear the cache.
		if ($options['caching']) {
			$html .= '
			</form>
			<td>
				<form action="" method="POST">
					<input type="hidden" name="action" value="clear_cache"/>
					<input class="button button-primary" type="submit" value="Clear Cache"/>
				</form>
			</td>';
		}

		echo $html;
	}

    public function plugin_compatibility_callback($args) {
        $html = '';

        $incompatiblePlugins = $args[0];
	    $compatiblePlugins = $args[1];

	    // Check if running on WPEngine on non cloud sites
	    if ( function_exists( 'is_wpe' ) ) {
		    if ( is_wpe() && Ezoic_Integration_Admin::IsCloudIntegrated() == false ) {
			    $html .= '<h3><span class="dashicons dashicons-warning text-danger"></span> Incompatibility with WPEngine</h3>';
			    $html .= 'There are incompatibilities with Ezoic WordPress integration and WPEngine hosting. We recommending switching to Ezoic Cloud integration. <a href="' . EZOIC__SITE_LOGIN . '?redirect=%2Fintegration" target="_blank">Click here to explore other integration options</a>.<br /><br />';
			    $html .= 'Learn how to successfully <a href="https://support.ezoic.com/kb/article/integrating-ezoic-with-wpengine" target="_blank">integrate Ezoic with WPEngine</a>.<br/><br />';
		    }
	    }

	    // incompatible plugins
        if (count($incompatiblePlugins) > 0) {
	        $html .= '<h3><span class="dashicons dashicons-warning text-danger"></span> Incompatible Plugins Detected</h3>';

	        if ( Ezoic_Integration_Admin::IsCloudIntegrated() ) {
		        $html .= 'The following plugin(s) must be disabled to fully utilize Ezoic without issues or conflicts. ';
		        if ( count( $compatiblePlugins ) > 0 ) {
			        $html .= 'See Ezoic Recommendations below.';
		        }
	        } else {
		        $html .= 'The following plugin(s) must be disabled to fully utilize <strong>Ezoic WordPress integration</strong> without issues or conflicts.<br/>We recommend switching to our <a href="' . EZOIC__SITE_LOGIN . '?redirect=%2Fintegration" target="_blank">Cloud Integration</a> for improved speed and compatibility';
		        if ( count( $compatiblePlugins ) > 0 ) {
			        $html .= ', or review additional Ezoic Recommendations below';
		        }
		        $html .= '.';
	        }
	        $html .= '<br /><br /><br/>';

            foreach ($incompatiblePlugins as $plugin) {
                $html .= '<strong>' . $plugin['name'] . ' (' . $plugin['version'] . ') </strong>';
                $html .= '<br />';
                $html .= $plugin['message'];

	            $deactivateLink = Ezoic_Integration_Compatibility_Check::pluginActionUrl($plugin['filename']);
	            $html .= '<br/><p><a class="button button-primary" href="' . $deactivateLink . '">Deactivate Plugin</a></p>';

                $html .= '<br /><br />';
            }
        }

        // show compatible plugins that can be replaced by Ezoic product (eg. Site Speed) and display recommendations
        if (count($compatiblePlugins) > 0) {
	        if (count($incompatiblePlugins) > 0) {
		        $html .= '<hr/><br/>';
	        }

	        $pluginString = '';
            foreach ($compatiblePlugins as $plugin) {
                $pluginString .= '<strong>'. $plugin['name'] .'</strong><br />';
                $pluginString .= $plugin['message'] .'<br /><br />';
            }
            $html .= '<h3>Ezoic Recommendations</h3>
                      We recommend using <strong>Ezoic\'s Site Speed Optimization</strong> features for caching and performance improvements.<br />'
                  .  'The following plugin(s) <i>may or may not</i> be compatible with Ezoic:<br /><br />'
                  .   $pluginString . '<br />';
        }

        echo $html;
    }

	public function display_notice( $options ) {

		$type = '';

		$cacheIndetifier     = new Ezoic_Integration_Cache_Identifier();
		$this->cacheIdentity = $cacheIndetifier->GetCacheIdentity();
		$this->cacheType     = $cacheIndetifier->GetCacheType();

		$time_check = current_time( 'timestamp' ) - 21600; // 6 hours
		if ( $options['is_integrated'] == '' || $options['check_time'] <= $time_check || ( isset( $_GET['recheck'] ) && $_GET['recheck'] ) ) {

			$results = $this->getIntegrationCheckEzoicResponse();

			$update                  = array();
			$update['is_integrated'] = $results['result'];
			$update['check_time']    = current_time( 'timestamp' );
			update_option( 'ezoic_integration_status', $update );

			if ( false === $results['result'] ) {

				if ( ! empty( $results['error'] ) ) {
					$args = apply_filters(
						'ezoic_view_arguments',
						array( 'type' => 'integration_error' ),
						'ezoic-integration-admin'
					);
				} else {
					$args = apply_filters(
						'ezoic_view_arguments',
						array( 'type' => 'not_integrated' ),
						'ezoic-integration-admin'
					);
				}

				foreach ( $args as $key => $val ) {
					$$key = $val;
				}

			}
			$is_integrated = $results['result'];

			$file = EZOIC__PLUGIN_DIR . 'admin/partials/' . 'ezoic-integration-admin-display' . '.php';
			include( $file );

		} else {
			$is_integrated = $options['is_integrated'];
		}


	}

	private function getIntegrationCheckEzoicResponse() {

		$content  = 'ezoic integration test';
		$response = $this->requestDataFromEzoic( $content );

		return $response;

	}

	private function requestDataFromEzoic( $final_content ) {

		$timeout = 5;

		$cache_key = md5( $final_content );

		$request_data = Ezoic_Integration_Request_Utils::GetRequestBaseData();

		$request_params = array(
			'cache_key'                    => $cache_key,
			'action'                       => 'get-index-series',
			'content_url'                  => get_home_url() . '?ezoic_domain_verify=1',
			'request_headers'              => $request_data["request_headers"],
			'response_headers'             => $request_data["response_headers"],
			'http_method'                  => $request_data["http_method"],
			'ezoic_api_version'            => $request_data["ezoic_api_version"],
			'ezoic_wp_integration_version' => $request_data["ezoic_wp_plugin_version"],
			'content'                      => $final_content,
			'request_type'                 => 'with_content',
		);

		$ezoic_options = \get_option( 'ezoic_integration_options' );

		if ( $this->cacheType != Ezoic_Cache_Type::NO_CACHE ) {

			$settings = array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL            => $request_data["ezoic_request_url"],
				CURLOPT_TIMEOUT        => $timeout,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTPHEADER     => array(
					'X-Wordpress-Integration: true',
					'X-Forwarded-For: ' . $request_data["client_ip"],
					'Content-Type: application/x-www-form-urlencoded',
					'Expect:',
				),
				CURLOPT_POST           => true,
				CURLOPT_HEADER         => true,
				CURLOPT_POSTFIELDS     => http_build_query( $request_params ),
				CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT'],
			);

			if ( $ezoic_options['verify_ssl'] == false ) {
				$settings[ CURLOPT_SSL_VERIFYPEER ] = false;
				$settings[ CURLOPT_SSL_VERIFYHOST ] = false;
			}

			$result = Ezoic_Integration_Request_Utils::MakeCurlRequest( $settings );

			if ( ! empty( $result['error'] ) ) {
				return array( "result" => false, "error" => $result['error'] );
			}

		} else {

			unset( $request_data["request_headers"]["Content-Length"] );
			$request_data["request_headers"]['X-Wordpress-Integration'] = 'true';

			$settings = array(
				'timeout' => $timeout,
				'body'    => $request_params,
				'headers' => array(
					'X-Wordpress-Integration' => 'true',
					'X-Forwarded-For'         => $request_data["client_ip"],
					'Expect'                  => ''
				),
			);

			if ( $ezoic_options['verify_ssl'] == false ) {
				$settings['sslverify'] = false;
			}

			$result = wp_remote_post( $request_data["ezoic_request_url"], $settings );

			if ( is_wp_error( $result ) ) {
				return array( "result" => false, "error" => $result->get_error_message() );
			}

		}

		if ( is_array( $result ) && isset( $result['body'] ) ) {
			$final = $result['body'];
		} else {
			$final = $result;
		}

		return array( "result" => $this->ParsePageContents( $final ) );

	}

	private function ParsePageContents( $contents ) {
		if ( strpos( $contents, 'This site is operated by Ezoic and Wordpress Integrated' ) !== false ) {
			return true;
		}

		return false;
	}
}
