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

var fishcakesbanner = document.querySelector('.js-fishcakes');
var fishcakesbutton = document.querySelector('.js-fishcakes-close');
var fishcakesoptions = document.querySelector('.js-fishcakes-options');

if (cookie.get('hide_cookie_banner') === 'yes') {
	fishcakesbanner.classList.add('is-hidden');
	// if(navigator.doNotTrack)

} else {
	fishcakesbanner.classList.add('is-active');
}

function closeBanner() {
	fishcakesbanner.classList.remove('is-active');
	fishcakesbanner.classList.add('is-hidden');
	cookie.set('hide_cookie_banner', 'yes', 365);
}

function consentScript() {
	console.log('i give my consent');
	var scripts = document.querySelectorAll('.fishcakes-consent-script');
	for(i = 0; i < scripts.length; i++) {
		scripts[i].setAttribute('type', 'text/javascript');
	}
}

fishcakesbutton.addEventListener('click', consentScript, false);
fishcakesbutton.addEventListener('click', closeBanner, false);
