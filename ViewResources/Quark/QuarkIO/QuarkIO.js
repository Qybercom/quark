/**
 * JS part of SaaS PHP framework
 *
 * @type {Quark}
 */
var Quark = Quark || {};

/**
 * Quark.IO namespace
 */
Quark.IO = {};

/**
 * @class Quark.IO.Mouse
 *
 * @param selector
 * @constructor
 */
Quark.IO.Mouse = function (selector) {
	var that = this;

	that.Elem = $(selector);

	that._pointerLockChange = function () {};

	/**
	 * @param opt
	 */
	that.PointerLock = function (opt) {
		that.Elem.each(function (i) {
			that.Elem[i].requestPointerLock = that.Elem[i].requestPointerLock
											|| that.Elem[i].mozRequestPointerLock
											|| that.Elem[i].webkitRequestPointerLock;

			that.Elem[i].requestPointerLock();
		});
	};

	/**
	 * @param opt
	 */
	that.Drag = function (opt) {
		opt = Quark.Extend(opt, {
			start: false,
			drag: false,
			stop: false,

			cancel: 'input, select',
			handle: false,

			preventDefault: true,
			delegateParent: true
		});

		var e, target, position = {}, frame = {};

		$(document).on('mousedown', selector, function (e) {
			target = $(e.target);

			if (target.is(opt.cancel)) return true;
			if (opt.handle != false && !target.is(opt.handle)) return true;
			if (opt.delegateParent && !target.is(selector))
				target = target.parent(selector);

			position = target.position();
			frame = {
				target: target,
				current: {
					x: e.pageX,
					y: e.pageY
				},
				scroll: {
					x: target.parent().scrollLeft(),
					y: target.parent().scrollTop()
				},
				point: {
					x: e.pageX - position.left,
					y: e.pageY - position.top
				}
			};

			if (opt.start instanceof Function) opt.start(frame);

			$(document).on(
				'mousemove',
				{
					frame: frame,
					drag: opt.drag
				},
				Quark.IO.Mouse._drag
			);

			return !opt.preventDefault;
		});

		$(document).on('mouseup', function (e) {
			$(document).off('mousemove', Quark.IO.Mouse._drag);

			if (!(opt.stop instanceof Function)) return;

			target = $(e.target).offset();

			opt.stop({
				x: target.left,
				y: target.top,
				target: target
			});
		});
	}
};

/**
 * @param e
 * @private
 */
Quark.IO.Mouse._drag = function (e) {
	var position = {
		x: e.pageX - e.data.frame.point.x + e.data.frame.scroll.x,
		y: e.pageY - e.data.frame.point.y + e.data.frame.scroll.y
	};
	var prev = e.data.frame.target.data('_prev') || position;
	var direction = {
		x: 0,
		y: 0
	};

	if (prev.x > position.x) direction.x = -1;
	if (prev.x < position.x) direction.x = 1;
	if (prev.y > position.y) direction.y = -1;
	if (prev.y < position.y) direction.y = 1;

	e.data.drag({
		target: e.data.frame.target,
		point: e.data.frame.point,
		scroll: e.data.frame.scroll,
		position: position,
		direction: direction,
		current: {
			x: e.pageX,
			y: e.pageY
		},
		delta: {
			x: e.pageX - e.data.frame.current.x,
			y: e.pageY - e.data.frame.current.y
		}
	});

	e.data.frame.target.data('_prev', position);
};

/**
 * @class Quark.IO.Keyboard
 *
 * @param selector
 *
 * @constructor
 */
Quark.IO.Keyboard = function (selector) {
	var that = this;

	that.Elem = $(selector || document);
	that.Keys = {
		'backSpace': 8,
		'tab': 9,
		'enter': 13,
		'shift': 16,
		'ctrl': 17,
		'alt': 18,
		'pause': 19,
		'capsLock': 20,
		'esc': 27,
		'space': 32,
		'pageUp': 33,
		'pageDown': 34,
		'end': 35,
		'home': 36,
		'arrowLeft': 37,
		'arrowUp': 38,
		'arrowRight': 39,
		'arrowDown': 40,
		'insert': 45,
		'delete': 46,
		'0': 48,
		'1': 49,
		'2': 50,
		'3': 51,
		'4': 52,
		'5': 53,
		'6': 54,
		'7': 55,
		'8': 56,
		'9': 57,
		'a': 65,
		'b': 66,
		'c': 67,
		'd': 68,
		'e': 69,
		'f': 70,
		'g': 71,
		'h': 72,
		'i': 73,
		'j': 74,
		'k': 75,
		'l': 76,
		'm': 77,
		'n': 78,
		'o': 79,
		'p': 80,
		'q': 81,
		'r': 82,
		's': 83,
		't': 84,
		'u': 85,
		'v': 86,
		'w': 87,
		'x': 88,
		'y': 89,
		'z': 90,
		'winLeft': 91,
		'winRight': 92,
		'apps': 93,
		'num0': 96,
		'num1': 97,
		'num2': 98,
		'num3': 99,
		'num4': 100,
		'num5': 101,
		'num6': 102,
		'num7': 103,
		'num8': 104,
		'num9': 105,
		'num*': 106,
		'num+': 107,
		'num-': 109,
		'num,': 110,
		'num/': 111,
		'f1': 112,
		'f2': 113,
		'f3': 114,
		'f4': 115,
		'f5': 116,
		'f6': 117,
		'f7': 118,
		'f8': 119,
		'f9': 120,
		'f10': 121,
		'f11': 122,
		'f12': 123,
		'numLock': 144,
		'scrollLock': 145,
		'printScreen': 154,
		'meta': 157,
		'=': 187,
		',': 188,
		'-': 189,
		'.': 190,
		'/': 191,
		'~': 192,
		'[': 219,
		'\\': 220,
		']': 221,
		"'": 222
	};

	that._pressed = [];
	that._pressedPrev = [];
	that._events = [];
	that._deferred = [];

	$(document).on('keydown', that.Elem, function (e) {
		var target = $(e.target);
		if (!target.is(that.Elem)) return;

		if (that._pressed.indexOf(e.keyCode) < 0)
			that._pressed.push(e.keyCode);

		var i = 0, j = 0, c = 0;

		while (i < that._events.length) {
			j = 0;
			c = 0;

			while (j < that._pressed.length) {
				if (that._events[i].code.indexOf(that._pressed[j]) >= 0)
					c++;

				j++;
			}

			if (c == that._events[i].code.length && (!that._events[i].one || (that._events[i].one && that._pressed.diff(that._pressedPrev).length != 0))) {
				that._events[i].eventDown = e;

				if (!that._events[i].up) that._events[i].callback(that._events[i]);
				else that._deferred.push(that._events[i]);
			}

			i++;
		}

		i = 0;
		that._pressedPrev = [];

		while (i < that._pressed.length) {
			that._pressedPrev.push(that._pressed[i]);

			i++;
		}
	});

	$(document).on('keyup', that.Elem, function (e) {
		var i = 0, j = 0, c = 0;

		while (i < that._deferred.length) {
			j = 0;
			c = 0;

			while (j < that._pressed.length) {
				if (that._deferred[i].code.indexOf(that._pressed[j]) >= 0)
					c++;

				j++;
			}

			if (c == that._deferred[i].code.length && that._deferred[i].up) {
				that._deferred[i].eventUp = e;

				that._deferred[i].callback(that._deferred[i]);
			}

			that._deferred.splice(i, 1);

			i++;
		}

		i = 0;

		while (i < that._pressed.length) {
			if (that._pressed[i] == e.keyCode)
				that._pressed.splice(i, 1);

			i++;
		}

		that._pressedPrev = [];
	});

	/**
	 * @param {string|string[]} combination
	 *
	 * @return {string|string[]}
	 *
	 * @private
	 */
	that._keys = function (combination) {
		return typeof(combination) == 'string' ? combination.split('+') : combination;
	};

	/**
	 * @param {string|string[]} combination
	 *
	 * @return {int[]}
	 *
	 * @private
	 */
	that._code = function (combination) {
		combination = that._keys(combination);

		var i = 0, key = '', keys = [];

		while (i < combination.length) {
			key = combination[i].toLowerCase();

			if (that.Keys[key] != undefined)
				keys.push(that.Keys[key]);

			i++;
		}

		return keys;
	};

	/**
	 * @param {string|string[]} combination
	 * @param {Function} callback
	 */
	that.Up = function (combination, callback) {
		that._events.push({
			up: true,
			keys: that._keys(combination),
			code: that._code(combination),
			callback: callback
		});
	};

	/**
	 * @param {string|string[]} combination
	 * @param {Function} callback
	 */
	that.UpOne = function (combination, callback) {
		that._events.push({
			up: true,
			keys: that._keys(combination),
			code: that._code(combination),
			callback: callback,
			one: true
		});
	};

	/**
	 * @param {string|string[]} combination
	 * @param {Function} callback
	 */
	that.Down = function (combination, callback) {
		that._events.push({
			up: false,
			keys: that._keys(combination),
			code: that._code(combination),
			callback: callback
		});
	};

	/**
	 * @param {string|string[]} combination
	 * @param {Function} callback
	 */
	that.DownOne = function (combination, callback) {
		that._events.push({
			up: false,
			keys: that._keys(combination),
			code: that._code(combination),
			callback: callback,
			one: true
		});
	};

	/**
	 * @param {string|string[]} combination
	 * @param {string} selector
	 * @param {Function=} callback
	 */
	that.Shortcut = function (combination, selector, callback) {
		that.UpOne(combination, function (e) {
			var action = $(selector), ok = true;

			if (action.length == 0) return;

			if (callback instanceof Function) {
				var result = callback(e, action);

				ok = result === null || result;
			}

			if (ok) action[0].click();
		});
	};

	/**
	 * @param {string|string[]} combination
	 * @param {string} url
	 * @param {Function=} callback
	 */
	that.Navigate = function (combination, url, callback) {
		that.UpOne(combination, function (e) {
			var ok = true;

			if (callback instanceof Function) {
				var result = callback(e, url);

				ok = result === null || result;
			}

			if (ok) window.location = url;
		});
	};
};