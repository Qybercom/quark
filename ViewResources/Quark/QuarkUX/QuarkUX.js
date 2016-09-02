/**
 * JS part of SaaS PHP framework
 *
 * @type {Quark}
 */
var Quark = Quark || {};

/**
 * @param {string} selector
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
				preventDefault: true,
				defaultCss: true,
				change: {
					x: 'left',
					y: 'top'
				},
				axis: {
					x: true,
					y: true
				}
			});

		setup.drag = function (e) {
			var allow = true;
			if (drag instanceof Function) {
				var a = drag(e);
				allow = a == undefined ? true : a;
			}

			if (!allow) return;

			if (allow && opt.defaultCss && opt.axis.x) e.target.css(opt.change.x, e.position.x + 'px');
			if (allow && opt.defaultCss && opt.axis.y) e.target.css(opt.change.y, e.position.y + 'px');
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
 * @param {string} selector
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
 * @param {string} selector
 * @param {Function} submit
 * @param {Function} type
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

/**
 * @type {Quark.UX._commandHistoryListener[]}
 * @private
 */
Quark.UX._commandHistoryListeners = [];

/**
 * @type {Quark.UX._commandHistoryListener[]}
 * @private
 */
Quark.UX._commandTimer = setInterval(function () {
    var i = 0;

    while (i < Quark.UX._commandHistoryListeners.length) {
        if (Quark.UX._commandHistoryListeners[i] instanceof Quark.UX._commandHistoryListener)
            Quark.UX._commandHistoryListeners[i].Check();

        i++;
    }
}, 50);

/**
 * Attention!
 * Scrollable element MUST HAVE `position: absolute`. Take at mind this fact and create
 * your UI corresponding to it
 *
 * http://stackoverflow.com/a/1877007/2097055
 *
 * @param {string} selector
 * @param {Function} change
 * @param {boolean} [scroll=true]
 *
 * @private
 * @constructor
 */
Quark.UX._commandHistoryListener = function (selector, change, scroll) {
    scroll = scroll == undefined ? true : scroll;

    var that = this;

    that.height = 0;
    that.heightPrevious = 0;

    that.Check = function () {
        that.height = $(selector).prop('scrollHeight') || 0;

        if (that.height == 0 || that.height == that.heightPrevious) return;

        if (change instanceof Function)
            change(that.height, that.heightPrevious);

        if (scroll)
            $(selector).animate({ scrollTop: that.height }, 'fast');

        that.heightPrevious = that.height;
    };
};

/**
 * @param {string} selector
 * @param {Function} [change=]
 * @param {boolean} [scroll=true]
 */
Quark.UX.CommandHistory = function (selector, change, scroll) {
    Quark.UX._commandHistoryListeners.push(new Quark.UX._commandHistoryListener(selector, change, scroll));
};

/**
 * http://stackoverflow.com/a/14645827/2097055
 */
(function (old) {
	$.fn.attr = function() {
		if (arguments.length !== 0)
			return old.apply(this, arguments);

		if (this.length === 0) return null;

		var out = [];

		$.each(this[0].attributes, function () {
			if (!this.specified) return;

			out.push({
				name: this.name,
				value: this.value
			});
      });

      return out;
	};
})($.fn.attr);

$.fn.selectOption = function (value) {
	if (this.length === 0) return false;

	var regex = new RegExp('<option(.*?)value="' + value + '"(.*?)>', 'gi');

	this.each(function () {
		var html = $(this).html();

		$(this).html(html
			.replace(/<option(.*?)(selected|selected="selected")>/gi, '<option$1>')
			.replace(regex, '<option$1value="' + value + '"$2 selected="selected">')
		);
	});

	return true;
};