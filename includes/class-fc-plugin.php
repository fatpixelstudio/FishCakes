<?php
/**
 * Plugin orchestrator.
 *
 * @package FishCakes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Boots the plugin and guards the minimum requirements.
 */
final class FC_Plugin {

	/**
	 * Minimum supported WordPress version.
	 */
	const MIN_WP = '6.4';

	/**
	 * Minimum supported PHP version.
	 */
	const MIN_PHP = '8.0';

	/**
	 * Registers all module hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! self::requirements_met() ) {
			add_action( 'admin_notices', array( __CLASS__, 'requirements_notice' ) );
			return;
		}

		FC_Frontend::init();
		FC_Blocker::init();
		if ( is_admin() ) {
			FC_Admin::init();
		}
	}

	/**
	 * Whether the current environment is supported.
	 *
	 * @return bool
	 */
	private static function requirements_met(): bool {
		return version_compare( get_bloginfo( 'version' ), self::MIN_WP, '>=' )
			&& version_compare( PHP_VERSION, self::MIN_PHP, '>=' );
	}

	/**
	 * Notices for unsupported environments.
	 *
	 * @return void
	 */
	public static function requirements_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			sprintf(
				/* translators: 1: required WordPress version, 2: required PHP version. */
				esc_html__( 'FishCakes requires WordPress %1$s or higher and PHP %2$s or higher. The plugin is not active on this site.', 'fishcakes' ),
				esc_html( self::MIN_WP ),
				esc_html( self::MIN_PHP )
			)
		);
	}
}
