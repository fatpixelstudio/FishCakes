=== FishCakes ===
Contributors: fatpixelstudio
Tags: cookies, consent, gdpr, privacy, accessibility, avg
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 2.0.0
License: MPL-2.0
License URI: https://www.mozilla.org/en-US/MPL/2.0/

GDPR/AVG-proof, accessible cookie consent for WordPress: a consent banner, gated scripts and blocked embeds — done the right way.

== Description ==

FishCakes asks your visitors for consent before anything non-essential is placed or loaded, and keeps their choice under their control.

**Consent done right**

* Accept all, reject all and granular choices, all equally easy to reach — refusing is never harder than accepting.
* Nothing is pre-ticked; necessary cookies are explained but can't be switched off.
* One first-party cookie stores the choice itself — which categories, when, and under which banner version — so you can demonstrate consent (GDPR Art. 7(1)).
* Withdrawal is as easy as giving: a footer link, a `[fishcakes_cookie_preferences]` shortcode and a `Fishcakes.open()` JavaScript API.
* Re-asking is built in: bump the banner version after a policy change and every visitor is asked again.

**Blocking that is actually enforced**

* Scripts you register (Settings → FishCakes → Scripts) are printed as inert placeholders and only execute after consent for their category — in the head or footer, inline code or a source URL.
* Embeds from YouTube, Vimeo, Spotify, SoundCloud and Google are replaced by a placeholder that explains the situation ("no choice made yet" or "your preferences don't allow this") with a button to open the banner. Works for the block editor, the classic editor, raw iframes and ACF oEmbed / WYSIWYG fields.
* Blocked embeds are replaced, never hidden — a hidden iframe still loads and still places cookies. Consent changes are applied without a page reload.

**Accessible by design**

* Dialog semantics with focus management, `aria-expanded` / `aria-controls` on every toggle, a polite live region that confirms saved choices, visible focus styles, contrast-safe defaults, reduced-motion support and a banner that never traps or covers content.

**Easy to make yours**

* All texts are editable (empty means: use the translated default), colors are set through CSS custom properties, and everything is translation-ready with a bundled POT file.

== Installation ==

1. Upload the `fishcakes` folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Open Settings → FishCakes: pick your privacy page, register your scripts and adjust the texts and colors.

== Frequently Asked Questions ==

= Which embeds are blocked? =

By default YouTube, Vimeo, Spotify, SoundCloud and Google (Maps/Docs/Calendar). Add any other host under Settings → FishCakes → Blocked embeds → Custom hosts.

= What about page caching? =

The consent cookie is read server-side, so embeds are rendered directly for visitors who consented. With a full-page cache that ignores cookies, enable "Cache-safe mode" (Settings → FishCakes → General): embeds are then always rendered as placeholders and upgraded in the browser right after — cached pages can never show embeds to visitors who did not consent.

= Where does the consent proof live? =

In a single first-party cookie (`fc_consent`) holding the categories chosen, the timestamp and the banner version. That cookie is strictly necessary, so it may be set before consent.

= Can visitors revoke consent? =

Yes — via the footer link, the `[fishcakes_cookie_preferences]` shortcode or any custom element with a `data-fc-open` attribute. Scripts already loaded during the current page view stop on the next view, embeds are downgraded immediately.

== Changelog ==

= 2.0.0 =

* Complete rewrite as a WordPress plugin.
* Settings page (Settings → FishCakes) for texts, colors, scripts and blocked providers.
* Gated script placeholders for head/footer, analytical/marketing; inline code is wrapped in JSON data islands so pasted `</script>` sequences can never break out of the placeholder.
* Embed blocking for block editor, classic editor, raw iframes and ACF fields.
* Consent record with banner version and expiry; re-asking built in.
* Cache-safe mode for full-page caches that ignore cookies.
* Multisite-aware uninstall cleanup.
* Accessibility overhaul: dialog semantics, focus management, live announcements, visible focus.
* New buildless asset pipeline (vanilla JS + plain CSS), yarn for linting only.
* Dropped the gulp build and the static example page.
