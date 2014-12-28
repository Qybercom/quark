/**
 * JS part of Quark PHP framework
 */
var Quark = {
	EventValidateError: 'quark.validation.error',
	EventSubmitError: 'quark.submit.error',
	EventSubmitSuccess: 'quark.submit.success'
};

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
		that._events[event[i]] = [];

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