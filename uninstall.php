<?php
/**
 * FishCakes uninstall: removes all stored settings.
 *
 * Consent cookies live in the visitor's browser, not in the database,
 * so there is nothing else to clean up.
 *
 * @package FishCakes
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'fishcakes_settings' );
