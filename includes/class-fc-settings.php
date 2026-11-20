<?php
/**
 * Settings storage, defaults, sanitizing and text resolution.
 *
 * @package FishCakes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Single autoloaded option holding all FishCakes settings.
 */
class FC_Settings {

	/**
	 * Option name.
	 */
	const OPTION = 'fishcakes_settings';

	/**
	 * Full settings schema with defaults.
	 *
	 * Empty strings mean "use the translated default".
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			'enabled'          => true,
			'position'         => 'bottom',
			'consent_expiry'   => 182,
			'banner_version'   => 1,
			'privacy_page'     => 0,
			'auto_footer_link' => true,
			'cache_safe'       => false,

			'texts'            => array(
				'banner_title'        => '',
				'banner_intro'        => '',
				'privacy_link_label'  => '',
				'button_accept'       => '',
				'button_reject'       => '',
				'button_save'         => '',
				'button_options'      => '',
				'saved_message'       => '',
				'footer_link_text'    => '',
				'embed_button'        => '',
				'embed_text_nochoice' => '',
				'embed_text_blocked'  => '',
				'necessary_label'     => '',
				'necessary_desc'      => '',
				'analytical_label'    => '',
				'analytical_desc'     => '',
				'marketing_label'     => '',
				'marketing_desc'      => '',
			),

			'colors'           => array(
				'bg'           => '',
				'text'         => '',
				'muted'        => '',
				'border'       => '',
				'primary'      => '',
				'primary_text' => '',
			),

			'radius'           => '',

			/*
			 * Extra classes added next to the plugin's own fc-* classes, so
			 * the banner and placeholders pick up a theme's design system.
			 * Defaults match the Fat Pixel NoodleSoup starter kit; an empty
			 * value adds nothing.
			 */
			'element_styles'   => true,
			'classes'          => array(
				'heading'          => 'gamma-heading',
				'button'           => 'button',
				'button_primary'   => 'button--primary',
				'button_secondary' => 'button--secondary',
				'link'             => 'link',
			),

			'blocker_enabled'  => true,
			'providers'        => array(
				'youtube'    => true,
				'vimeo'      => true,
				'spotify'    => true,
				'soundcloud' => true,
				'googlemaps' => true,
			),
			'custom_hosts'     => '',

			'scripts'          => array(),
		);
	}

	/**
	 * Effective settings: saved values merged over defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function get(): array {
		static $settings = null;
		if ( null !== $settings ) {
			return $settings;
		}

		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();

		$settings = array();
		foreach ( self::defaults() as $key => $default ) {
			if ( 'scripts' === $key ) {
				/*
				 * List-shaped setting (repeater rows). Rows are fully
				 * sanitized on save, so the stored list is used as-is —
				 * merging it against the (empty) default list would wipe it.
				 */
				$settings[ $key ] = is_array( $saved[ $key ] ?? null ) ? $saved[ $key ] : array();
			} elseif ( is_array( $default ) ) {
				$settings[ $key ] = self::merge_assoc( $saved[ $key ] ?? null, $default );
			} else {
				$settings[ $key ] = $saved[ $key ] ?? $default;
			}
		}

		/**
		 * Filters the effective FishCakes settings.
		 *
		 * @param array<string,mixed> $settings Effective settings.
		 */
		$settings = apply_filters( 'fishcakes_settings', $settings );

		return $settings;
	}

	/**
	 * Merges saved keys over defaults, ignoring unknown keys.
	 *
	 * @param mixed               $saved    Saved value, possibly null.
	 * @param array<string,mixed> $defaults Default values.
	 * @return array<string,mixed>
	 */
	private static function merge_assoc( $saved, array $defaults ): array {
		if ( ! is_array( $saved ) ) {
			return $defaults;
		}

		$out = $defaults;
		foreach ( $saved as $key => $value ) {
			if ( array_key_exists( $key, $defaults ) ) {
				$out[ $key ] = $value;
			}
		}

		return $out;
	}

	/**
	 * Translated default texts.
	 *
	 * @return array<string,string>
	 */
	public static function default_texts(): array {
		return array(
			'banner_title'        => __( 'Cookies', 'fishcakes' ),
			'banner_intro'        => __( 'We use cookies and similar technologies on this website. Choose which cookies we may place. You can change your choice at any time. See our {privacy_link} for more information.', 'fishcakes' ),
			'privacy_link_label'  => __( 'privacy policy', 'fishcakes' ),
			'button_accept'       => __( 'Accept all cookies', 'fishcakes' ),
			'button_reject'       => __( 'Reject all cookies', 'fishcakes' ),
			'button_save'         => __( 'Save choices', 'fishcakes' ),
			'button_options'      => __( 'Change choices', 'fishcakes' ),
			'saved_message'       => __( 'Your cookie preferences have been saved.', 'fishcakes' ),
			'footer_link_text'    => __( 'Cookie settings', 'fishcakes' ),
			'embed_button'        => __( 'Cookie settings', 'fishcakes' ),
			'embed_text_nochoice' => __( 'This content comes from an external service that may place marketing cookies. You have not made a choice about cookies yet, so we are not showing it.', 'fishcakes' ),
			'embed_text_blocked'  => __( 'This content comes from an external service that may place marketing cookies. Your cookie preferences do not allow that right now.', 'fishcakes' ),
			'necessary_label'     => __( 'Necessary', 'fishcakes' ),
			'necessary_desc'      => __( 'Necessary cookies are needed for this website to work — for example, to remember your cookie choices. These cannot be switched off.', 'fishcakes' ),
			'analytical_label'    => __( 'Analytical', 'fishcakes' ),
			'analytical_desc'     => __( 'Analytical cookies measure how this website is used — for example which pages are visited and for how long. We use this data to improve the website.', 'fishcakes' ),
			'marketing_label'     => __( 'Marketing & social media', 'fishcakes' ),
			'marketing_desc'      => __( 'Marketing and social media cookies are placed by third parties — such as YouTube, Vimeo and Spotify — to show embedded content and to tailor advertisements to your interests.', 'fishcakes' ),
		);
	}

	/**
	 * Resolves a text: custom value when set, translated default otherwise.
	 *
	 * @param string $key Text key.
	 * @return string
	 */
	public static function text( string $key ): string {
		$custom = self::get()['texts'][ $key ] ?? '';

		if ( is_string( $custom ) && '' !== trim( $custom ) ) {
			return $custom;
		}

		return self::default_texts()[ $key ] ?? '';
	}

	/**
	 * Inline HTML allowed in editable texts.
	 *
	 * @return array<string,array<string,true>>
	 */
	public static function allowed_text_html(): array {
		return array(
			'a'      => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
			'i'      => array(),
			'code'   => array(),
			'br'     => array(),
			'span'   => array(),
		);
	}

	/**
	 * Known embed providers (label + matching host patterns).
	 *
	 * @return array<string,array{label:string,hosts:string[]}>
	 */
	private static function known_providers(): array {
		return array(
			'youtube'    => array(
				/* translators: Embed provider name. */
				'label' => __( 'YouTube', 'fishcakes' ),
				'hosts' => array( 'youtube.com', 'youtu.be', 'youtube-nocookie.com' ),
			),
			'vimeo'      => array(
				/* translators: Embed provider name. */
				'label' => __( 'Vimeo', 'fishcakes' ),
				'hosts' => array( 'vimeo.com' ),
			),
			'spotify'    => array(
				/* translators: Embed provider name. */
				'label' => __( 'Spotify', 'fishcakes' ),
				'hosts' => array( 'spotify.com' ),
			),
			'soundcloud' => array(
				/* translators: Embed provider name. */
				'label' => __( 'SoundCloud', 'fishcakes' ),
				'hosts' => array( 'soundcloud.com' ),
			),
			'googlemaps' => array(
				/* translators: Embed provider name. */
				'label' => __( 'Google Maps', 'fishcakes' ),
				'hosts' => array( 'google.com' ),
			),
		);
	}

	/**
	 * All active providers: known ones that are enabled plus custom hosts.
	 *
	 * @return array<string,array{label:string,hosts:string[]}>
	 */
	public static function providers(): array {
		$enabled  = self::get()['providers'];
		$settings = array();

		foreach ( self::known_providers() as $slug => $provider ) {
			if ( ! empty( $enabled[ $slug ] ) ) {
				$settings[ $slug ] = $provider;
			}
		}

		foreach ( self::parse_custom_hosts() as $index => $custom ) {
			$settings[ 'custom-' . $index ] = $custom;
		}

		/**
		 * Filters the list of blocked embed providers.
		 *
		 * @param array<string,array{label:string,hosts:string[]}> $settings Active providers.
		 */
		return apply_filters( 'fishcakes_providers', $settings );
	}

	/**
	 * Parses the "custom hosts" textarea: one host per line,
	 * optionally followed by | and a label.
	 *
	 * @return array<int,array{label:string,hosts:string[]}>
	 */
	private static function parse_custom_hosts(): array {
		$raw = (string) self::get()['custom_hosts'];
		$out = array();

		foreach ( preg_split( '/\r\n|\r|\n/', $raw ) ?: array() as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}

			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			$host  = strtolower( $parts[0] );

			if ( ! preg_match( '/^[a-z0-9][a-z0-9.-]*[a-z0-9]$/', $host ) ) {
				continue;
			}

			$out[] = array(
				'label' => isset( $parts[1] ) && '' !== $parts[1] ? $parts[1] : $host,
				'hosts' => array( $host ),
			);
		}

		return $out;
	}

	/**
	 * Finds the active provider matching a URL (host or any of its parents).
	 *
	 * @param string $url Embed URL.
	 * @return array{slug:string,label:string}|null
	 */
	public static function match_provider( string $url ): ?array {
		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! is_string( $host ) || '' === $host ) {
			return null;
		}

		$host = strtolower( $host );

		foreach ( self::providers() as $slug => $provider ) {
			foreach ( (array) $provider['hosts'] as $pattern ) {
				$pattern = strtolower( (string) $pattern );

				if ( $host === $pattern || str_ends_with( $host, '.' . $pattern ) ) {
					return array(
						'slug'  => (string) $slug,
						'label' => (string) $provider['label'],
					);
				}
			}
		}

		return null;
	}

	/**
	 * Sanitizes the whole settings array (register_setting callback).
	 *
	 * Script code is intentionally stored raw: it can only be entered by
	 * users with the manage_options capability, who can already add
	 * arbitrary code via themes and other plugins.
	 *
	 * @param mixed $input Submitted settings.
	 * @return array<string,mixed>
	 */
	public static function sanitize( $input ): array {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();

		$out                       = $defaults;
		$out['enabled']            = ! empty( $input['enabled'] );
		$out['position']           = in_array( $input['position'] ?? '', array( 'bottom', 'top' ), true ) ? $input['position'] : 'bottom';
		$out['consent_expiry']     = self::clamp_expiry( $input['consent_expiry'] ?? 0 );
		$out['banner_version']     = max( 1, absint( $input['banner_version'] ?? 1 ) );
		$out['privacy_page']       = absint( $input['privacy_page'] ?? 0 );
		$out['auto_footer_link']   = ! empty( $input['auto_footer_link'] );
		$out['cache_safe']         = ! empty( $input['cache_safe'] );
		$out['blocker_enabled']    = ! empty( $input['blocker_enabled'] );
		$out['element_styles']     = ! empty( $input['element_styles'] );
		$out['radius']             = self::sanitize_radius( (string) ( $input['radius'] ?? '' ) );

		foreach ( array_keys( $defaults['texts'] ) as $key ) {
			$raw                  = (string) ( $input['texts'][ $key ] ?? '' );
			$out['texts'][ $key ] = trim( wp_kses( $raw, self::allowed_text_html() ) );
		}

		foreach ( array_keys( $defaults['colors'] ) as $key ) {
			$color                 = sanitize_hex_color( (string) ( $input['colors'][ $key ] ?? '' ) );
			$out['colors'][ $key ] = $color ?: '';
		}

		foreach ( array_keys( $defaults['classes'] ) as $key ) {
			$out['classes'][ $key ] = self::sanitize_classes( (string) ( $input['classes'][ $key ] ?? '' ) );
		}

		foreach ( array_keys( $defaults['providers'] ) as $slug ) {
			$out['providers'][ $slug ] = ! empty( $input['providers'][ $slug ] );
		}

		$out['custom_hosts'] = self::sanitize_custom_hosts( (string) ( $input['custom_hosts'] ?? '' ) );
		$out['scripts']      = self::sanitize_scripts( (array) ( $input['scripts'] ?? array() ) );

		return $out;
	}

	/**
	 * Clamps the consent expiry between 1 and 365 days.
	 *
	 * @param mixed $value Submitted value.
	 * @return int
	 */
	private static function clamp_expiry( $value ): int {
		$days = absint( $value );
		if ( $days < 1 ) {
			$days = 182;
		}

		return min( 365, $days );
	}

	/**
	 * Sanitizes a space-separated list of CSS class names.
	 *
	 * @param string $value Submitted value.
	 * @return string
	 */
	public static function sanitize_classes( string $value ): string {
		$classes = preg_split( '/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY ) ?: array();
		$classes = array_filter( array_map( 'sanitize_html_class', $classes ) );

		return implode( ' ', array_unique( $classes ) );
	}

	/**
	 * Builds a class attribute value: the plugin's own classes followed by
	 * the configured theme classes for the given keys.
	 *
	 * @param string   $base Plugin classes (fc-*), always kept.
	 * @param string[] $keys Keys of the "classes" setting to append.
	 * @return string Sanitized, space-separated class list.
	 */
	public static function classes( string $base, array $keys = array() ): string {
		$extra = self::get()['classes'];
		$list  = array( $base );

		foreach ( $keys as $key ) {
			$list[] = (string) ( $extra[ $key ] ?? '' );
		}

		return self::sanitize_classes( implode( ' ', $list ) );
	}

	/**
	 * Sanitizes a border radius value (px, em, rem or %).
	 *
	 * @param string $value Submitted value.
	 * @return string
	 */
	private static function sanitize_radius( string $value ): string {
		$value = trim( $value );

		return preg_match( '/^\d+(px|em|rem|%)$/', $value ) ? $value : '';
	}

	/**
	 * Sanitizes the custom hosts textarea back to "host|Label" lines.
	 *
	 * @param string $value Submitted value.
	 * @return string
	 */
	private static function sanitize_custom_hosts( string $value ): string {
		$lines = array();

		foreach ( preg_split( '/\r\n|\r|\n/', $value ) ?: array() as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}

			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			$host  = strtolower( $parts[0] );

			if ( ! preg_match( '/^[a-z0-9][a-z0-9.-]*[a-z0-9]$/', $host ) ) {
				continue;
			}

			$label   = isset( $parts[1] ) ? sanitize_text_field( $parts[1] ) : '';
			$lines[] = $host . ( '' !== $label ? '|' . $label : '' );
		}

		return implode( "\n", $lines );
	}

	/**
	 * Sanitizes the script repeater rows.
	 *
	 * @param array<int,mixed> $rows Submitted rows.
	 * @return array<int,array<string,mixed>>
	 */
	private static function sanitize_scripts( array $rows ): array {
		$out     = array();
		$dropped = 0;

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$type = in_array( $row['type'] ?? '', array( 'inline', 'url' ), true ) ? $row['type'] : 'inline';
			$code = (string) ( $row['code'] ?? '' );
			$url  = esc_url_raw( (string) ( $row['url'] ?? '' ) );

			// Accept complete <script> snippets: strip the wrapper and,
			// for src-only tags, treat them as URL scripts.
			$unwrapped = self::unwrap_script_tag( $code );

			if ( null !== $unwrapped['url'] ) {
				$type = 'url';
				$url  = $url ?: esc_url_raw( $unwrapped['url'] );
				$code = '';
			} else {
				$code = $unwrapped['code'];
			}

			// Defense in depth: only http(s) sources are accepted.
			// esc_url_raw already rejects other schemes (javascript:, …);
			// this explicit check keeps the guarantee even if the escaping
			// pipeline ever changes.
			if ( '' !== $url ) {
				$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );

				if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
					$url = '';
				}
			}

			// Drop empty or invalid rows entirely.
			if ( 'url' === $type && '' === $url ) {
				$dropped++;
				continue;
			}
			if ( 'inline' === $type && '' === trim( $code ) ) {
				$dropped++;
				continue;
			}

			$out[] = array(
				'id'       => sanitize_key( (string) ( $row['id'] ?? '' ) ) ?: 'fc' . wp_rand( 10000, 99999 ),
				'name'     => sanitize_text_field( (string) ( $row['name'] ?? '' ) ),
				'enabled'  => ! empty( $row['enabled'] ),
				'category' => in_array( $row['category'] ?? '', array( 'analytical', 'marketing' ), true ) ? $row['category'] : 'analytical',
				'location' => in_array( $row['location'] ?? '', array( 'head', 'foot' ), true ) ? $row['location'] : 'foot',
				'type'     => $type,
				'code'     => $code,
				'url'      => $url,
			);
		}

		if ( $dropped > 0 ) {
			add_settings_error(
				self::OPTION,
				'fc_scripts_dropped',
				sprintf(
					/* translators: %d: number of script rows. */
					_n(
						'FishCakes: %d script was not saved because it was empty or its URL was invalid.',
						'FishCakes: %d scripts were not saved because they were empty or their URLs were invalid.',
						$dropped,
						'fishcakes'
					),
					$dropped
				)
			);
		}

		return $out;
	}

	/**
	 * Strips an outer <script> wrapper from a pasted snippet.
	 *
	 * A wrapper tag would otherwise close the placeholder early. A script
	 * tag that only has a src attribute and no code is converted to a URL.
	 *
	 * @param string $code Pasted code.
	 * @return array{code:string,url:string|null} Inner code and, when the wrapper was src-only, the URL.
	 */
	private static function unwrap_script_tag( string $code ): array {
		$code = trim( $code );

		if ( ! preg_match( '/^<script\b([^>]*)>(.*)<\/script\s*>$/is', $code, $matches ) ) {
			return array(
				'code' => $code,
				'url'  => null,
			);
		}

		$attributes = $matches[1];
		$inner      = trim( $matches[2] );

		if ( '' === $inner && preg_match( '/\bsrc=["\']([^"\']+)["\']/i', $attributes, $src ) ) {
			return array(
				'code' => '',
				'url'  => $src[1],
			);
		}

		return array(
			'code' => $inner,
			'url'  => null,
		);
	}
}
