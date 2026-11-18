<?php
/**
 * Server-side consent state (reads the cookie the front-end runtime writes).
 *
 * @package FishCakes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads and validates the consent cookie.
 *
 * The cookie is strictly necessary (it only stores the visitor's own
 * choice), so it may be placed before consent under the GDPR/AVG.
 */
class FC_Consent {

	/**
	 * Consent cookie name.
	 */
	const COOKIE = 'fc_consent';

	/**
	 * The validated consent record, or null when no valid consent exists.
	 *
	 * A record is valid when it matches the current banner version and
	 * has not expired. An invalid record is treated as "no choice".
	 *
	 * @return array{necessary:bool,analytical:bool,marketing:bool,ts:int}|null
	 */
	public static function get(): ?array {
		if ( ! isset( $_COOKIE[ self::COOKIE ] ) ) {
			return null;
		}

		$data = json_decode( (string) wp_unslash( $_COOKIE[ self::COOKIE ] ), true );

		if ( ! is_array( $data ) ) {
			return null;
		}

		$settings = FC_Settings::get();

		if ( (int) ( $data['ver'] ?? 0 ) !== (int) $settings['banner_version'] ) {
			return null;
		}

		$ts     = (int) ( $data['ts'] ?? 0 );
		$maxAge = max( 1, (int) $settings['consent_expiry'] ) * DAY_IN_SECONDS;

		if ( $ts < 1 || ( time() - $ts ) > $maxAge ) {
			return null;
		}

		return array(
			'necessary'  => true,
			'analytical' => ! empty( $data['analytical'] ),
			'marketing'  => ! empty( $data['marketing'] ),
			'ts'         => $ts,
		);
	}

	/**
	 * Whether consent has been given for a category.
	 *
	 * @param string $category One of "analytical" or "marketing".
	 * @return bool
	 */
	public static function allowed( string $category ): bool {
		$consent = self::get();

		return $consent ? ! empty( $consent[ $category ] ) : false;
	}
}
