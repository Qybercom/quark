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

	//if (target.constructor == Object || target.constructor == Array)
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

	that.On = function (name, callback) {
		if (!(callback instanceof Function)) return false;
		if (!(that._events[name] instanceof Array)) return false;

		that._events[name].push(callback);

		return true;
	};

	that.Off = function (name, callback) {

	};

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