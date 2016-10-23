/**
 * JS part of Quark PHP framework
 */
var Quark = Quark || {};

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
	 * W
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

/**
 * @url http://javascript.ru/unsorted/top-10-functions
 */
Quark.Cookie = {};

/**
 * @param name
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

    if(expires && expires.toUTCString)
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
    (function() {
        String.prototype.trim = function() {
            return this.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');
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
	 * @return {string}
	 */
	Decode: function (e) {
		var t = '', n, r, i, s, o, u, a, f = 0;

		e = e.replace(/[^A-Za-z0-9\+\/\=]/g, '');

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
		e = e.replace(/\r\n/g,"\n");

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
	}
};