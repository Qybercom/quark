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
		var drag = opt.drag,
			setup = Quark.Extend(opt, {
				preventDefault: true
			});

		setup.drag = function (e) {
			e.target.css({
				left: e.position.x + 'px',
				top: e.position.y + 'px'
			});

			if (drag instanceof Function) drag(e);
		};

		that.Elem.each(function () {
			(new Quark.IO.Mouse($(this))).Drag(setup);
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
			preventDefault: true,

			axis: {
				x: true,
				y: true
			},

			directions: {
				nw: def, n: def, ne: def,
				w: def,            e: def,
				sw: def, s: def, se: def
			},

			min: {
				width: 0,
				height: 0
			}
		});

		that.Elem.each(function () {
			var target = $(this),
				parent = null,
				handle = new Quark.IO.Mouse(opt.handle),
				dimension = {x: 0, y: 0},
				startDimension = {x: 0, y: 0},
				startTarget = {},

				width = 0,
				height = 0,
				left = 0,
				top = 0;

			handle.Drag({
				preventDefault: opt.preventDefault,
				handle: opt.handle,

				start: function (e) {
					startTarget = (opt.handle ? $(e.target).parent(target) : $(e.target));
					parent = startTarget.parent().position();

					startDimension.x = startTarget.width();
					startDimension.y = startTarget.height();

					if (opt.start instanceof Function) opt.start(e);
				},
				drag: function (e) {
					e.scroll = {
						x: startTarget.parent().scrollLeft(),
						y: startTarget.parent().scrollTop()
					};

					if (direction.e)
						width = startDimension.x + e.delta.x;

					if (direction.w) {
						width = startDimension.x - e.delta.x;
						left = (width <= opt.min.width ? 0 : e.current.x - parent.left) + e.scroll.x;
					}

					if (direction.s)
						height = startDimension.y + e.delta.y;

					if (direction.n) {
						height = startDimension.y - e.delta.y;
						top = (height <= opt.min.height ? 0 : e.current.y - parent.top) + e.scroll.y;
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