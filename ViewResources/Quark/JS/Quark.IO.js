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

			if (opt.handle != false && !target.is(opt.handle)) return true;
			if (target.is(opt.cancel)) return false;
			if (!target.is(selector))
				target = target.parent(selector);

			position = target.position();
			frame = {
				target: target,
				current: {
					x: e.pageX,
					y: e.pageY
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

			if (!opt.preventDefault) return true;

			e.stopPropagation();
			return false;
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
	e.data.drag({
		target: e.data.frame.target,
		point: e.data.frame.point,
		current: {
			x: e.pageX,
			y: e.pageY
		},
		position: {
			x: e.pageX - e.data.frame.point.x,
			y: e.pageY - e.data.frame.point.y
		},
		delta: {
			x: e.pageX - e.data.frame.current.x,
			y: e.pageY - e.data.frame.current.y
		}
	});
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
	that.Keys = [];

	/**
	 * @param combination
	 */
	that.Up = function (combination, callback) {

	};

	/**
	 * @param combination
	 */
	that.Down = function (combination, callback) {
		that.Elem.on('keydown', function (e) {
			console.log(e);
		});
	};
};