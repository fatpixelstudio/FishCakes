/**
 * Cookies, omnomnomnom!
 *
 * Inspiration by the man, the myth, the legend: PPK
 * More info on: http://www.quirksmode.org/js/cookies.html
 */

var cookie = {

	set: function (name, value, days) {
		var expires;
		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
			expires = '; expires=' + date.toGMTString();
		} else {
			expires = '';
		}
		document.cookie = name + '=' + value + expires + '; path=/';
	},

	erase: function (name) {
		cookie.set(name, '', -1);
	},

	get: function (name) {
		var nameEQ = name + '=';
		var ca = document.cookie.split(';');
		for (var i = 0; i < ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0) === ' ') {
				c = c.substring(1, c.length);
			}
			if (c.indexOf(nameEQ) === 0) {
				return c.substring(nameEQ.length, c.length);
			}
		}
		return null;
	}
};

/* jshint ignore:start */
/*!
 * domready (c) Dustin Diaz 2014 - License MIT
 */
! function (name, definition) {

	if (typeof module != 'undefined') module.exports = definition()
	else if (typeof define == 'function' && typeof define.amd == 'object') define(definition)
	else this[name] = definition()

}('domready', function () {

	var fns = [],
		listener, doc = typeof document === 'object' && document,
		hack = doc && doc.documentElement.doScroll,
		domContentLoaded = 'DOMContentLoaded',
		loaded = doc && (hack ? /^loaded|^c/ : /^loaded|^i|^c/).test(doc.readyState)


	if (!loaded && doc)
		doc.addEventListener(domContentLoaded, listener = function () {
			doc.removeEventListener(domContentLoaded, listener)
			loaded = 1
			while (listener = fns.shift()) listener()
		})

	return function (fn) {
		loaded ? setTimeout(fn, 0) : fns.push(fn)
	}
});
/* jshint ignore:end */

var Fishcakes = (function () {

	var elements = {
		fishcakesbanner : document.querySelector('.js-fishcakes'),
		fishcakesacceptall : document.querySelector('.js-fishcakes-accept-all'),
		fishcakesoptions : document.querySelector('.js-fishcakes-options'),
		fishcakesoptionsbutton : document.querySelector('.js-fishcakes-options-button'),
		fishcakesinfobutton : document.querySelectorAll('.js-fishcakes-moreinfo-button'),
		fishcakesoptionsform : document.querySelector('.js-fishcakes-form'),
		fishcakesedit : document.querySelector('.js-fishcakes-edit')
	};

	function toggleInfo(event) {
		event.preventDefault();

		var button = event.target;
		var id = button.getAttribute('href');
		var target = elements.fishcakesbanner.querySelector(id);

		if (target.classList.contains('is-active')) {
			target.classList.remove('is-active');
			button.textContent = button.getAttribute('data-text-show');
		} else {
			target.classList.add('is-active');
			button.textContent = button.getAttribute('data-text-hide');
		}
	}

	function toggleOptions(event) {
		event.preventDefault();

		if(elements.fishcakesoptions.classList.contains('is-active')) {
			elements.fishcakesoptions.classList.remove('is-active');
			elements.fishcakesoptionsbutton.textContent = elements.fishcakesoptionsbutton.getAttribute('data-text-show');
		}
		else {
			elements.fishcakesoptions.classList.add('is-active');
			elements.fishcakesoptionsbutton.textContent = elements.fishcakesoptionsbutton.getAttribute('data-text-hide');
		}
	}

	function closeBanner() {
		elements.fishcakesbanner.classList.remove('is-active');
		elements.fishcakesbanner.classList.add('is-hidden');
		cookie.set('hide_cookie_banner', 'yes', 365);

		var marketingembeds = document.querySelectorAll('.js-fishcakes-marketing-embed');
		if(marketingembeds !== null && marketingembeds.length > 0 && cookie.get('cookie_consent_marketing') === 'yes') {
			location.reload();
		}
	}

	function consentAllScript() {
		// console.log('i give my consent to all');
		cookie.set('cookie_consent_analytical', 'yes', 365);
		cookie.set('cookie_consent_marketing', 'yes', 365);
		closeBanner();
		fishcakeswrapper_head(true, true);
		fishcakeswrapper_foot(true, true);
	}

	function consentChoice(analytical, marketing) {
		var choiceanalytical = (analytical) ? 'yes' : 'no';
		var choicemarketing = (marketing) ? 'yes' : 'no';

		// console.log('i consent ' + choiceanalytical + 'to analytical cookies');
		// console.log('i consent ' + choicemarketing + 'to marketing cookies');

		cookie.set('cookie_consent_analytical', choiceanalytical, 365);
		cookie.set('cookie_consent_marketing', choicemarketing, 365);
		closeBanner();
		fishcakeswrapper_head(analytical, marketing);
		fishcakeswrapper_foot(analytical, marketing);
	}

	function resetOptions(event) {
		event.preventDefault();

		elements.fishcakesbanner.classList.remove('is-hidden');
		elements.fishcakesbanner.classList.add('is-active');
		cookie.erase('hide_cookie_banner');
	}

	function handleForm(event) {
		event.preventDefault();

		var form = event.target;
		var analytical = form.querySelector('input[name=analytical]').checked;
		var marketing = form.querySelector('input[name=marketing]').checked;

		consentChoice(analytical, marketing);
		// console.log('submit options');
		// console.log('analytical: ' + analytical);
		// console.log('marketing: ' + marketing);
	}

	function init() {
		elements.fishcakesbanner = document.querySelector('.js-fishcakes');
		elements.fishcakesacceptall = document.querySelector('.js-fishcakes-accept-all');
		elements.fishcakesoptions = document.querySelector('.js-fishcakes-options');
		elements.fishcakesoptionsbutton = document.querySelector('.js-fishcakes-options-button');
		elements.fishcakesinfobutton = document.querySelectorAll('.js-fishcakes-moreinfo-button');
		elements.fishcakesoptionsform = document.querySelector('.js-fishcakes-form');
		elements.fishcakeseditbutton = document.querySelectorAll('.js-fishcakes-edit');

		if (cookie.get('hide_cookie_banner') === 'yes') {
			elements.fishcakesbanner.classList.add('is-hidden');
			var loadchoiceanalytical = cookie.get('cookie_consent_analytical') === 'yes';
			var loadchoicemarketing = cookie.get('cookie_consent_marketing') === 'yes';
			fishcakeswrapper_head(loadchoiceanalytical, loadchoicemarketing);
			fishcakeswrapper_foot(loadchoiceanalytical, loadchoicemarketing);
		} else {
			elements.fishcakesbanner.classList.add('is-active');
		}

		elements.fishcakesacceptall.addEventListener('click', consentAllScript, false);
		elements.fishcakesoptionsbutton.addEventListener('click', toggleOptions, false);
		elements.fishcakesoptionsform.addEventListener('submit', handleForm, false);

		for(i = 0; i < elements.fishcakesinfobutton.length; i++) {
			elements.fishcakesinfobutton[i].addEventListener('click', toggleInfo, false);
		}

		if(elements.fishcakeseditbutton !==  null && elements.fishcakeseditbutton.length > 0) {
			for (i = 0; i < elements.fishcakeseditbutton.length; i++) {
				elements.fishcakeseditbutton[i].addEventListener('click', resetOptions, false);
			}
		}
	}

	/**
	 * Return public methods
	 */
	return {
		init: init
	};
})();

// Executed on DOM ready
domready(function () {
	Fishcakes.init();
});
