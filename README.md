# FishCakes

GDPR/AVG-proof, accessible cookie consent for WordPress — done the right way.

FishCakes asks visitors for consent before anything non-essential is placed or loaded, keeps their choice under their control, and makes all of it configurable from **Settings → FishCakes**.

## How it works

### Consent

- One first-party cookie (`fc_consent`) stores the consent record: chosen categories, timestamp and the banner version it was given under. It is strictly necessary (it only stores the visitor's own choice) and readable server-side, so PHP can decide about embeds without waiting for JavaScript.
- Accept all, reject all and granular choices are equally prominent. Nothing is pre-ticked; the "Necessary" category is always on and explained.
- A record that doesn't match the current banner version, or has expired, is treated as *no choice*: the banner returns. Bump **Settings → FishCakes → Banner version** after a policy or text change to re-ask everyone.
- Withdrawal is as easy as giving: an automatic footer link (toggleable), the `[fishcakes_cookie_preferences]` shortcode, any element with a `data-fc-open` attribute, or `Fishcakes.open()` in JavaScript.

### Gated scripts

Scripts registered under **Settings → FishCakes → Scripts** are printed as inert placeholders:

```html
<script type="text/plain" data-fc-category="analytical" data-fc-name="Google Analytics 4">…</script>
<script type="text/plain" data-fc-category="marketing" data-fc-src="https://…/pixel.js"></script>
```

Nothing is fetched or executed until the visitor consents to the script's category (note the `data-fc-src` attribute: a `text/plain` script is never even fetched). On consent the runtime promotes the placeholders to real scripts, in document order, without a page reload. Paste snippets with or without their surrounding `<script>` tags — src-only tags become URL scripts automatically.

### Blocked embeds

Embeds from YouTube, Vimeo, Spotify, SoundCloud and Google (plus any custom hosts you add) are **replaced** by a placeholder — never hidden, because a hidden iframe still loads and can still set cookies. The placeholder states whether the visitor hasn't made a choice yet or their preferences don't allow the content, with a button that re-opens the banner.

Coverage:

| Source | Mechanism |
| --- | --- |
| Block editor embed blocks | `embed_oembed_html` (the embed block saves a bare URL that `WP_Embed::autoembed` converts) |
| Classic editor oEmbeds | `embed_oembed_html` via `autoembed` |
| Raw `<iframe>` in content (incl. Custom HTML blocks) | `the_content` pass at priority 20, after `do_blocks` |
| ACF oEmbed fields | `acf/format_value/type=oembed` (ACF fetches iframes itself) |
| ACF WYSIWYG fields | `acf_the_content` pass |

Every handled iframe is tagged with a `data-fc-embed` attribute, so repeated filter runs (pagination, nested content) never process an embed twice. The original embed is kept in an inert `<template>`, which makes up- and downgrading fully reversible without a reload: allowing marketing loads the embed in place, revoking removes it again. Feeds and wp-admin screens are skipped; ajax/REST render contexts are not, so front-end pagination stays blocked and block editor previews honestly show what visitors see.

### Accessibility

Dialog semantics (`role="dialog"`, non-modal) with focus moved to the banner on open and returned to the trigger on choice; `aria-expanded`/`aria-controls` on the options toggle; a polite live region announces saved preferences; visible `:focus-visible` outlines; contrast-safe color defaults; the page gets matching padding while the banner is open so nothing is covered (WCAG 2.2 — Focus Not Obscured); `prefers-reduced-motion` is respected; no `alert()`s and no `location.reload()`s.

## Settings

**Settings → FishCakes**:

- **General** — enable, banner position, consent expiry (days), banner version, privacy page, automatic footer link.
- **Appearance** — five color pickers and a border radius, all output as `--fc-*` CSS custom properties; the stylesheet reads every visual property from those variables.
- **Cookie categories** — labels and descriptions per category (mention purposes and third parties, as the GDPR expects).
- **Banner texts** — every text is editable; empty means "use the translated default" (shown as the placeholder). The intro supports a `{privacy_link}` token and basic HTML.
- **Scripts** — repeater with name, category (analytical/marketing), location (head/footer), inline code or source URL.
- **Blocked embeds** — provider checkboxes, custom hosts (`host.example.com` or `host.example.com|Label` per line), and the three placeholder texts.

## JavaScript API

```js
// Re-open the banner
window.Fishcakes.open();

// Current consent state
window.Fishcakes.getConsent();
// → { hasChoice: true, necessary: true, analytical: true, marketing: false, ts: 1765862400 }

// React to consent changes
document.addEventListener( 'fishcakes:consent-updated', ( event ) => {
	console.log( event.detail );
} );
```

## PHP filters

```php
// Adjust the effective settings
add_filter( 'fishcakes_settings', function ( array $settings ): array {
	$settings['consent_expiry'] = 90;
	return $settings;
} );

// Add/adjust blocked embed providers
add_filter( 'fishcakes_providers', function ( array $providers ): array {
	$providers['example'] = [
		'label' => 'Example',
		'hosts' => [ 'example.com' ],
	];
	return $providers;
} );
```

## Development

No build step: the shipped assets in `assets/` are the source. Yarn is only for lint tooling.

```sh
yarn install
yarn lint          # eslint + stylelint
yarn lint:js
yarn lint:css
```

For local testing, symlink or clone this repository into `wp-content/plugins/fishcakes`.

Useful test scenarios:

1. No consent yet → banner auto-opens, embeds show the "no choice" placeholder, no registered scripts run.
2. Accept all → scripts execute without reload, embeds load in place.
3. Reject all → everything stays blocked with the "not allowed" placeholder.
4. Change your mind via the footer link → checkboxes reflect the current choice; revoking removes embeds immediately.
5. Bump the banner version → the banner returns for every visitor.

## Notes & limitations

- **Revocation and already-loaded scripts:** withdrawing consent stops scripts from loading on the next page view; already-initialized trackers (e.g. GA) cannot be un-executed in the current view. This is the standard behavior for client-side consent tools.
- **Caching:** the consent cookie is read server-side, so consented visitors get embeds rendered directly. With full-page caching that ignores cookies, embeds come back as placeholders and are upgraded in the browser immediately after — privacy-safe either way.
- **`<template>` support:** the inert template technique requires a browser from this decade (everything since ~2017). In truly ancient browsers, blocked embeds would not be replaceable; scripts stay blocked regardless.
- **IAB TCF / cross-domain consent signaling** is out of scope for v2.

## License

MPL-2.0 — see [LICENSE](LICENSE).
