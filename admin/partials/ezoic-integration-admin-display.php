<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/admin/partials
 */
?>

<?php if ( $type == 'not_integrated' ) : ?>
    <div class="error notice update-message notice-error">
        <p><?php
			_e( '<strong>INTEGRATION NOT SUCCESSFUL!</strong> - 
                    <a href="' . EZOIC__SITE_LOGIN . '" target="_blank">Check your integration status here.</a>',
				'ezoic' );
			?></p>
    </div>
<?php elseif ( $type == 'integration_error' ) : ?>
    <div class="error notice update-message notice-error">
        <p><?php
			_e( '<strong>INTEGRATION ERROR:</strong>&nbsp; ' . $results['error'], 'ezoic' );
			?></p>
    </div>
<?php elseif ( $is_integrated ) : ?>
    <div class="updated notice">
        <p><strong>SUCCESS!</strong>&nbsp; You are now fully integrated with <?php echo EZOIC__SITE_NAME; ?>!</p>
    </div>
<?php endif; ?>
