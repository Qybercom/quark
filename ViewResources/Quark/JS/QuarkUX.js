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
	};
};

/**
 * @param selector
 * @param {{n:Function,e:Function,s:Function,w:Function}} opt
 *
 * @constructor
 */
Quark.UX.KeyboardNavigation = function (selector, opt) {
	var that = this;

	const WASD = ['w','a','s','d'];

	that.KeyBoard = new Quark.IO.Keyboard(selector);
	that.Directions = opt || {};
		that.Directions.n = opt.n || false;
		that.Directions.e = opt.e || false;
		that.Directions.s = opt.s || false;
		that.Directions.w = opt.w || false;

	/**
	 * @param {String} n
	 * @param {String} e
	 * @param {String} s
	 * @param {String} w
	 */
	that.Navigator = function (n, e, s, w) {
		if (n instanceof Array && n.length == 4) {
			w = n[3];
			s = n[2];
			e = n[1];
			n = n[0];
		}

		that._dir('n', n);
		that._dir('e', e);
		that._dir('s', s);
		that._dir('w', w);
	};

	/**
	 * @param {String} dir
	 * @param {String} key
	 *
	 * @private
	 */
	that._dir = function (dir, key) {
		that.KeyBoard.Down([key], that.Directions[dir]);
	};

	that._dir('n', 'arrowUp');
	that._dir('e', 'arrowRight');
	that._dir('s', 'arrowDown');
	that._dir('w', 'arrowLeft');
};

/**
 * @param selector
 * @param submit
 * @param type
 */
Quark.UX.Command = function (selector, submit, type) {
    $(document).on('keydown', selector, function (e) {
        var text = $(this).val();

        if (e.keyCode != 13) {
            if (type instanceof Function) type(text);

            return;
        }

        if (submit instanceof Function)
            submit(text);

        $(this).val('');

        return false;
    });
};