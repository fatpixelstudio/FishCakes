var fishcakesbanner = document.querySelector('.js-fishcakes');
var fishcakesbutton = document.querySelector('.js-fishcakes-close');

if (cookie.get('hide_cookie_banner') === 'yes') {
	fishcakesbanner.classList.add('is-hidden');
} else {
	fishcakesbanner.classList.add('is-active');
}

function closeBanner() {
	fishcakesbanner.classList.remove('is-active');
	fishcakesbanner.classList.add('is-hidden');
	cookie.set('hide_cookie_banner', 'yes', 365);
}

fishcakesbutton.addEventListener('click', closeBanner, false);
