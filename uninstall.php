<?php
/**
 * FishCakes uninstall: removes all stored settings.
 *
 * On multisite the option lives per site, so every site is cleaned up.
 * Consent cookies live in the visitor's browser, not in the database,
 * so there is nothing else to remove.
 *
 * @package FishCakes
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		delete_option( 'fishcakes_settings' );
		restore_current_blog();
	}
} else {
	delete_option( 'fishcakes_settings' );
}
