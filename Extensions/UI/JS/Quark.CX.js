/**
 * JS part of SaaS PHP framework
 *
 * @type {Quark}
 */
var Quark = Quark || {};

/**
 * Quark.CX namespace
 */
Quark.CX = {};

/**
 * @class Quark.CX.Mouse
 *
 * @param selector
 * @constructor
 */
Quark.CX.Mouse = function (selector) {
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

		var e, target, frame = {
			pageX: 0,
			pageY: 0,
			setup: opt
		};

		$(document).on('mousedown', that.Elem, function (e) {
			target = $(e.target);

			if (!that.Elem.is(target)) return true;

			if (opt.handle != false && !target.is(opt.handle)) return true;
			if (target.is(opt.cancel)) return false;

			frame.pageX = e.pageX;
			frame.pageY = e.pageY;
			frame.target = target;

			if (opt.start instanceof Function) opt.start(frame);

			$(document).on('mousemove', frame, opt.drag);

			if (opt.preventDefault) {
				e.stopPropagation();
				return false;
			}
		});

		$(document).on('mouseup', function (e) {
			$(document).off('mousemove', opt.drag);

			if (opt.stop instanceof Function) {
				frame.pageX = e.pageX;
				frame.pageY = e.pageY;

				opt.stop(frame);
			}
		});
	}
};

/**
 * @class Quark.CX.Keyboard
 *
 * @param selector
 * @constructor
 */
Quark.CX.Keyboard = function (selector) {
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