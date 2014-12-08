/**
 * JS part of SaaS PHP framework
 *
 * @type {Quark}
 */
var Quark = Quark || {};

/**
 * @param selector
 * @constructor
 */
Quark.UX = function (selector) {
	var that = this;

	that.Elem = $(selector);

	/**
	 * @param value
	 */
	that.Rotate = function (value) {
		that.Elem.css('transform', 'rotate(' + value + 'deg)');
	};

	/**
	 * @param opt
	 */
	that.Drag = function (opt) {
		opt = Quark.Extend(opt, {
			cancel: false,
			handle: false,
			preventDefault: true,

			start: false,
			drag: false,
			stop: false,

			axis: {
				x: true,
				y: true
			}
		});

		that.Elem.each(function () {
			var target = new Quark.CX.Mouse($(this)),
				position = {x: 0, y: 0},
				startPosition = {x: 0, y: 0},
				startTarget = {};

			target.Drag({
				cancel: opt.cancel,
				handle: opt.handle,
				preventDefault: opt.preventDefault || true,

				start: function (e) {
					startTarget = (opt.handle ? $(e.target).parent(target) : $(e.target));
					startPosition = startTarget.position();

					if (opt.start instanceof Function) opt.start(e);
				},
				drag: function (e) {
					position.x = (opt.axis.x ? e.pageX - e.data.pageX : 0) + (startPosition.x || startPosition.left);
					position.y = (opt.axis.y ? e.pageY - e.data.pageY : 0) + (startPosition.y || startPosition.top);

					startTarget.css({
						left: position.x + 'px',
						top: position.y + 'px'
					});

					if (opt.drag instanceof Function) opt.drag(e, position, startTarget);
				},
				stop: opt.stop
			});
		});
	};

	/**
	 * @param opt
	 */
	that.Resize = function (opt) {
		var def = opt.directions == undefined,
			direction = {
				w: opt.directions.nw || opt.directions.w || opt.directions.sw,
				n: opt.directions.nw || opt.directions.n || opt.directions.ne,
				e: opt.directions.ne || opt.directions.e || opt.directions.se,
				s: opt.directions.se || opt.directions.s || opt.directions.sw
			};

		opt = Quark.Extend(opt, {
			handle: false,
			preventDefault: true,

			start: false,
			drag: false,
			stop: false,

			axis: {
				x: true,
				y: true
			},

			directions: {
				nw: def,  n: def, ne: def,
				w: def, 			 e: def,
				sw: def,  s: def, se: def
			},

			min: {
				width: 0,
				height: 0
			}
		});

		that.Elem.each(function () {
			var target = $(this),
				parent = null,
				handle = new Quark.CX.Mouse(opt.handle),
				dimension = {x: 0, y: 0},
				offset = {x: 0, y: 0},
				startDimension = {x: 0, y: 0},
				startTarget = {},

				width = 0,
				height = 0,
				left = 0,
				top = 0;

			handle.Drag({
				preventDefault: opt.preventDefault || true,
				handle: handle.Elem,

				start: function (e) {
					startTarget = (opt.handle ? $(e.target).parent(target) : $(e.target));
					parent = startTarget.parent().position();

					startDimension.x = startTarget.width();
					startDimension.y = startTarget.height();

					if (opt.start instanceof Function) opt.start(e);
				},
				drag: function (e) {
					offset.x = (opt.axis.x ? e.pageX - e.data.pageX : 0);
					offset.y = (opt.axis.y ? e.pageY - e.data.pageY : 0);

					if (direction.e)
						width = startDimension.x + offset.x;

					if (direction.w) {
						width = startDimension.x - offset.x;
						left = width <= opt.min.width ? 0 : e.pageX - parent.left;
					}

					if (direction.s)
						height = startDimension.y + offset.y;

					if (direction.n) {
						height = startDimension.y - offset.y;
						top = height <= opt.min.height ? 0 : e.pageY - parent.top;
					}

					if (width) startTarget.css('width', (width <= opt.min.width ? opt.min.width: width) + 'px');
					if (height) startTarget.css('height', (height <= opt.min.height ? opt.min.height : height) + 'px');
					if (left) startTarget.css('left', left + 'px');
					if (top) startTarget.css('top', top + 'px');

					if (opt.resize instanceof Function) opt.resize(e, dimension, startTarget);
				},
				stop: opt.stop
			});
		});
	}
};