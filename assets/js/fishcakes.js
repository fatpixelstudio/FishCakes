/**
 * FishCakes — cookie consent runtime.
 *
 * Vanilla JS, no dependencies. The server renders the banner (with all
 * texts and settings baked in); this script controls visibility, consent
 * storage, gated script activation and embed placeholders.
 *
 * Public API:
 *   Fishcakes.open()                Re-open the consent banner.
 *   Fishcakes.getConsent()          Current consent state.
 *   fishcakes:consent-updated event Fired on document after every choice.
 *
 * @package FishCakes
 */

( () => {
	'use strict';

	const config = window.FishcakesConfig || {};
	const cookieName = config.cookieName || 'fc_consent';
	const expiryDays = Number( config.expiryDays ) > 0 ? Number( config.expiryDays ) : 182;
	const bannerVersion = Number( config.version ) || 1;
	const CATEGORIES = [ 'analytical', 'marketing' ];

	/* ------------------------------------------------------------------ *
	 * Consent storage
	 *
	 * One first-party cookie holding a JSON record: which categories were
	 * chosen, when, and under which banner version. This is the proof of
	 * consent (GDPR Art. 7(1)) and is readable server-side so PHP can
	 * decide about embeds. The cookie itself is strictly necessary.
	 * ------------------------------------------------------------------ */

	function readCookie( name ) {
		const prefix = name + '=';

		for ( const part of document.cookie.split( ';' ) ) {
			const candidate = part.trim();
			if ( candidate.startsWith( prefix ) ) {
				return candidate.slice( prefix.length );
			}
		}

		return null;
	}

	function readConsent() {
		const raw = readCookie( cookieName );

		if ( ! raw ) {
			return null;
		}

		try {
			const data = JSON.parse( decodeURIComponent( raw ) );

			if ( typeof data !== 'object' || data === null ) {
				return null;
			}

			// A record from another banner version or an expired record
			// counts as "no choice": the visitor must be asked again.
			if ( Number( data.ver ) !== bannerVersion ) {
				return null;
			}

			const timestamp = Number( data.ts ) || 0;
			const maxAge = expiryDays * 86400;

			if ( timestamp < 1 || Date.now() / 1000 - timestamp > maxAge ) {
				return null;
			}

			return {
				necessary: true,
				analytical: data.analytical === true,
				marketing: data.marketing === true,
				ts: timestamp,
			};
		} catch ( error ) {
			return null;
		}
	}

	function writeConsent( analytical, marketing ) {
		const timestamp = Math.floor( Date.now() / 1000 );
		const payload = {
			ver: bannerVersion,
			necessary: true,
			analytical: !! analytical,
			marketing: !! marketing,
			ts: timestamp,
		};
		const secure = window.location.protocol === 'https:' ? '; Secure' : '';

		document.cookie =
			cookieName +
			'=' +
			encodeURIComponent( JSON.stringify( payload ) ) +
			'; max-age=' +
			expiryDays * 86400 +
			'; path=/; SameSite=Lax' +
			secure;

		return payload;
	}

	function consentState() {
		const consent = readConsent();

		if ( consent ) {
			return { hasChoice: true, ...consent };
		}

		return { hasChoice: false, necessary: true, analytical: false, marketing: false, ts: 0 };
	}

	/* ------------------------------------------------------------------ *
	 * DOM references
	 * ------------------------------------------------------------------ */

	const banner = document.querySelector( '[data-fc-banner]' );
	const acceptButton = banner ? banner.querySelector( '[data-fc-accept]' ) : null;
	const rejectButton = banner ? banner.querySelector( '[data-fc-reject]' ) : null;
	const toggleButton = banner ? banner.querySelector( '[data-fc-toggle]' ) : null;
	const form = banner ? banner.querySelector( '[data-fc-form]' ) : null;
	const title = banner ? banner.querySelector( '.fc-banner__title' ) : null;
	const announceRegion = document.querySelector( '[data-fc-announce]' );
	const checks = {
		analytical: document.getElementById( 'fc-check-analytical' ),
		marketing: document.getElementById( 'fc-check-marketing' ),
	};

	let lastFocused = null;

	/* ------------------------------------------------------------------ *
	 * Gated scripts
	 *
	 * Registered scripts are printed by PHP as inert
	 * <script type="text/plain" data-fc-category="…"> placeholders.
	 * They are promoted to real scripts — in document order, without a
	 * page reload — once consent for their category exists.
	 * ------------------------------------------------------------------ */

	function promoteScripts( state ) {
		const placeholders = document.querySelectorAll( 'script[type="text/plain"][data-fc-category]' );

		placeholders.forEach( ( node ) => {
			const category = node.getAttribute( 'data-fc-category' );

			if ( ! CATEGORIES.includes( category ) || ! state[ category ] ) {
				return;
			}

			const replacement = document.createElement( 'script' );
			const skip = [ 'type', 'data-fc-category', 'data-fc-src', 'data-fc-name' ];

			for ( const attribute of Array.from( node.attributes ) ) {
				if ( ! skip.includes( attribute.name ) ) {
					replacement.setAttribute( attribute.name, attribute.value );
				}
			}

			const src = node.getAttribute( 'data-fc-src' );

			if ( src ) {
				replacement.src = src;
			} else {
				replacement.textContent = node.textContent;
			}

			if ( node.parentNode ) {
				node.parentNode.insertBefore( replacement, node.nextSibling );
				node.remove();
			}
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Blocked embeds
	 *
	 * Placeholders carry the original embed in an inert <template>.
	 * Upgrading clones the template into a live node; downgrading
	 * removes it again — fully reversible, no reload either way.
	 * ------------------------------------------------------------------ */

	function updateBlockers( state ) {
		const blockers = document.querySelectorAll( '[data-fc-blocker]' );

		blockers.forEach( ( blocker ) => {
			const template = blocker.querySelector( 'template' );
			const textElement = blocker.querySelector( '.fc-blocker__text' );

			if ( ! template || ! textElement ) {
				return;
			}

			if ( state.marketing ) {
				blocker.classList.add( 'is-allowed' );
				blocker.classList.remove( 'is-blocked' );

				if ( ! blocker.querySelector( '.fc-blocker__embed' ) ) {
					const live = document.createElement( 'div' );
					live.className = 'fc-blocker__embed';
					live.appendChild( template.content.cloneNode( true ) );
					blocker.appendChild( live );
				}
			} else {
				blocker.classList.remove( 'is-allowed' );
				blocker.classList.add( 'is-blocked' );

				const live = blocker.querySelector( '.fc-blocker__embed' );
				if ( live ) {
					live.remove();
				}

				const key = state.hasChoice ? 'data-fc-text-blocked' : 'data-fc-text-nochoice';
				const message = textElement.getAttribute( key );

				if ( message ) {
					textElement.textContent = message;
				}
			}
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Banner control
	 * ------------------------------------------------------------------ */

	function measureBanner() {
		if ( ! banner ) {
			return;
		}

		const height = banner.offsetHeight;

		if ( height > 0 ) {
			document.documentElement.style.setProperty( '--fc-banner-h', height + 'px' );
		}
	}

	function syncChecks() {
		const state = consentState();

		if ( checks.analytical ) {
			checks.analytical.checked = state.analytical;
		}

		if ( checks.marketing ) {
			checks.marketing.checked = state.marketing;
		}
	}

	function openBanner( focus = true ) {
		if ( ! banner || ! banner.hidden ) {
			return;
		}

		if ( focus ) {
			lastFocused = document.activeElement;
		}

		syncChecks();
		banner.hidden = false;
		document.documentElement.classList.add( 'fc-has-banner' );
		document.documentElement.setAttribute( 'data-fc-position', config.position || 'bottom' );
		measureBanner();

		if ( focus && title ) {
			title.focus();
		}
	}

	function closeBanner() {
		if ( ! banner || banner.hidden ) {
			return;
		}

		banner.hidden = true;
		document.documentElement.classList.remove( 'fc-has-banner' );
		document.documentElement.style.removeProperty( '--fc-banner-h' );
		closeOptions();

		if ( lastFocused && document.contains( lastFocused ) && typeof lastFocused.focus === 'function' ) {
			lastFocused.focus();
			lastFocused = null;
		}
	}

	function openOptions() {
		if ( ! form || ! toggleButton || ! form.hidden ) {
			return;
		}

		form.hidden = false;
		toggleButton.setAttribute( 'aria-expanded', 'true' );
		measureBanner();

		const first = form.querySelector( 'input[type="checkbox"]' );
		if ( first ) {
			first.focus();
		}
	}

	function closeOptions() {
		if ( ! form || ! toggleButton || form.hidden ) {
			return;
		}

		form.hidden = true;
		toggleButton.setAttribute( 'aria-expanded', 'false' );
		measureBanner();
		toggleButton.focus();
	}

	function announce( message ) {
		if ( ! announceRegion || ! message ) {
			return;
		}

		announceRegion.textContent = '';

		window.setTimeout( () => {
			announceRegion.textContent = message;
		}, 50 );
	}

	function saveChoice( analytical, marketing ) {
		const payload = writeConsent( analytical, marketing );
		const state = { hasChoice: true, necessary: true, analytical: !! analytical, marketing: !! marketing, ts: payload.ts };

		closeBanner();
		announce( config.savedMessage || '' );
		promoteScripts( state );
		updateBlockers( state );

		document.dispatchEvent( new CustomEvent( 'fishcakes:consent-updated', { detail: state } ) );
	}

	/* ------------------------------------------------------------------ *
	 * Event wiring
	 * ------------------------------------------------------------------ */

	if ( acceptButton ) {
		acceptButton.addEventListener( 'click', () => saveChoice( true, true ) );
	}

	if ( rejectButton ) {
		rejectButton.addEventListener( 'click', () => saveChoice( false, false ) );
	}

	if ( toggleButton ) {
		toggleButton.addEventListener( 'click', () => {
			if ( form && form.hidden ) {
				openOptions();
			} else {
				closeOptions();
			}
		} );
	}

	if ( form ) {
		form.addEventListener( 'submit', ( event ) => {
			event.preventDefault();

			saveChoice(
				!! ( checks.analytical && checks.analytical.checked ),
				!! ( checks.marketing && checks.marketing.checked )
			);
		} );
	}

	// Delegated: works for the footer link, shortcode buttons, embed
	// placeholders and content injected later (pagination, ajax).
	document.addEventListener( 'click', ( event ) => {
		if ( ! ( event.target instanceof Element ) ) {
			return;
		}

		const trigger = event.target.closest( '[data-fc-open]' );

		if ( ! trigger ) {
			return;
		}

		event.preventDefault();
		openBanner();
	} );

	window.addEventListener( 'resize', () => {
		if ( banner && ! banner.hidden ) {
			measureBanner();
		}
	} );

	/* ------------------------------------------------------------------ *
	 * Public API + boot
	 * ------------------------------------------------------------------ */

	window.Fishcakes = {
		open: openBanner,
		getConsent: consentState,
	};

	function init() {
		const state = consentState();

		if ( state.hasChoice ) {
			promoteScripts( state );
			updateBlockers( state );
		} else {
			updateBlockers( state );
			openBanner();
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
