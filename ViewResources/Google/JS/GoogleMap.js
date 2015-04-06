/**
 * Google Maps abstraction layer
 *
 * @version 1.4.0
 * @author Alex Furnica
 *
 * @param selector
 * @param opt
 *
 * @constructor
 */
var GoogleMap = function (selector, opt) {
	var that = this;

	that._maps = [];

    $(selector).each(function () {
        /**
         * Google Maps settings
         */
        that.Settings = GoogleMap._extend(opt, {
            disableDefaultUI: true,
            disableDoubleClickZoom: true,
            scrollwheel: true,
            mapTypeId: GoogleMap.Type.Roadmap,
            center: GoogleMap.Point(opt.center || {
                lat: 0,
                lng: 0
            }),

            zoom: opt.zoom || 16,
            zoomControl: false,
            zoomControlOptions: {
                style: google.maps.ZoomControlStyle.DEFAULT
            },

            scaleControl: false,
            streetViewControl: false,
            overviewMapControl: false
        });

        that._maps.push(new google.maps.Map($(this)[0], that.Settings));
    });

	/**
	 * @param option
	 * @param value
	 */
	that.Set = function (option, value) {
		that.Settings[option] = value;

		var i = 0;
		while (i < that._maps.length) {
			that._maps[i].setOptions(that.Settings);
			i++;
		}
	};

	/**
	 * @param name
	 * @param callback
	 */
	that.On = function (name, callback) {
		var i = 0;
		while (i < that._maps.length) {
			GoogleMap.On(that._maps[i], name, callback);
			i++;
		}
	};

	that.Child = function (child) {
		var i = 0;
		while (i < that._maps.length) {
			child._init(that._maps[i]);
			i++;
		}
	};
};

/**
 * @param position
 * @param alt
 *
 * @return google.maps.LatLng
 */
GoogleMap.Point = function (position, alt) {
	if (position == undefined) return null;

	position = alt == undefined ? position : {
		lat: position,
		lng: alt
	};

	return new google.maps.LatLng(position.lat, position.lng);
};

/**
 * @param p1
 * @param p2
 *
 * @return Number
 */
GoogleMap.Distance = function (p1, p2) {
    return google.maps.geometry.spherical.computeDistanceBetween(new GoogleMap.Point(p1), new GoogleMap.Point(p2));
};

/**
 * GoogleMap types
 */
GoogleMap.Type = {
	Roadmap: google.maps.MapTypeId.ROADMAP
};

/**
 * @param  obj
 * @param name
 * @param callback
 */
GoogleMap.On = function (obj, name, callback) {
	google.maps.event.addListener(obj, name, function (e) {
		if (e != undefined) {
			e.position = {
				lat: e.latLng.lat(),
				lng: e.latLng.lng()
			};
			e.delta = GoogleMap.DeltaBetween(e.position, obj.___position || e.position);
		}

		callback(e);

		if (e != undefined)
			obj.___position = e.position;
	});
};

/**
 * @param  obj
 * @param name
 * @param  opt
 */
GoogleMap.Trigger = function (obj, name, opt) {
	google.maps.event.trigger(obj, name, opt);
};

/**
 * @param  obj
 * @param name
 * @param callback
 */
GoogleMap.Off = function (obj, name, callback) {
	google.maps.event.removeListener(obj, name, callback);
};

/**
 * @param obj
 * @param backbone
 */
GoogleMap._extend = function (obj, backbone) {
	return $.extend(true, {}, obj, backbone);
};

/**
 * @param width
 * @param angle
 *
 * @return {number}
 */
GoogleMap.Delta = function (width, angle) {
	return (width / (25400 * Math.PI * Math.cos(angle * (Math.PI / 180)))) * 360;
};

/**
 * @param a
 * @param b
 *
 * @return {{lat: number, lng: number}}
 */
GoogleMap.DeltaBetween = function (a, b) {
	return {
		lat: (a.lat || 0) - (b.lat || 0),
		lng: (a.lng || 0) - (b.lng || 0)
	};
};

/**
 * @param width
 * @param position
 *
 * @return {{
 * 		n: {lat: number, lng: number},
 * 		e: {lat: number, lng: number},
 * 		s: {lat: number, lng: number},
 * 		w: {lat: number, lng: number},
 * 		nw: {lat: number, lng: number},
 * 		ne: {lat: number, lng: number},
 * 		se: {lat: number, lng: number},
 * 		sw: {lat: number, lng: number}
 * 	}}
 */
GoogleMap.Edge = function (width, position) {
	width = width || 0;
	position = position || {};

	return {
		n: {lat: position.lat + GoogleMap.Delta(width,	0),	lng: position.lng -GoogleMap.Delta(0, 0)},
		e: {lat: position.lat + GoogleMap.Delta(0,		0),	lng: position.lng +GoogleMap.Delta(width, position.lat)},
		s: {lat: position.lat - GoogleMap.Delta(width,	0),	lng: position.lng +GoogleMap.Delta(0, 0)},
		w: {lat: position.lat - GoogleMap.Delta(0,		0),	lng: position.lng -GoogleMap.Delta(width, position.lat)},

		nw: {lat: position.lat + GoogleMap.Delta(width, 0),	lng: position.lng -GoogleMap.Delta(width, position.lat)},
		ne: {lat: position.lat + GoogleMap.Delta(width, 0),	lng: position.lng +GoogleMap.Delta(width, position.lat)},
		se: {lat: position.lat - GoogleMap.Delta(width, 0),	lng: position.lng +GoogleMap.Delta(width, position.lat)},
		sw: {lat: position.lat - GoogleMap.Delta(width, 0),	lng: position.lng -GoogleMap.Delta(width, position.lat)}
	};
};

/**
 * @prototype
 */
GoogleMap.Object = {
	_object: null,
	_opt: {},
	_map: null,
	_events: [],

	ready: function () { },

	Init: function () { },

	/**
	 * @param name
	 * @param callback
	 */
	On: function (name, callback) {
		var that = this;
		var _cb = function (e) {
			e.that = that;
			callback(e);
		};

		if (this._object == null) this._events.push({name:name, callback:_cb});
		else GoogleMap.On(this._object, name, _cb);
	},

	/**
	 * @param name
	 * @param opt
	 */
	Trigger: function (name, opt) {
		GoogleMap.Trigger(this._object, name, opt);
	},

	/**
	 * @param obj
	 */
	Child: function (obj) {
		obj._parent = this;
		obj._init(this._map);
	},

	/**
	 * @param map
	 * @private
	 */
	_init: function (map) {
		this._map = map;

		this.Init();
		this.Set = function (opt) {
			this._object.setOptions(opt);
		};
		this.On('domready', this.ready);

		var i = 0;
		while (i < this._events.length) {
			this.On(this._events[i].name, this._events[i].callback);
			i++;
		}
	}
};

/**
 * @param opt
 *
 * @constructor
 */
GoogleMap.Marker = function (opt) {
	var that = this;

	this.Init = function () {
		var obj = GoogleMap._extend(opt, {
			position: GoogleMap.Point(opt.position),
			map: that._map
		});

		that.position = opt.position;
		that._object = new google.maps.Marker(obj);
	};

	this.Show = function () {
		that.Set({visible:true});
	};

	this.Hide = function () {
		that.Set({visible:false});
	};
};
GoogleMap.Marker.prototype = GoogleMap.Object;

/**
 * @param opt
 *
 * @constructor
 */
GoogleMap.Tooltip = function (opt) {
	var that = this;

	that._id = 'tooltip' + Quark.GuID();
	that.custom = opt.custom || false;

	/**
	 * DOM elements of tooltip
	 */
	that.element = {
		inner: {},
		outer: {},
		_copy: {}
	};

	that.Init = function () {
		that._object = new google.maps.InfoWindow(GoogleMap._extend(opt, {
			content: '<div id="' + that._id + '">' + opt.content + '</div>'
		}));
	};

	/**
	 * Init & open InfoWindow
	 */
	that.Show = function () {
		that._object.open(that._parent._map, that._parent._object);
	};

	/**
	 * Close InfoWindow
	 */
	that.Hide = function () {
		that._object.close();
	};

	/**
	 * @param html
	 * @param custom
	 */
	that.Content = function (html, custom) {
		if (custom != undefined)
			that.custom = custom;

		if (!that.element.outer.length) return;

		if (!that.custom) that.element.inner.html(html);
		else that.element.outer.html(html);
	};

	/**
	 * @param css
	 */
	that.css = function (css) {
		that.element.outer.css(css);
	};

	that.ready = function () {
		if (!that.element.inner.length) {
			that.element.inner = $('#' + that._id);
			that.element.outer = that.element.inner.parent().parent().parent().parent();
			that.element._copy = that.element.outer.html();
		}

		that.Content('<div id="' + that._id + '" class="quark-map-tooltip">' + opt.content + '</div>', opt.custom);
	};
};
GoogleMap.Tooltip.prototype = GoogleMap.Object;

/**
 * @prototype
 */
GoogleMap.PointObject = GoogleMap._extend(GoogleMap.Object, {
	redraw: true,
	points: [],

	AddPoint: function (position) {
		this.points.push(GoogleMap.Point(position));

		this.Redraw();
	},

	MovePoint: function (oldPosition, newPosition) {
		var point = this.GetPoint(oldPosition);

		if (point == null) return;

		this.points[point].lat = newPosition.lat;
		this.points[point].lng = newPosition.lng;

		this.Redraw();
	},

	RemovePoint: function (position) {
		var point = this.GetPoint(position);

		if (point == null) return;

			this.points.splice(point, 1);

		this.Redraw();
	},

	GetPoint: function (position) {
		var i = 0;

		while (i < this.points.length) {
			if (this.points[i].lat == position.lat && this.points[i].lng == position.lng) return i;

			i++;
		}

		return null;
	},

	Move: function (delta) {
		delta = delta || {};

		var i = 0;

		while (i < this.points.length) {
			this.points[i].lat += delta.lat;
			this.points[i].lng += delta.lng;

			i++;
		}

		this.Redraw();
	},

	Redraw: function () {
		var show = this.redraw && this._object != null && this._object.getMap() != null;

		if (show) this.Set({map: null});
		this.Render();
		if (show) this.Set({map: this._parent._map});
	},

	Points: function () {
		var i = 0;
		var out = [];

		while (i < this.points.length) {
			out.push(GoogleMap.Point(this.points[i]));

			i++;
		}

		return out;
	},

	/**
	 * Show the object
	 */
	Show: function () {
		this.Set({map: this._parent._map});
	},

	/**
	 * Hide the object
	 */
	Hide: function () {
		this.Set({map: null});
	}
});

/**
 * @param opt
 *
 * @constructor
 */
GoogleMap.Route = function (opt) {
	var that = this;

	/**
	 * Rendering the route
	 */
	that.Render = function (opt) {
		that._opt = GoogleMap._extend(opt, {
			path: that.Points(),
			geodesic: true,
			style: {
				strokeColor: 'black',
				strokeOpacity: 1.0,
				strokeWeight: 1
			}
		});

		that._object = new google.maps.Polyline(that._opt);
	};

	/**
	 * @param map
	 */
	that.Init = function (map) {
		that.points = opt.points || [];
		that.Render(opt);
	};
};
GoogleMap.Route.prototype = GoogleMap.PointObject;

/**
 * @param opt
 *
 * @constructor
 */
GoogleMap.Circle = function (opt) {
	var that= this;

	that.width = opt.width || ((opt.radius * 2) / 1000);

	/**
	 * Rendering the circle
	 */
	that.Render = function (opt) {
		opt = opt || {};

		that._opt = GoogleMap._extend(opt, {
			center: GoogleMap.Point(that.points[0] || opt.center),
			radius: (that.width / 2) * 1000
		});

		that._object = new google.maps.Circle(that._opt);

		if (that.points.length == 0)
			that.points.push(opt.center);
	};

	/**
	 * @param map
	 */
	that.Init = function (map) {
		that.points = opt.points || [];
		that.Render(opt);
	};

	/**
	 * @param width
	 */
	that.Width = function (width) {
		that.width = width;
		that.Redraw();
	};
};
GoogleMap.Circle.prototype = GoogleMap.PointObject;

/**
 * @param opt
 *
 * @constructor
 */
GoogleMap.Polygon = function (opt) {
    var that = this;

	that.width = opt.width || ((opt.radius * 2) / 1000);

    /**
     * Rendering the polygon
     */
    that.Render = function (opt) {
		that._opt = GoogleMap._extend(opt, {
			paths: that.Points(),
			geodesic: true
		});

		that._object =  new google.maps.Polygon(that._opt);
    };

	/**
	 * @param map
	 */
	that.Init = function (map) {
		if (opt.center && opt.width)
			that.Square(opt.width, opt.center);

		that.points = opt.points || that.points;
		that.Render(opt);
	};

	/**
	 * @param width
	 * @param center
	 *
	 * @constructor
	 */
	that.Square = function (width, center) {
		that.width = width;
		that.center = center;

		var edge = GoogleMap.Edge(that.width, that.center);

		that.points = [];
		that.points.push(edge.ne);
		that.points.push(edge.nw);
		that.points.push(edge.sw);
		that.points.push(edge.se);

		that.Redraw();
	};
};
GoogleMap.Polygon.prototype = GoogleMap.PointObject;