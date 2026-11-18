<?php
/**
 * Front-end: assets, consent banner, gated scripts, footer link, shortcode.
 *
 * @package FishCakes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Front-end rendering.
 */
class FC_Frontend {

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_head', array( __CLASS__, 'print_scripts_head' ), 5 );
		add_action( 'wp_footer', array( __CLASS__, 'print_scripts_foot' ), 20 );
		add_action( 'wp_footer', array( __CLASS__, 'render_footer_link' ), 99 );
		add_action( 'wp_footer', array( __CLASS__, 'render_banner' ), 100 );

		add_shortcode( 'fishcakes_cookie_preferences', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Whether the consent UI and blocking are active.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return (bool) FC_Settings::get()['enabled'];
	}

	/**
	 * Registers (does not enqueue) the public assets.
	 *
	 * @return void
	 */
	public static function register_assets(): void {
		wp_register_style( 'fishcakes', FISHCAKES_URL . 'assets/css/fishcakes.css', array(), FISHCAKES_VERSION );
		wp_register_script(
			'fishcakes',
			FISHCAKES_URL . 'assets/js/fishcakes.js',
			array(),
			FISHCAKES_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Enqueues the public assets plus the runtime config and color overrides.
	 *
	 * @return void
	 */
	public static function enqueue_assets(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		wp_enqueue_style( 'fishcakes' );
		wp_enqueue_script( 'fishcakes' );

		$settings = FC_Settings::get();

		$css = self::custom_css();
		if ( '' !== $css ) {
			wp_add_inline_style( 'fishcakes', $css );
		}

		wp_localize_script(
			'fishcakes',
			'FishcakesConfig',
			array(
				'cookieName'   => FC_Consent::COOKIE,
				'expiryDays'   => (int) $settings['consent_expiry'],
				'version'      => (int) $settings['banner_version'],
				'position'     => (string) $settings['position'],
				'savedMessage' => FC_Settings::text( 'saved_message' ),
			)
		);
	}

	/**
	 * Builds the inline CSS for the color / radius settings.
	 *
	 * Every visual property in the stylesheet reads from a CSS custom
	 * property; this only outputs the ones the admin has overridden.
	 *
	 * @return string
	 */
	private static function custom_css(): string {
		$settings = FC_Settings::get();
		$colors   = $settings['colors'];

		$map = array(
			'bg'           => '--fc-color-bg',
			'text'         => '--fc-color-text',
			'border'       => '--fc-color-border',
			'primary'      => '--fc-color-primary',
			'primary_text' => '--fc-color-primary-text',
		);

		$rules = array();

		foreach ( $map as $key => $variable ) {
			if ( ! empty( $colors[ $key ] ) ) {
				$rules[] = $variable . ':' . $colors[ $key ];
			}
		}

		$radius = trim( (string) $settings['radius'] );
		if ( '' !== $radius ) {
			$rules[] = '--fc-radius:' . $radius;
		}

		return $rules ? ':root{' . implode( ';', $rules ) . '}' : '';
	}

	/**
	 * Prints gated script placeholders in <head>.
	 *
	 * @return void
	 */
	public static function print_scripts_head(): void {
		self::print_scripts( 'head' );
	}

	/**
	 * Prints gated script placeholders in the footer.
	 *
	 * @return void
	 */
	public static function print_scripts_foot(): void {
		self::print_scripts( 'foot' );
	}

	/**
	 * Prints registered scripts as inert placeholders.
	 *
	 * Nothing loads until the visitor has given consent for the script's
	 * category; the front-end runtime activates them without a reload.
	 * Note the src is kept in a data attribute: a script tag with a
	 * non-executable type must never even be fetched.
	 *
	 * @param string $location "head" or "foot".
	 * @return void
	 */
	private static function print_scripts( string $location ): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		foreach ( FC_Settings::get()['scripts'] as $script ) {
			if ( empty( $script['enabled'] ) ) {
				continue;
			}

			$category = (string) ( $script['category'] ?? '' );
			if ( ! in_array( $category, array( 'analytical', 'marketing' ), true ) ) {
				continue;
			}

			if ( ( $script['location'] ?? 'foot' ) !== $location ) {
				continue;
			}

			$name = (string) ( $script['name'] ?? '' );
			$attr = $name ? sprintf( ' data-fc-name="%s"', esc_attr( $name ) ) : '';

			if ( 'url' === ( $script['type'] ?? 'inline' ) && ! empty( $script['url'] ) ) {
				printf(
					'<script type="text/plain" data-fc-category="%1$s" data-fc-src="%2$s"%3$s></script>' . "\n",
					esc_attr( $category ),
					esc_url( $script['url'] ),
					$attr // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				);
			} elseif ( ! empty( $script['code'] ) ) {
				printf(
					'<script type="text/plain" data-fc-category="%1$s"%2$s>%3$s</script>' . "\n",
					esc_attr( $category ),
					$attr, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
					$script['code'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-only raw code, inert until consent.
				);
			}
		}
	}

	/**
	 * Renders the persistent "Cookie settings" trigger in the footer.
	 *
	 * Withdrawal of consent must be as easy as giving it (GDPR Art. 7(3)).
	 *
	 * @return void
	 */
	public static function render_footer_link(): void {
		if ( ! self::is_enabled() || ! FC_Settings::get()['auto_footer_link'] ) {
			return;
		}

		printf(
			'<button type="button" class="fc-footer-link" data-fc-open>%s</button>',
			esc_html( FC_Settings::text( 'footer_link_text' ) )
		);
	}

	/**
	 * Shortcode: [fishcakes_cookie_preferences].
	 *
	 * Renders a button that re-opens the consent banner, for precise
	 * placement in menus, footers or privacy pages.
	 *
	 * @return string
	 */
	public static function shortcode(): string {
		if ( ! self::is_enabled() ) {
			return '';
		}

		return sprintf(
			'<button type="button" class="fc-link fc-reopen" data-fc-open>%s</button>',
			esc_html( FC_Settings::text( 'footer_link_text' ) )
		);
	}

	/**
	 * Renders the consent banner and the live region for announcements.
	 *
	 * The banner starts hidden; the runtime shows it when no valid consent
	 * exists. Keeping the markup server-rendered avoids a banner flash and
	 * keeps all texts translatable and admin-editable.
	 *
	 * @return void
	 */
	public static function render_banner(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		$settings = FC_Settings::get();
		$position = 'top' === $settings['position'] ? 'top' : 'bottom';
		$allowed  = FC_Settings::allowed_text_html();
		$intro    = self::replace_privacy_token( FC_Settings::text( 'banner_intro' ) );
		?>
		<!-- FishCakes cookie consent -->
		<div class="fc-banner fc-banner--<?php echo esc_attr( $position ); ?>" id="fc-banner" role="dialog" aria-modal="false" aria-labelledby="fc-banner-title" aria-describedby="fc-banner-text" data-fc-banner data-nosnippet hidden>
			<div class="fc-banner__inner">
				<div class="fc-banner__content">
					<h2 class="fc-banner__title" id="fc-banner-title" tabindex="-1"><?php echo esc_html( FC_Settings::text( 'banner_title' ) ); ?></h2>
					<p class="fc-banner__text" id="fc-banner-text"><?php echo wp_kses( $intro, $allowed ); ?></p>
				</div>
				<div class="fc-banner__actions">
					<button type="button" class="fc-btn fc-btn--primary" data-fc-accept><?php echo esc_html( FC_Settings::text( 'button_accept' ) ); ?></button>
					<button type="button" class="fc-btn fc-btn--secondary" data-fc-reject><?php echo esc_html( FC_Settings::text( 'button_reject' ) ); ?></button>
					<button type="button" class="fc-link" data-fc-toggle aria-expanded="false" aria-controls="fc-options"><?php echo esc_html( FC_Settings::text( 'button_options' ) ); ?></button>
				</div>
			</div>
			<form class="fc-options" id="fc-options" data-fc-form hidden>
				<div class="fc-category">
					<label class="fc-category__label" for="fc-check-necessary">
						<input type="checkbox" class="fc-category__input" id="fc-check-necessary" checked disabled>
						<?php echo esc_html( FC_Settings::text( 'necessary_label' ) ); ?>
					</label>
					<p class="fc-category__desc" id="fc-desc-necessary"><?php echo wp_kses( FC_Settings::text( 'necessary_desc' ), $allowed ); ?></p>
				</div>
				<div class="fc-category">
					<label class="fc-category__label" for="fc-check-analytical">
						<input type="checkbox" class="fc-category__input" id="fc-check-analytical" aria-describedby="fc-desc-analytical">
						<?php echo esc_html( FC_Settings::text( 'analytical_label' ) ); ?>
					</label>
					<p class="fc-category__desc" id="fc-desc-analytical"><?php echo wp_kses( FC_Settings::text( 'analytical_desc' ), $allowed ); ?></p>
				</div>
				<div class="fc-category">
					<label class="fc-category__label" for="fc-check-marketing">
						<input type="checkbox" class="fc-category__input" id="fc-check-marketing" aria-describedby="fc-desc-marketing">
						<?php echo esc_html( FC_Settings::text( 'marketing_label' ) ); ?>
					</label>
					<p class="fc-category__desc" id="fc-desc-marketing"><?php echo wp_kses( FC_Settings::text( 'marketing_desc' ), $allowed ); ?></p>
				</div>
				<div class="fc-options__save">
					<button type="submit" class="fc-btn fc-btn--primary"><?php echo esc_html( FC_Settings::text( 'button_save' ) ); ?></button>
				</div>
			</form>
		</div>
		<p class="fc-sr-only" data-fc-announce aria-live="polite"></p>
		<?php
	}

	/**
	 * Replaces the {privacy_link} token in a text with a link to the
	 * configured privacy page (falls back to the plain label).
	 *
	 * @param string $text Text containing the token.
	 * @return string
	 */
	public static function replace_privacy_token( string $text ): string {
		$label   = esc_html( FC_Settings::text( 'privacy_link_label' ) );
		$page_id = (int) FC_Settings::get()['privacy_page'];

		$link = $label;

		if ( $page_id && get_post( $page_id ) ) {
			$link = sprintf( '<a href="%s">%s</a>', esc_url( (string) get_permalink( $page_id ) ), $label );
		}

		return str_replace( '{privacy_link}', $link, $text );
	}
}
