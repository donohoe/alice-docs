/*
	main.js
	https://github.com/donohoe/alice-docs/tree/master/public/js/main.js
*/
var _Alice= {
	init: function(){
		this.addEvents();
		this.findElementsInView();
	},

	addEvents: function() {
		window.addEventListener('scroll', this.onScroll, false);
	},

	removeEvents: function() {
		window.removeEventListener('scroll', this.onScroll, false);
	},

	onScroll: function () {
		_Alice.findElementsInView();
	},

	findElementsInView: function () {
		var elms = document.querySelectorAll('[data-progressive]');
		var len = elms.length;
		if (len === 0) {
			this.removeEvents();
		}
		var lookForward = ((window.innerHeight || document.documentElement.clientHeight) / 100 * 50);
		for (var i = 0; i < len; i++) {
			var el = elms[i];
			var inView = _Alice.scrolledIntoView(el, lookForward);
			if (inView) {
				this.renderElementInView(el);
			}
		}
	},

	scrolledIntoView: function (el, lookForward) {
		var lookForward = lookForward || 0;
		var coords = el.getBoundingClientRect();
		var h = window.innerHeight || document.documentElement.clientHeight;
		if (lookForward) {
			h = h + parseInt(lookForward, 10);
		}
		return ((coords.top >= 0 && coords.left >= 0 && coords.top) <= h);
	},

	renderElementInView: function (el) {
		var itemType = el.getAttribute('data-component') || false;
		if (!itemType) {
			return;
		}
		switch (itemType) {
			case 'slide':
			case 'photo':
				var img = el.querySelector('img') || false;
				if (img) {
				//	_Alice.Image.x(img, x);
				}
				break;
			case 'video':
			//	x.load(el);
				break;
			case 'facebook':
			case 'youtube':
			case 'soundcloud':
			case 'spotify':
			case 'google-maps':
			case 'iframe':
				_Alice.IframeEmbed.load(el);
				break;
			case 'documentcloud':
				_Alice.DocumentCloud.loadEmbed(el);
				break;
			case 'instagram':
			case 'twitter':
			case 'facebook':
			case 'script':
				_Alice.scriptEmbed(el);
				break;
			default:
		}
		el.removeAttribute('data-progressive');
	},

	scriptEmbed: function(el, callback){
		console.log('loadEmbedByJS');
		var callback = callback || false;
		var src = el.getAttribute('data-js') || false;
		if (src) {
			var h = document.head || document.getElementsByTagName('head')[0];
			var s = document.createElement('script');
			if (callback && typeof callback === 'function') {
				s.onload = function () {
					if (callback) { callback(); }
				};
			}
			s.src = src;
			h.appendChild(s);
		}
	}
};

document.addEventListener("DOMContentLoaded", function(event) {
	_Alice.init();
});
