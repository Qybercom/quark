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

			preventDefault: true
		});

		var e, target, position = {}, frame = {};

		$(document).on('mousedown', selector, function (e) {
			target = $(e.target);

			if (target.is(opt.cancel)) return true;
			if (opt.handle != false && !target.is(opt.handle)) return true;
			if (!target.is(selector))
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
 * @constructor
 */
Quark.IO.Keyboard = function (selector) {
	var that = this;

	that.Elem = $(selector);
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
		'ins': 45,
		'del': 46,
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
		'scrLock': 145,
		'prtScr': 154,
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
	that._events = [];

	$(document).on('keydown', that.Elem, function (e) {
		if (that._pressed.indexOf(e.keyCode) < 0)
			that._pressed.push(e.keyCode);

		var i = 0, j = 0, c = 0;

		while (i < that._events.length) {
			j = 0;
			c = 0;

			while (j < that._pressed.length) {
				if (that._events[i].combination.indexOf(that._pressed[j]) >= 0)
					c++;

				j++;
			}

			if (c == that._events[i].combination.length)
				that._events[i].callback(that._events[i]);

			i++;
		}
	});

	$(document).on('keyup', that.Elem, function (e) {
		var i = 0;

		while (i < that._pressed.length) {
			if (that._pressed[i] == e.keyCode)
				that._pressed.splice(i, 1);

			i++;
		}
	});

	/**
	 * @param {Array} combination
	 * @return {Array}
	 *
	 * @private
	 */
	that._combination = function (combination) {
		var i = 0, keys = [];

		while (i < combination.length) {
			if (that.Keys[combination[i]] != undefined)
				keys.push(that.Keys[combination[i]]);

			i++;
		}

		return keys;
	};

	/**
	 * @param combination
	 * @param {Function} callback
	 */
	that.Up = function (combination, callback) {
		var keys = that._combination(combination);
		console.log(keys);
	};

	/**
	 * @param combination
	 * @param {Function} callback
	 */
	that.Down = function (combination, callback) {
		that._events.push({
			combination: that._combination(combination),
			callback: callback
		});
	};
};