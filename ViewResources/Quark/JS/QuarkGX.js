/**
 * JS part of SaaS PHP framework
 *
 * @type {Quark}
 */
var Quark = Quark || {};

/**
 * Quark.GX namespace
 */
Quark.GX = {
	_clock: null,
	_tasks: []
};

/**
 * @param value
 * @return {number}
 */
Quark.GX.Normalize = function (value) {
	return value == 0 ? 0 : (value / Math.abs(value));
};

/**
 * Graphical core for Quark framework. Manipulations with object defined by [selector]
 *
 * @param selector
 * @param opt
 *
 * @constructor
 */
Quark.GX.Model = function (selector, opt) {
	var that = this;

	//noinspection JSValidateJSDoc
	/**
	 * @type {jQuery}
	 */
	that.Elem = $(selector);

	that.meshes = {};

	that.position = {x:0, y:0, z:0};
	that.rotation = {x:0, y:0, z:0};
	that.dimension = {x:0, y:0, z:0};

	/**
	 * @param opt
	 */
	that.Render = function (opt) {
		opt = opt || {};
		opt.trail = opt.trail || false;

		opt.position = opt.position || {x:0,y:0,z:0};
		opt.rotation = opt.rotation || {x:0,y:0,z:0};
		opt.dimension = opt.dimension || {x:1,y:1,z:1};

		opt.position.quantity = opt.position.quantity || 'px';
		opt.rotation.quantity = opt.rotation.quantity || 'deg';

		opt.origin = opt.origin || {};
		opt.origin.transform = opt.origin.transform || {};
		opt.origin.transform.x = opt.origin.transform.x || '50%';
		opt.origin.transform.y = opt.origin.transform.y || '50%';
		opt.origin.transform.z = opt.origin.transform.z || '0';
		opt.origin.perspective = opt.origin.perspective || {};
		opt.origin.perspective.x = opt.origin.perspective.x || '50%';
		opt.origin.perspective.y = opt.origin.perspective.y || '50%';

		that.position = that._prop(opt, 'position');
		that.rotation = that._prop(opt, 'rotation');
		that.dimension = that._prop(opt, 'dimension');

		console.log(opt.origin.transform);

		that.Elem.css({
			'position': 'absolute',
			'-webkit-transform-style': 'preserve-3d',
			'-webkit-transform-origin': opt.origin.transform.x + ' ' + opt.origin.transform.y + ' ' + opt.origin.transform.z,
			'-webkit-perspective-origin': opt.origin.perspective.x + ' ' + opt.origin.perspective.y,
			'-webkit-transform': ''
				//+ 'scaleX(' + that.dimension.x + ') '
				//+ 'scaleY(' + that.dimension.y + ') '
				//+ 'scaleZ(' + that.dimension.z + ') '

				+ 'translate3d('
				+ that.position.x + 'px, '
				+ that.position.y + 'px, '
				+ that.position.z + 'px'
				+ ') '

				+ 'rotateX(' + that.rotation.x + opt.rotation.quantity + ') '
				+ 'rotateY(' + that.rotation.y + opt.rotation.quantity + ') '
				+ 'rotateZ(' + that.rotation.z + opt.rotation.quantity + ')'
		});
	};

	/**
	 * @param opt
	 * @param key
	 *
	 * @return {{x:number, y:number, z:number}}
	 * @private
	 */
	that._prop = function (opt, key) {
		return {
			x: parseFloat((opt.trail ? that[key].x : 0) + parseFloat(opt[key].x || 0)),
			y: parseFloat((opt.trail ? that[key].y : 0) + parseFloat(opt[key].y || 0)),
			z: parseFloat((opt.trail ? that[key].z : 0) + parseFloat(opt[key].z || 0))
		};
	};

	/**
	 * @param selector
	 * @param opt
	 *
	 * @return {Quark.GX.Model}
	 *
	 * @constructor
	 */
	that.Mesh = function (selector, opt) {
		if (that.meshes[selector] == undefined)
			that.meshes[selector] = new Quark.GX.Model(that.Elem.find(selector));

		opt = opt || {};
		opt.namespace = opt.namespace || false;

		return that.meshes[selector].Render(opt);
	};

	/**
	 * @param meshes
	 *
	 * @return {Quark.GX.Model}
	 *
	 * @constructor
	 */
	that.Meshes = function (meshes) {
		for (selector in meshes)
			if (Object.prototype.hasOwnProperty.call(meshes, selector)) that.Mesh(selector, meshes[selector]);

		return that;
	};

	if (opt != undefined)
		that.Render(opt);
};

/**
 * Scene class
 *
 * @note Quark.GX need specific simple DOM structure
 * <div id="[viewport_selector]">
 * 		<div class="quark-scene">
 * 		...
 * 		</div>
 * </div>
 *
 * @param viewport
 * @param opt
 *
 * @constructor
 */
Quark.GX.Scene = function (viewport, opt) {
	opt = opt || {};
	opt.children = opt.children || true;

	var that = this;

	that._viewport = $(viewport);
	that._scene = new Quark.GX.Model(that._viewport.find('.quark-scene'));

	that._viewport.css({
		'position': 'absolute',
		'left': '0px',
		'top': '0px',

		'width': '100%',
		'height': '100%',

		'-webkit-transform-style': 'preserve-3d',
		'-webkit-transform-origin': '50% 50%',
		'-webkit-perspective-origin': '50% 50% 0',
		'-webkit-perspective': '700px',

		'overflow': 'hidden'
	});

	that._scene.Elem.css({
		width: '100%',
		height: '100%'
	});

	/**
	 * Put camera to specified point
	 *
	 * @param point
	 */
	that.Camera = function (point) {
		that._scene.Render(point);

		// TODO: camera stuff
		/*that._scene.Render({
			trail: point.trail,
			origin: {
				transform: {
					x: (point.position ? (point.position.z || 0) : 0) + 'px',
					y: (point.position ? (point.position.x || 0) : 0) + 'px'
				}
			},
			position: point.position,
			rotation: point.rotation
		});*/
	};

	that.Light = function (point) {
		that._scene.Elem.css({
			'-webkit-filter': 'Light(enabled)'
		});

		that._scene.Elem[0].filters[0].addPoint(50, 50, 140, 255, 0, 0, 100);
	};

	if (opt.children)
		that._children = new Quark.GX.Model(that._scene.Elem.children(), {
			position: {x: 0, y: 0, z: 0}
		});
};

Quark.GX.Light = function (opt) {

};