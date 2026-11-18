<?php
/**
 * Settings → FishCakes admin page.
 *
 * @package FishCakes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin UI (Settings API).
 */
class FC_Admin {

	/**
	 * Settings page slug.
	 */
	const PAGE_SLUG = 'fishcakes';

	/**
	 * Settings option group.
	 */
	const OPTION_GROUP = 'fishcakes';

	/**
	 * Default colors, kept in sync with assets/css/fishcakes.css.
	 *
	 * @var array<string,string>
	 */
	const COLOR_DEFAULTS = array(
		'bg'           => '#f7f7f8',
		'text'         => '#27272a',
		'border'       => '#d4d4d8',
		'primary'      => '#18181b',
		'primary_text' => '#ffffff',
	);

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Adds the options page under Settings.
	 *
	 * @return void
	 */
	public static function add_page(): void {
		add_options_page(
			__( 'FishCakes cookie consent', 'fishcakes' ),
			__( 'FishCakes', 'fishcakes' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Enqueues the admin assets on the settings page only.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public static function enqueue_assets( $hook ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'fishcakes-admin', FISHCAKES_URL . 'assets/css/fishcakes-admin.css', array(), FISHCAKES_VERSION );
		wp_enqueue_script( 'fishcakes-admin', FISHCAKES_URL . 'assets/js/fishcakes-admin.js', array( 'jquery', 'wp-color-picker' ), FISHCAKES_VERSION, true );
	}

	/**
	 * Registers the setting, sections and fields.
	 *
	 * @return void
	 */
	public static function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			FC_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( FC_Settings::class, 'sanitize' ),
				'default'           => FC_Settings::defaults(),
			)
		);

		$sections = array(
			'fc_general'    => __( 'General', 'fishcakes' ),
			'fc_appearance' => __( 'Appearance', 'fishcakes' ),
			'fc_categories' => __( 'Cookie categories', 'fishcakes' ),
			'fc_texts'      => __( 'Banner texts', 'fishcakes' ),
			'fc_scripts'    => __( 'Scripts', 'fishcakes' ),
			'fc_embeds'     => __( 'Blocked embeds', 'fishcakes' ),
		);

		foreach ( $sections as $id => $title ) {
			add_settings_section( $id, $title, '__return_false', self::PAGE_SLUG );
		}

		self::add_general_fields();
		self::add_appearance_fields();
		self::add_category_fields();
		self::add_banner_text_fields();
		self::add_script_fields();
		self::add_embed_fields();
	}

	/**
	 * Renders the settings page.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------ *
	 * Field registration
	 * ------------------------------------------------------------------ */

	/**
	 * General section fields.
	 *
	 * @return void
	 */
	private static function add_general_fields(): void {
		add_settings_field(
			'fc_enabled',
			__( 'Enable', 'fishcakes' ),
			fn() => self::checkbox_field( 'enabled', __( 'Show the consent banner, gate registered scripts and block embeds.', 'fishcakes' ) ),
			self::PAGE_SLUG,
			'fc_general'
		);

		add_settings_field(
			'fc_position',
			__( 'Banner position', 'fishcakes' ),
			fn() => self::select_field( 'position', array( 'bottom' => __( 'Bottom (recommended)', 'fishcakes' ), 'top' => __( 'Top', 'fishcakes' ) ) ),
			self::PAGE_SLUG,
			'fc_general'
		);

		add_settings_field(
			'fc_expiry',
			__( 'Consent expiry (days)', 'fishcakes' ),
			fn() => self::number_field( 'consent_expiry', __( 'How long a consent stays valid. After it expires, the banner returns. Max 365.', 'fishcakes' ), 1, 365 ),
			self::PAGE_SLUG,
			'fc_general'
		);

		add_settings_field(
			'fc_version',
			__( 'Banner version', 'fishcakes' ),
			fn() => self::number_field( 'banner_version', __( 'Increase this number to ask every visitor for consent again, for example after changing the texts or your cookie policy.', 'fishcakes' ), 1, 999999 ),
			self::PAGE_SLUG,
			'fc_general'
		);

		add_settings_field(
			'fc_privacy_page',
			__( 'Privacy page', 'fishcakes' ),
			array( __CLASS__, 'field_privacy_page' ),
			self::PAGE_SLUG,
			'fc_general'
		);

		add_settings_field(
			'fc_auto_footer_link',
			__( 'Footer link', 'fishcakes' ),
			fn() => self::checkbox_field( 'auto_footer_link', __( 'Automatically add a “Cookie settings” button to the footer, so consent can always be withdrawn.', 'fishcakes' ) ),
			self::PAGE_SLUG,
			'fc_general'
		);

		add_settings_field(
			'fc_cache_safe',
			__( 'Cache-safe mode', 'fishcakes' ),
			fn() => self::checkbox_field( 'cache_safe', __( 'For sites with a full-page cache that ignores cookies. Embeds are always rendered as placeholders and upgraded in the browser, so cached pages can never show embeds to visitors who did not consent.', 'fishcakes' ) ),
			self::PAGE_SLUG,
			'fc_general'
		);
	}

	/**
	 * Appearance section fields.
	 *
	 * @return void
	 */
	private static function add_appearance_fields(): void {
		$labels = array(
			'bg'           => __( 'Background', 'fishcakes' ),
			'text'         => __( 'Text', 'fishcakes' ),
			'border'       => __( 'Border', 'fishcakes' ),
			'primary'      => __( 'Buttons (primary)', 'fishcakes' ),
			'primary_text' => __( 'Button text (primary)', 'fishcakes' ),
		);

		foreach ( $labels as $key => $label ) {
			add_settings_field(
				'fc_color_' . $key,
				$label,
				fn() => self::color_field( $key ),
				self::PAGE_SLUG,
				'fc_appearance'
			);
		}

		add_settings_field(
			'fc_radius',
			__( 'Border radius', 'fishcakes' ),
			fn() => self::text_value_field( 'radius', __( 'For example 6px, 0.5rem or 0. Empty uses the default (6px).', 'fishcakes' ), '6px' ),
			self::PAGE_SLUG,
			'fc_appearance'
		);
	}

	/**
	 * Cookie category section fields.
	 *
	 * @return void
	 */
	private static function add_category_fields(): void {
		$fields = array(
			'necessary_label'  => __( 'Necessary — label', 'fishcakes' ),
			'necessary_desc'   => __( 'Necessary — description', 'fishcakes' ),
			'analytical_label' => __( 'Analytical — label', 'fishcakes' ),
			'analytical_desc'  => __( 'Analytical — description', 'fishcakes' ),
			'marketing_label'  => __( 'Marketing & social media — label', 'fishcakes' ),
			'marketing_desc'   => __( 'Marketing & social media — description', 'fishcakes' ),
		);

		foreach ( $fields as $key => $label ) {
			add_settings_field(
				'fc_text_' . $key,
				$label,
				fn() => self::text_field( $key, str_ends_with( $key, '_desc' ) ),
				self::PAGE_SLUG,
				'fc_categories'
			);
		}
	}

	/**
	 * Banner text section fields.
	 *
	 * @return void
	 */
	private static function add_banner_text_fields(): void {
		$fields = array(
			'banner_title'       => __( 'Banner title', 'fishcakes' ),
			'banner_intro'       => __( 'Banner intro', 'fishcakes' ),
			'privacy_link_label' => __( 'Privacy link text', 'fishcakes' ),
			'button_accept'      => __( 'Button: accept all', 'fishcakes' ),
			'button_reject'      => __( 'Button: reject all', 'fishcakes' ),
			'button_save'        => __( 'Button: save choices', 'fishcakes' ),
			'button_options'     => __( 'Button: change choices', 'fishcakes' ),
			'saved_message'      => __( 'Confirmation message', 'fishcakes' ),
			'footer_link_text'   => __( 'Footer link text', 'fishcakes' ),
		);

		foreach ( $fields as $key => $label ) {
			add_settings_field(
				'fc_text_' . $key,
				$label,
				fn() => self::text_field( $key, 'banner_intro' === $key ),
				self::PAGE_SLUG,
				'fc_texts'
			);
		}
	}

	/**
	 * Scripts section fields (the repeater).
	 *
	 * @return void
	 */
	private static function add_script_fields(): void {
		add_settings_field(
			'fc_scripts',
			__( 'Registered scripts', 'fishcakes' ),
			array( __CLASS__, 'field_scripts' ),
			self::PAGE_SLUG,
			'fc_scripts'
		);
	}

	/**
	 * Embed section fields.
	 *
	 * @return void
	 */
	private static function add_embed_fields(): void {
		add_settings_field(
			'fc_blocker_enabled',
			__( 'Block embeds', 'fishcakes' ),
			fn() => self::checkbox_field( 'blocker_enabled', __( 'Replace embeds that may place tracking cookies with a placeholder until marketing consent is given. Covers the block editor, classic editor, raw iframes, ACF oEmbed and ACF WYSIWYG fields.', 'fishcakes' ) ),
			self::PAGE_SLUG,
			'fc_embeds'
		);

		add_settings_field(
			'fc_providers',
			__( 'Providers', 'fishcakes' ),
			array( __CLASS__, 'field_providers' ),
			self::PAGE_SLUG,
			'fc_embeds'
		);

		add_settings_field(
			'fc_custom_hosts',
			__( 'Custom hosts', 'fishcakes' ),
			array( __CLASS__, 'field_custom_hosts' ),
			self::PAGE_SLUG,
			'fc_embeds'
		);

		$fields = array(
			'embed_button'        => __( 'Placeholder button', 'fishcakes' ),
			'embed_text_nochoice' => __( 'Placeholder text: no choice yet', 'fishcakes' ),
			'embed_text_blocked'  => __( 'Placeholder text: not allowed', 'fishcakes' ),
		);

		foreach ( $fields as $key => $label ) {
			add_settings_field(
				'fc_text_' . $key,
				$label,
				fn() => self::text_field( $key, str_starts_with( $key, 'embed_text' ) ),
				self::PAGE_SLUG,
				'fc_embeds'
			);
		}
	}

	/* ------------------------------------------------------------------ *
	 * Field renderers
	 * ------------------------------------------------------------------ */

	/**
	 * Checkbox bound to a top-level setting.
	 *
	 * @param string $key         Setting key.
	 * @param string $description Description.
	 * @return void
	 */
	private static function checkbox_field( string $key, string $description ): void {
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::name( $key ) ); ?>" value="1" <?php checked( (bool) FC_Settings::get()[ $key ] ); ?>>
			<?php esc_html_e( 'Enabled', 'fishcakes' ); ?>
		</label>
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php
	}

	/**
	 * Select bound to a top-level setting.
	 *
	 * @param string                $key         Setting key.
	 * @param array<string,string>  $options     Options.
	 * @param string                $description Description.
	 * @return void
	 */
	private static function select_field( string $key, array $options, string $description = '' ): void {
		$current = (string) FC_Settings::get()[ $key ];
		?>
		<select name="<?php echo esc_attr( self::name( $key ) ); ?>">
			<?php foreach ( $options as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
		if ( '' !== $description ) {
			printf( '<p class="description">%s</p>', esc_html( $description ) );
		}
	}

	/**
	 * Number input bound to a top-level setting.
	 *
	 * @param string $key         Setting key.
	 * @param string $description Description.
	 * @param int    $min         Minimum.
	 * @param int    $max         Maximum.
	 * @return void
	 */
	private static function number_field( string $key, string $description, int $min, int $max ): void {
		?>
		<input type="number" name="<?php echo esc_attr( self::name( $key ) ); ?>" value="<?php echo esc_attr( (string) (int) FC_Settings::get()[ $key ] ); ?>" min="<?php echo esc_attr( (string) $min ); ?>" max="<?php echo esc_attr( (string) $max ); ?>" step="1" class="small-text">
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php
	}

	/**
	 * Free text input bound to a top-level setting.
	 *
	 * @param string $key         Setting key.
	 * @param string $description Description.
	 * @param string $placeholder Placeholder.
	 * @return void
	 */
	private static function text_value_field( string $key, string $description, string $placeholder = '' ): void {
		$value = (string) FC_Settings::get()[ $key ];
		?>
		<input type="text" name="<?php echo esc_attr( self::name( $key ) ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="small-text">
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php
	}

	/**
	 * Text/textarea bound to a "texts" key. Empty value = translated default,
	 * shown as the placeholder.
	 *
	 * @param string $key      Text key.
	 * @param bool   $textarea Render as textarea.
	 * @return void
	 */
	private static function text_field( string $key, bool $textarea = false ): void {
		$custom   = (string) FC_Settings::get()['texts'][ $key ];
		$default  = FC_Settings::default_texts()[ $key ] ?? '';
		$name     = self::name( 'texts' ) . '[' . $key . ']';
		$note     = __( 'Leave empty to use the default (translated) text.', 'fishcakes' );

		if ( 'banner_intro' === $key ) {
			$note = __( 'Leave empty to use the default text. Use {privacy_link} to insert a link to your privacy page. Basic HTML allowed.', 'fishcakes' );
		}
		?>
		<?php if ( $textarea ) : ?>
			<textarea name="<?php echo esc_attr( $name ); ?>" rows="3" class="large-text" placeholder="<?php echo esc_attr( $default ); ?>"><?php echo esc_textarea( $custom ); ?></textarea>
		<?php else : ?>
			<input type="text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $custom ); ?>" placeholder="<?php echo esc_attr( $default ); ?>" class="large-text">
		<?php endif; ?>
		<p class="description"><?php echo esc_html( $note ); ?></p>
		<?php
	}

	/**
	 * Color picker bound to a "colors" key.
	 *
	 * @param string $key Color key.
	 * @return void
	 */
	private static function color_field( string $key ): void {
		$value   = (string) FC_Settings::get()['colors'][ $key ];
		$default = self::COLOR_DEFAULTS[ $key ] ?? '';
		?>
		<input type="text" name="<?php echo esc_attr( self::name( 'colors' ) ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" data-default-color="<?php echo esc_attr( $default ); ?>" class="fc-color-field">
		<?php
	}

	/**
	 * Privacy page dropdown.
	 *
	 * @return void
	 */
	public static function field_privacy_page(): void {
		$current = (int) FC_Settings::get()['privacy_page'];

		wp_dropdown_pages(
			array(
				'name'              => self::name( 'privacy_page' ),
				'selected'          => $current,
				'show_option_none'  => __( '— Select a page —', 'fishcakes' ),
				'option_none_value' => '0',
				'value_field'       => 'ID',
			)
		);
		?>
		<p class="description"><?php esc_html_e( 'Linked from the banner intro via the {privacy_link} token.', 'fishcakes' ); ?></p>
		<?php
	}

	/**
	 * Provider checkboxes.
	 *
	 * @return void
	 */
	public static function field_providers(): void {
		$enabled = FC_Settings::get()['providers'];
		$labels  = array(
			'youtube'    => __( 'YouTube', 'fishcakes' ),
			'vimeo'      => __( 'Vimeo', 'fishcakes' ),
			'spotify'    => __( 'Spotify', 'fishcakes' ),
			'soundcloud' => __( 'SoundCloud', 'fishcakes' ),
			'googlemaps' => __( 'Google (Maps, Docs, Calendar)', 'fishcakes' ),
		);
		?>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Blocked embed providers', 'fishcakes' ); ?></legend>
			<?php foreach ( $labels as $slug => $label ) : ?>
				<label style="display:block;margin-bottom:4px;">
					<input type="checkbox" name="<?php echo esc_attr( self::name( 'providers' ) ); ?>[<?php echo esc_attr( $slug ); ?>]" value="1" <?php checked( ! empty( $enabled[ $slug ] ) ); ?>>
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
			<p class="description"><?php esc_html_e( 'Hosts and all their subdomains are matched against embed URLs and iframe sources.', 'fishcakes' ); ?></p>
		</fieldset>
		<?php
	}

	/**
	 * Custom hosts textarea.
	 *
	 * @return void
	 */
	public static function field_custom_hosts(): void {
		?>
		<textarea name="<?php echo esc_attr( self::name( 'custom_hosts' ) ); ?>" rows="3" class="large-text code" placeholder="host.example.com&#10;other.example.org|Other provider"><?php echo esc_textarea( (string) FC_Settings::get()['custom_hosts'] ); ?></textarea>
		<p class="description"><?php esc_html_e( 'One hostname per line, optionally followed by | and a display name. Blocks embeds from these hosts and their subdomains.', 'fishcakes' ); ?></p>
		<?php
	}

	/**
	 * The scripts repeater.
	 *
	 * Rows are submitted with explicit numeric indices
	 * (fishcakes_settings[scripts][N][…]). PHP groups the fields of one
	 * row together that way; auto-append "[]" names would split every
	 * field into its own array element. New rows are cloned client-side
	 * from the template, whose "__IDX__" placeholder is replaced with
	 * the next index from data-next-index.
	 *
	 * @return void
	 */
	public static function field_scripts(): void {
		$scripts = FC_Settings::get()['scripts'];

		$next_index = 0;
		foreach ( $scripts as $index => $script ) {
			if ( is_numeric( $index ) ) {
				$next_index = max( $next_index, (int) $index + 1 );
			}
		}
		?>
		<div class="fc-scripts">
			<p class="description"><?php esc_html_e( 'Scripts are printed as inert placeholders and only execute after the visitor consents to their category. The choice is applied without a page reload.', 'fishcakes' ); ?></p>
			<div id="fc-script-rows" data-next-index="<?php echo esc_attr( (string) $next_index ); ?>">
				<?php
				foreach ( $scripts as $index => $script ) {
					if ( ! is_numeric( $index ) ) {
						continue;
					}

					self::render_script_row( (string) (int) $index, is_array( $script ) ? $script : array() );
				}
				?>
			</div>
			<p>
				<button type="button" class="button button-secondary" id="fc-script-add"><?php esc_html_e( 'Add script', 'fishcakes' ); ?></button>
			</p>
			<template id="fc-script-row-template">
				<?php self::render_script_row( '__IDX__', array() ); ?>
			</template>
			<p class="description"><?php esc_html_e( 'Inline code: paste the snippet, with or without the surrounding <script> tags. Source URL: load an external script file.', 'fishcakes' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Renders a single repeater row, filled or empty.
	 *
	 * @param string              $index Row index (numeric, or "__IDX__" for the JS template).
	 * @param array<string,mixed> $script Saved script row.
	 * @return void
	 */
	private static function render_script_row( string $index, array $script ): void {
		$script = wp_parse_args(
			$script,
			array(
				'id'       => '',
				'name'     => '',
				'enabled'  => true,
				'category' => 'analytical',
				'location' => 'foot',
				'type'     => 'inline',
				'code'     => '',
				'url'      => '',
			)
		);

		$name = self::name( 'scripts' ) . '[' . $index . ']';
		?>
		<div class="fc-script-row">
			<div class="fc-script-row__head">
				<label class="fc-script-row__enabled">
					<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( (bool) $script['enabled'] ); ?>>
					<span class="screen-reader-text"><?php esc_html_e( 'Script enabled', 'fishcakes' ); ?></span>
				</label>
				<input type="text" name="<?php echo esc_attr( $name ); ?>[name]" value="<?php echo esc_attr( (string) $script['name'] ); ?>" placeholder="<?php esc_attr_e( 'Name (for example: Google Analytics 4)', 'fishcakes' ); ?>" class="regular-text">
				<select name="<?php echo esc_attr( $name ); ?>[category]" class="fc-script-category">
					<option value="analytical" <?php selected( $script['category'], 'analytical' ); ?>><?php esc_html_e( 'Analytical', 'fishcakes' ); ?></option>
					<option value="marketing" <?php selected( $script['category'], 'marketing' ); ?>><?php esc_html_e( 'Marketing', 'fishcakes' ); ?></option>
				</select>
				<select name="<?php echo esc_attr( $name ); ?>[location]" class="fc-script-location">
					<option value="head" <?php selected( $script['location'], 'head' ); ?>><?php esc_html_e( 'Head', 'fishcakes' ); ?></option>
					<option value="foot" <?php selected( $script['location'], 'foot' ); ?>><?php esc_html_e( 'Footer', 'fishcakes' ); ?></option>
				</select>
				<select name="<?php echo esc_attr( $name ); ?>[type]" class="fc-script-type">
					<option value="inline" <?php selected( $script['type'], 'inline' ); ?>><?php esc_html_e( 'Inline code', 'fishcakes' ); ?></option>
					<option value="url" <?php selected( $script['type'], 'url' ); ?>><?php esc_html_e( 'Source URL', 'fishcakes' ); ?></option>
				</select>
				<button type="button" class="button fc-script-remove" aria-label="<?php esc_attr_e( 'Remove this script', 'fishcakes' ); ?>">&times;</button>
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>[id]" value="<?php echo esc_attr( (string) $script['id'] ); ?>">
			</div>
			<div class="fc-script-row__code">
				<textarea name="<?php echo esc_attr( $name ); ?>[code]" rows="3" class="large-text code" placeholder="<?php esc_attr_e( 'Paste the snippet here — with or without the surrounding <script> tags.', 'fishcakes' ); ?>"><?php echo esc_textarea( (string) $script['code'] ); ?></textarea>
			</div>
			<div class="fc-script-row__url">
				<input type="url" name="<?php echo esc_attr( $name ); ?>[url]" value="<?php echo esc_attr( esc_url( (string) $script['url'] ) ); ?>" placeholder="https://example.com/tracking.js" class="large-text">
			</div>
		</div>
		<?php
	}

	/**
	 * Builds an option name attribute.
	 *
	 * @param string $key Setting key.
	 * @return string
	 */
	private static function name( string $key ): string {
		return FC_Settings::OPTION . '[' . $key . ']';
	}
}
