<?php
/**
 * Blocks tracking embeds (YouTube, Vimeo, …) behind a consent placeholder.
 *
 * Covered sources:
 * - Block editor embeds and classic editor oEmbeds, via `embed_oembed_html`
 *   (the embed block saves a bare URL that WP_Embed::autoembed() turns into
 *   an iframe through WP_Embed::shortcode()).
 * - Raw <iframe> tags in post content (classic editor, Custom HTML blocks),
 *   via a `the_content` pass that runs after do_blocks().
 * - ACF oEmbed fields, via `acf/format_value/type=oembed` (ACF fetches the
 *   iframe itself, bypassing `embed_oembed_html`).
 * - ACF WYSIWYG fields, via `acf_the_content` (the same content pass).
 *
 * The iframe is REPLACED, never hidden: a hidden iframe still loads and can
 * still place cookies. The original embed is kept inside an inert
 * <template> element so the front-end runtime can swap it in without a
 * page reload once consent is given.
 *
 * @package FishCakes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Embed blocker.
 */
class FC_Blocker {

	/**
	 * Registers the blocking filters.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'embed_oembed_html', array( __CLASS__, 'filter_embed_oembed_html' ), 10, 2 );
		add_filter( 'the_content', array( __CLASS__, 'filter_content' ), 20 );
		add_filter( 'acf_the_content', array( __CLASS__, 'filter_content' ), 20 );
		add_filter( 'acf/format_value/type=oembed', array( __CLASS__, 'filter_acf_oembed' ) );
	}

	/**
	 * Whether blocking should be skipped in the current context.
	 *
	 * Feeds and wp-admin screens are skipped (feeds cannot run the runtime;
	 * admin screens don't face visitors). Ajax and REST requests are NOT
	 * skipped: front-end pagination must stay blocked, and block editor
	 * previews then honestly show what visitors see.
	 *
	 * @return bool
	 */
	private static function skip(): bool {
		static $skip = null;
		if ( null !== $skip ) {
			return $skip;
		}

		$settings = FC_Settings::get();
		$skip     = ! $settings['enabled']
			|| ! $settings['blocker_enabled']
			|| is_feed()
			|| ( is_admin() && ! wp_doing_ajax() );

		return $skip;
	}

	/**
	 * `embed_oembed_html` callback: oEmbed iframe from block editor,
	 * classic editor, [embed] shortcode, widget blocks and ACF WYSIWYG.
	 *
	 * @param string $html Embed HTML.
	 * @param string $url  Embed URL.
	 * @return string
	 */
	public static function filter_embed_oembed_html( $html, $url = '' ): string {
		return self::maybe_wrap( (string) $html, (string) $url );
	}

	/**
	 * `acf/format_value/type=oembed` callback: ACF oEmbed fields.
	 *
	 * ACF caches the raw iframe HTML itself, so this filter receives the
	 * iframe; the provider is derived from its src attribute.
	 *
	 * @param string $value Field value (iframe HTML).
	 * @return string
	 */
	public static function filter_acf_oembed( $value ): string {
		if ( ! is_string( $value ) || '' === $value ) {
			return (string) $value;
		}

		return self::maybe_wrap( $value, '' );
	}

	/**
	 * Content pass for raw <iframe> tags (classic editor content,
	 * Custom HTML blocks, ACF WYSIWYG fields).
	 *
	 * Runs at priority 20, after do_blocks (9), autoembed (8) and
	 * wpautop (10), so iframes produced by those filters are already
	 * wrapped and carry the data-fc-embed marker and are skipped here.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	public static function filter_content( $content ): string {
		if ( ! is_string( $content ) || '' === $content || self::skip() ) {
			return $content;
		}

		return (string) preg_replace_callback(
			'/<iframe\b[^>]*>.*?<\/iframe>/is',
			static fn( array $matches ): string => self::handle_raw_iframe( $matches[0] ),
			$content
		);
	}

	/**
	 * Wraps a single raw iframe when its source belongs to a blocked provider.
	 *
	 * @param string $iframe Full iframe tag.
	 * @return string
	 */
	private static function handle_raw_iframe( string $iframe ): string {
		if ( str_contains( $iframe, 'data-fc-embed' ) ) {
			return $iframe; // Already handled.
		}

		if ( ! preg_match( '/\bsrc=["\']([^"\']+)["\']/i', $iframe, $matches ) ) {
			return $iframe;
		}

		$provider = FC_Settings::match_provider( $matches[1] );

		return $provider ? self::wrap( $iframe, $provider ) : $iframe;
	}

	/**
	 * Wraps embed HTML for a blocked provider, unless already wrapped.
	 *
	 * @param string $html Embed HTML.
	 * @param string $url  Embed URL, when known.
	 * @return string
	 */
	private static function maybe_wrap( string $html, string $url = '' ): string {
		if ( '' === trim( $html ) || self::skip() ) {
			return $html;
		}

		if ( str_contains( $html, 'data-fc-embed' ) ) {
			return $html; // Already handled (idempotency for repeated filter runs).
		}

		$provider = $url ? FC_Settings::match_provider( $url ) : null;

		if ( null === $provider && preg_match( '/\bsrc=["\']([^"\']+)["\']/i', $html, $matches ) ) {
			$provider = FC_Settings::match_provider( $matches[1] );
		}

		return $provider ? self::wrap( $html, $provider ) : $html;
	}

	/**
	 * Builds the placeholder markup around an embed.
	 *
	 * When consent for marketing cookies exists and is positive, the live
	 * embed is rendered immediately (no flash of a placeholder) and a
	 * template copy is kept for a no-reload downgrade when consent is
	 * withdrawn.
	 *
	 * @param string                             $embed_html Embed HTML.
	 * @param array{slug:string,label:string}    $provider   Matched provider.
	 * @return string
	 */
	private static function wrap( string $embed_html, array $provider ): string {
		$slug    = (string) $provider['slug'];
		$label   = (string) $provider['label'];
		$marked  = self::mark_iframe( $embed_html, $slug );
		$consent = FC_Consent::get();

		/*
		 * With consent for marketing the embed renders directly (no flash
		 * of a placeholder). In cache-safe mode the consent cookie is
		 * deliberately ignored here, so the served HTML never depends on
		 * it and a full-page cache cannot leak embeds to visitors who
		 * never consented; the runtime upgrades in the browser instead.
		 */
		$allowed = null !== $consent
			&& $consent['marketing']
			&& empty( FC_Settings::get()['cache_safe'] );

		// Current notice text: "no choice yet" vs "preferences don't allow".
		$no_choice = FC_Settings::text( 'embed_text_nochoice' );
		$blocked   = FC_Settings::text( 'embed_text_blocked' );
		$notice    = ( null === $consent ) ? $no_choice : $blocked;

		ob_start();
		?>
		<div class="fc-blocker <?php echo $allowed ? 'is-allowed' : 'is-blocked'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static strings ?>" data-fc-blocker data-fc-name="<?php echo esc_attr( $label ); ?>">
			<template><?php echo $marked; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inert template copy of the original embed ?></template>
			<?php // data-nosnippet: keep the notice out of search result snippets (it sits inside the post content). ?>
			<div class="fc-blocker__notice" data-nosnippet>
				<p class="<?php echo esc_attr( FC_Settings::classes( 'fc-blocker__title', array( 'heading' ) ) ); ?>"><?php echo esc_html( $label ); ?></p>
				<p class="fc-blocker__text" data-fc-text-nochoice="<?php echo esc_attr( $no_choice ); ?>" data-fc-text-blocked="<?php echo esc_attr( $blocked ); ?>"><?php echo wp_kses( $notice, FC_Settings::allowed_text_html() ); ?></p>
				<button type="button" class="<?php echo esc_attr( FC_Settings::classes( 'fc-btn fc-btn--secondary', array( 'button', 'button_secondary' ) ) ); ?>" data-fc-open><?php echo esc_html( FC_Settings::text( 'embed_button' ) ); ?></button>
			</div>
			<?php if ( $allowed ) : ?>
				<div class="fc-blocker__embed"><?php echo $marked; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- original embed, shown with consent ?></div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Tags an iframe with the data-fc-embed marker so repeated filter runs
	 * (the_content + ACF filters, pagination, …) never process it twice.
	 *
	 * @param string $html Embed HTML.
	 * @param string $slug Provider slug.
	 * @return string
	 */
	private static function mark_iframe( string $html, string $slug ): string {
		$position = stripos( $html, '<iframe' );

		if ( false === $position ) {
			return $html;
		}

		$marker = ' data-fc-embed="' . esc_attr( $slug ) . '"';

		return substr( $html, 0, $position + strlen( '<iframe' ) ) . $marker . substr( $html, $position + strlen( '<iframe' ) );
	}
}
