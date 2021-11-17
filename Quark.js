/**
 * JS part of Quark PHP framework
 */
var Quark = Quark || {};

Quark.Language = navigator.language || navigator.userLanguage;
Quark.LanguageFamily = Quark.Language.split('-')[0];

Quark.EventValidateError = 'quark.validation.error';
Quark.EventSubmitError = 'quark.submit.error';
Quark.EventSubmitSuccess = 'quark.submit.success';

/**
 * @param target
 * @param defaults
 */
Quark.Extend = function (target, defaults) {
	target = target || {};

	var k;

	for (k in defaults) {
		if (defaults[k] != undefined && (defaults[k].constructor == Object || defaults[k].constructor == Array))
			target[k] = Quark.Extend(target[k], defaults[k]);
		else target[k] = target[k] !== undefined ? target[k] : defaults[k];
	}

	return target;
};

/**
 * Original algorithm from
 * http://stackoverflow.com/a/2117523/2097055
 *
 * @return {string}
 */
Quark.GuID = function () {
	return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
		var r = Math.random() * 16|0;
		return (c == 'x' ? r : (r&0x3|0x8)).toString(16);
	});
};

/**
 * @param events
 *
 * @constructor
 */
Quark.Event = function (events) {
	var that = this, i = 0;

	that._events = {};

	while (i < events.length) {
		that._events[events[i]] = [];

		i++;
	}

	/**
	 * @param name
	 * @param callback
	 *
	 * @return {boolean}
	 */
	that.On = function (name, callback) {
		if (!(callback instanceof Function)) return false;
		if (!(that._events[name] instanceof Array)) return false;

		that._events[name].push(callback);

		return true;
	};

	/**
	 * @param name
	 * @param callback
	 */
	that.Off = function (name, callback) {

	};

	/**
	 * @param name
	 * @param args
	 *
	 * @return {boolean}
	 */
	that.Dispatch = function (name, args) {
		if (!(that._events[name] instanceof Array)) return false;

		var i = 0;

		while (i < that._events[name].length) {
			that._events[name][i](args);

			i++;
		}
	};
};

Quark.ServiceWorker = function () {};
Quark.ServiceWorker.Event = Quark.ServiceWorker.Event || {};
Quark.ServiceWorker.Event.Installed = Quark.ServiceWorker.Event.Installed || function () {};
Quark.ServiceWorker.Event.NotificationIncoming = Quark.ServiceWorker.Event.NotificationIncoming || function () {};
Quark.ServiceWorker.Event.NotificationDenied = Quark.ServiceWorker.Event.NotificationDenied || function () {};
Quark.ServiceWorker.Event.NotificationReady = Quark.ServiceWorker.Event.NotificationReady || function () {};
Quark.ServiceWorker.Event.NotificationClick = Quark.ServiceWorker.Event.NotificationClick || function () {};

/**
 * @param {string} url
 * @param {object=} opt
 */
Quark.ServiceWorker.Register = function (url, opt) {
	opt = opt || {};
		opt.scope = opt.scope || '/';

	navigator.serviceWorker
		.register(url, {scope: opt.scope})
		.then(function (registration) {
			if (opt.success instanceof Function)
				opt.success(registration);
		});
};

/**
 * https://stackoverflow.com/questions/29774836/failed-to-construct-notification-illegal-constructor
 * https://medium.com/@madridserginho/how-to-handle-webpush-api-pushsubscriptionchange-event-in-modern-browsers-6e47840d756f
 * https://developer.mozilla.org/en-US/docs/Web/API/PushSubscription/endpoint
 *
 * @param {Function} callback
 */
Quark.ServiceWorker.Ready = function (callback) {
	self.addEventListener('install', function (eventInstalled) {
		self.skipWaiting();

		if (Quark.ServiceWorker.Event.Installed instanceof Function)
			Quark.ServiceWorker.Event.Installed(eventInstalled);
	});

	self.addEventListener('activate', callback);

	self.addEventListener('push', function (eventPush) {
		if (Quark.ServiceWorker.Event.NotificationIncoming instanceof Function)
			Quark.ServiceWorker.Event.NotificationIncoming(eventPush);

		if (!self.Notification || self.Notification.permission !== 'granted') {
			if (Quark.ServiceWorker.Event.NotificationDenied instanceof Function)
				Quark.ServiceWorker.Event.NotificationDenied(self.Notification.permission);

			return;
		}

		var data = eventPush.data.json();

		if (Quark.ServiceWorker.Event.NotificationReady instanceof Function)
			Quark.ServiceWorker.Event.NotificationReady(data);

		self.registration.notificationclick = function (eventClick) {
			if (Quark.ServiceWorker.Event.NotificationClick instanceof Function)
				Quark.ServiceWorker.Event.NotificationClick(eventClick);
		};

		self.registration.showNotification(data.title, data);
	});
};

Quark.Notification = function () {};

/**
 * @param {string} url
 * @param {object=} opt
 */
Quark.Notification.RequestPermission = function (url, opt) {
	opt = opt || {};
		opt.scope = opt.scope || '/';

	Notification.requestPermission(function (status) {
		if (status == 'granted') Quark.ServiceWorker.Register(url, opt);
		else {
			if (opt.denied instanceof Function)
				opt.denied(status);
		}
	});
};

/**
 * @param {string} vapidPublic
 * @param {Function=} success
 * @param {Function=} error
 */
Quark.Notification.Subscribe = function (vapidPublic, success, error) {
	self.registration.pushManager
		.subscribe({
			userVisibleOnly: true,
			applicationServerKey: Quark.Base64.DecodeUInt8Array(vapidPublic)
		})
		.then(success)
		.catch(error);
};

/**
 * @param {string} appVAPIDPublic
 * @param {string} appURLDeviceRegister
 */
Quark.Notification.SubscribeAndRegister = function (appVAPIDPublic, appURLDeviceRegister) {
	Quark.Notification.Subscribe(appVAPIDPublic, function (subscription) {
		Quark.Request.POSTFormURLEncoded(
			appURLDeviceRegister,
			{
				device: {
					type: 'web',
					'id': JSON.stringify(subscription)
				}
			}
		);
	});
};

/**
 * https://stackoverflow.com/a/37562814/2097055
 *
 * @param {object|array} data
 * @param {object} serializer
 * @param {string=} prefix
 *
 * @return {object|string}
 */
Quark.FormSerialize = function (data, serializer, prefix) {
	var key = null;

	for (key in data) {
		if (data[key].constructor == Object) {
			Quark.FormSerialize(data[key], serializer, key);
			continue;
		}

		if (data[key].constructor == Array) {
			Quark.FormSerialize(data[key], serializer, key + '[]');
			continue;
		}

		serializer.append(prefix == undefined ? key : prefix + '[' + key + ']', data[key]);
	}

	return serializer;
};

/**
 * @param {string} url
 * @param {object=} opt
 * @param {Function=} success
 * @param {Function=} error
 *
 * @return {Promise<Response>}
 */
Quark.Request = function (url, opt, success, error) {
	return fetch(url, opt || {}).then(success).catch(error);
};

/**
 * @param {string} url
 * @param {Function=} success
 * @param {Function=} error
 * @param {object=} opt
 *
 * @return {Promise<Response>}
 */
Quark.Request.GET = function (url, success, error, opt) {
	opt = opt || {};
		opt.method = opt.method || 'GET';

	return Quark.Request(url, opt, success, error);
};

/**
 * @param {string} url
 * @param {any} data
 * @param {Function=} success
 * @param {Function=} error
 * @param {object=} opt
 *
 * @return {Promise<Response>}
 */
Quark.Request.POST = function (url, data, success, error, opt) {
	opt = opt || {};
		opt.method = opt.method || 'POST';
		opt.body = data;

	return Quark.Request(url, opt, success, error);
};

/**
 * @param {string} url
 * @param {any} data
 * @param {Function} serializer
 * @param {Function=} success
 * @param {Function=} error
 * @param {object=} opt
 *
 * @return {Promise<Response>}
 */
Quark.Request.POSTForm = function (url, data, serializer, success, error, opt) {
	return Quark.Request.POST(url, Quark.FormSerialize(data, serializer), success, error, opt);
};

/**
 * @param {string} url
 * @param {object|array=} data
 * @param {Function=} success
 * @param {Function=} error
 * @param {object=} opt
 *
 * @return {Promise<Response>}
 */
Quark.Request.POSTFormURLEncoded = function (url, data, success, error, opt) {
	return Quark.Request.POSTForm(url, data, new URLSearchParams(), success, error, opt);
};

/**
 * @param {string} url
 * @param {object|array=} data
 * @param {Function=} success
 * @param {Function=} error
 * @param {object=} opt
 *
 * @return {Promise<Response>}
 */
Quark.Request.POSTMultipartFormData = function (url, data, success, error, opt) {
	return Quark.Request.POSTForm(url, data, new FormData(), success, error, opt);
};

/**
 * @url http://javascript.ru/unsorted/top-10-functions
 */
Quark.Cookie = {};

/**
 * @param name
 *
 * @return {string|undefined}
 */
Quark.Cookie.Get = function (name) {
	var cookies = document.cookie.split('; '), i = 0, cookie = [];

	while (i < cookies.length) {
		cookie = cookies[i].trim().split('=');

		if (cookie.length == 2 && cookie[0] == name)
			return decodeURIComponent(cookie[1]);

		i++;
	}

	return undefined;
};

/**
 * @param name
 * @param value
 * @param opt
 */
Quark.Cookie.Set = function (name, value, opt) {
	opt = opt || {};

	var expires = opt.expires;

	if (typeof expires == 'number' && expires) {
		var d = new Date();
		d.setTime(d.getTime() + expires * 1000);
		expires = opt.expires = d;
	}

	if (expires && expires.toUTCString)
		opt.expires = expires.toUTCString();

	value = encodeURIComponent(value);

	var cookie = name + '=' + value;

	for (var property in opt) {
		cookie += '; ' + property;

		var val = opt[property];

		if (val !== true)
			cookie += '=' + val;
	}

	document.cookie = cookie;
};

/**
 * @param name
 */
Quark.Cookie.Remove = function (name) {
	Quark.Cookie.Set(name, null, {
		expires: -1
	});
};

/**
 * https://developer.mozilla.org/ru/docs/Web/JavaScript/Reference/Global_Objects/String/Trim
 */
if (!String.prototype.trim) {
	(function () {
		String.prototype.trim = function () {
			return this.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');
		};
	})();
}

/**
 * http://javascript.ru/blog/ixth/minmax-dlya-massivov
 */
if (!Array.prototype.max) {
	(function () {
		Array.prototype.max = function () {
			return Math.max.apply(Math, this);
		}
	})();
}

if (!Array.prototype.min) {
	(function () {
		Array.prototype.min = function () {
			return Math.min.apply(Math, this);
		}
	})();
}

/**
 * https://stackoverflow.com/a/4026828/2097055
 */
if (!Array.prototype.diff)
	(function () {
		Array.prototype.diff = function(a) {
			return this.filter(function(i) { return a.indexOf(i) < 0; });
		};
	})();

/**
 * https://stackoverflow.com/a/9716515/2097055
 *
 * @param value
 *
 * @returns {boolean}
 */
Quark.IsNumeric = function (value) {
	return !isNaN(parseFloat(value)) && isFinite(value);
};

/**
 * http://artkiev.com/blog/number_format-in-javascript.htm
 *
 * @param {number|string} number
 * @param {int=} decimals
 * @param {string=} dec_point
 * @param {string=} separator
 *
 * @returns {string}
 */
Quark.NumberFormat = function number_format (number, decimals, dec_point, separator) {
	number = (number + '').replace(/[^0-9+\-Ee.]/g, '');

	var n = !isFinite(+number) ? 0 : +number,
		precision = !isFinite(+decimals) ? 0 : Math.abs(decimals),
		sep = (typeof separator === 'undefined') ? ',' : separator,
		dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
		toFixedFix = function(n, precision) {
			var k = Math.pow(10, precision);
			return '' + (Math.round(n * k) / k).toFixed(precision);
		},
		s = (precision ? toFixedFix(n, precision) : '' + Math.round(n)).split('.');

	if (s[0].length > 3)
		s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);

	if ((s[1] || '').length < precision) {
		s[1] = s[1] || '';
		s[1] += new Array(precision - s[1].length + 1).join('0');
	}

	return s.join(dec);
};

if (!Number.prototype.format) {
	(function () {
		Number.prototype.format = function (decimals, dec_point, separator) {
			return Quark.NumberFormat(this, decimals, dec_point, separator);
		};
	})();
}

/**
 * http://stackoverflow.com/a/324533/2097055
 *
 * @param {string} selector
 *
 * @return {string}
 */
Quark.CSS = function (selector) {
	var i = 0,
		selectors = document.styleSheets[0].rules || document.styleSheets[0].cssRules;

	while (i < selectors.length) {
		if (selectors[x].selectorText == selector)
			return selectors[x].cssText
				? selectors[x].cssText
				: selectors[x].style.cssText;

		i++;
	}
};

/**
 * http://stackoverflow.com/a/6969486
 *
 * @param {string} str
 *
 * @return {string}
 */
Quark.EscapeRegEx = function (str) {
	return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
};

/**
 * https://stackoverflow.com/a/4835406/2097055
 *
 * @param {string} str
 *
 * @return {string}
 */
Quark.EscapeHTML = function (str) {
	var key = '',
		keys = [],
		map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};

	for (key in map)
		keys.push(key);

	return str.replace(
		new RegExp('[' + keys.join('') + ']', 'g'),
		function(m) { return map[m]; }
	);
};

/**
 * http://stackoverflow.com/a/25840184/2097055
 */
Quark.Base64 = {
	_keyStr: 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=',
	/**
	 * @param {string} e
	 * @return {string}
	 */
	Encode: function (e) {
		var t = '', n, r, i, s, o, u, a, f = 0;

		e = Quark.Base64._utf8_encode(e);

		while (f < e.length) {
			n = e.charCodeAt(f++);
			r = e.charCodeAt(f++);
			i = e.charCodeAt(f++);

			s = n >> 2;
			o = (n & 3) << 4 | r >> 4;
			u = (r & 15) << 2 | i >> 6;
			a = i & 63;

			if (isNaN(r)) u = a = 64;
			else if (isNaN(i)) a = 64;

			t = t
				+ this._keyStr.charAt(s)
				+ this._keyStr.charAt(o)
				+ this._keyStr.charAt(u)
				+ this._keyStr.charAt(a)
		}

		return t;
	},
	/**
	 * @param {string} e
	 *
	 * @return {string}
	 */
	Decode: function (e) {
		var t = '', n, r, i, s, o, u, a, f = 0;

		e = e.toString().replace(/[^A-Za-z0-9\+\/\=]/g, '');

		while (f < e.length) {
			s = this._keyStr.indexOf(e.charAt(f++));
			o = this._keyStr.indexOf(e.charAt(f++));
			u = this._keyStr.indexOf(e.charAt(f++));
			a = this._keyStr.indexOf(e.charAt(f++));

			n = s << 2 | o >> 4;
			r = (o & 15) << 4 | u >> 2;
			i = (u & 3) << 6 | a;

			t = t + String.fromCharCode(n);

			if (u != 64)
				t = t + String.fromCharCode(r);

			if (a != 64)
				t = t + String.fromCharCode(i);
		}

		t = Quark.Base64._utf8_decode(t);

		return t;
	},
	/**
	 * @param {string} e
	 * @return {string}
	 */
	_utf8_encode: function (e) {
		e = e.toString().replace(/\r\n/g,"\n");

		var t = '', n = 0;

		while (n < e.length) {
			var r = e.charCodeAt(n);

			if (r < 128) {
				t += String.fromCharCode(r);
			}
			else if(r > 127 && r < 2048) {
				t += String.fromCharCode(r >> 6 | 192);
				t += String.fromCharCode(r & 63 | 128);
			}
			else {
				t += String.fromCharCode(r >> 12 | 224);
				t += String.fromCharCode(r >> 6 & 63 | 128);
				t += String.fromCharCode(r & 63 | 128);
			}

			n++;
		}

		return t;
	},
	/**
	 * @param {string} e
	 * @return {string}
	 */
	_utf8_decode: function (e) {
		var t = '', n = 0, r = 0, c1 = 0, c2 = 0;

		while (n < e.length) {
			r = e.charCodeAt(n);

			if (r < 128) {
				t += String.fromCharCode(r);
				n++;
			}
			else if (r > 191 && r < 224) {
				c1 = e.charCodeAt(n + 1);
				t += String.fromCharCode((r & 31) << 6 | c1 & 63);
				n += 2;
			}
			else {
				c1 = e.charCodeAt(n + 1);
				c2 = e.charCodeAt(n + 2);

				t += String.fromCharCode((r & 15) << 12 | (c1 & 63) << 6 | c2 & 63);
				n += 3;
			}
		}

		return t;
	},

	DecodeUInt8Array: function (raw) {
		var padding = '='.repeat((4 - raw.length % 4) % 4),
			source = atob((raw + padding)
				.replace(/\-/g, '+')
				.replace(/_/g, '/')
			),
			out = new Uint8Array(source.length),
			i = 0;

		while (i < source.length) {
			out[i] = source.charCodeAt(i);

			i++;
		}

		return out;
	}
};

/**
 * @param obj
 *
 * @return {string}
 */
Quark.ObjectURL = function (obj) {
	var url = window.URL || window.webkitURL;
	
	return url.createObjectURL(obj);
};

/**
 * @param {*} data
 * @param {string} type
 *
 * @return {string}
 */
Quark.DataURL = function (data, type) {
	return 'data:' + type + ';base64,' + Quark.Base64.Encode(data);
};

/**
 * @param {ArrayBuffer} buffer
 *
 * @constructor
 */
Quark.DataView = function (buffer) {
	var that = this;
	
	/**
	 * @type {ArrayBuffer}
	 */
	that.Buffer = new DataView(buffer);
	
	/**
	 * @param offset
	 * @param str
	 */
	that.WriteString = function (offset, str) {
		var i = 0;
		
		while (i < str.length) {
			that.Buffer.setUint8(offset + i, str.charCodeAt(i));
			i++;
		}
	};
	
	/**
	 * @param offset
	 * @param input
	 */
	that.PCM16Bit = function (offset, input) {
		var i = 0, s = 0;
		
		while (i < input.length) {
			s = Math.max(-1, Math.min(1, input[i]));
			that.Buffer.setInt16(offset, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
			
			i++;
			offset += 2;
		}
	};
};

/**
 * @param size
 *
 * @return {Quark.DataView}
 */
Quark.DataView.WithBuffer = function (size) {
	return new Quark.DataView(new ArrayBuffer(size));
};


// https://github.com/uxitten/polyfill/blob/master/string.polyfill.js
// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/padStart
if (!String.prototype.padStart) {
	String.prototype.padStart = function padStart (targetLength, padString) {
		targetLength = targetLength >> 0; //floor if number or convert non-number to 0;
		padString = String(padString || ' ');

		if (this.length > targetLength) return String(this);
		else {
			targetLength = targetLength - this.length;

			if (targetLength > padString.length)
				padString += padString.repeat(targetLength / padString.length); //append to original to ensure we are longer than needed

			return padString.slice(0,targetLength) + String(this);
		}
	};
}

/**
 * https://stackoverflow.com/a/14167041/2097055
 *
 * @param {string} url
 * @param {string=} windowName
 * @param {object=} windowFeatures
 * @param {number=} windowCloseTimeout
 */
Quark.Print = function (url, windowName, windowFeatures, windowCloseTimeout) {
	windowName = windowName || 'Print';
	windowFeatures = windowFeatures || {
		left: 100, top: 100,
		width: 1024, height: 768
	};
		windowFeatures.toolbar = windowFeatures.toolbar || 0;
		windowFeatures.resizable = windowFeatures.resizable || 0;

	windowCloseTimeout = windowCloseTimeout || 500;

	var windowFeaturesOut = JSON.stringify(windowFeatures)
		.replace(/{/g, '')
		.replace(/}/g, '')
		.replace(/:/g, '=')
		.replace(/,/g, ', ')
		.replace(/"/g, '');

	var windowTarget = window.open(url, windowName, windowFeaturesOut);

	windowTarget.addEventListener('load', function () {
		windowTarget.print();

		setTimeout(function(){
			windowTarget.close();
		}, windowCloseTimeout);
	}, true);
};