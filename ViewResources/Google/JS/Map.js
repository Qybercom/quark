/**
 * Google Maps abstraction layer
 *
 * @version 1.3.0
 * @author Alex Furnica
 *
 * @param elem
 * @param opt
 *
 * @constructor
 */
var Map = function (elem, opt) {
	var that = this, map = $(elem);

	if (map.length == 0) return;

	/**
	 * @type Array
	 */
	that.Markers = [];
	that.Routes = [];

	/**
	 * @type Function
	 */
	that.click = opt.click || function () {};
	that.mouseover = opt.mouseover || function () {};
	that.mousemove = opt.mousemove || function () {};
	that.mouseout = opt.mouseout || function () {};

	/**
	 * Google Maps settings
	 */
	that.Settings = {
		disableDefaultUI: true,
		disableDoubleClickZoom: true,
		scrollwheel: false,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		center: Map.Point(opt.center || {
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
	};

	that._map = new google.maps.Map(map[0], that.Settings);

	/**
	 * @param position
	 */
	that.Center = function (position) {
		that.Settings.center = Map.Point(position);

		that._map.setOptions(that.Settings);
	};

	/**
	 * @param zoom
	 * @param scroll
	 */
	that.Zoom = function (zoom, scroll) {
		if (scroll != undefined)
			that.Settings.scrollwheel = scroll;

		that.Settings.zoom = zoom;

		that._map.setOptions(that.Settings);
	};

	/**
	 * @param opt
	 * @return Map.Marker
	 */
	that.Marker = function (opt) {
		that.Markers.push(new Map.Marker(that._map, opt));

		return that.Markers[that.Markers.length - 1];
	};

	/**
	 * @param opt
	 * @return Map.Route
	 */
	that.Route = function (opt) {
		that.Routes.push(new Map.Route(that._map, opt));

		return that.Routes[that.Routes.length - 1];
	};

	/**
	 * @param name
	 * @private
	 */
	that._event = function (name) {
		google.maps.event.addListener(that._map, name, function (e) {
			if (!(that[name] instanceof Function)) return;

			that[name]({
				map: that,
				position: {
					lat: e.latLng.lat(),
					lng: e.latLng.lng()
				}
			});
		});
	};

	/**
	 * @param component
	 * @param filter
	 * @return Array
	 */
	that.Find = function (component, filter) {
		if (component.__key != undefined)
			component = component.__key;

		if (that[component] == undefined || !(that[component] instanceof Array)) return [];

		if (filter == undefined)
			return that[component];

		var i = 0, output = [];

		while (i < that[component].length) {
			if (filter(that[component][i]))
				output.push(that[component][i]);

			i++;
		}

		return output;
	};

	that._event('click');
	that._event('mouseover');
	that._event('mousemove');
	that._event('mouseout');
};

/**
 * @param position
 * @return google.maps.LatLng
 */
Map.Point = function (position) {
	return new google.maps.LatLng(position.lat, position.lng);
};

/**
 * @param map
 * @param opt
 *
 * @constructor
 */
Map.Marker = function (map, opt) {
	opt = opt || {};

	var that = this;

	/**
	 * Data object
	 */
	that.data = opt.data || {};

	/**
	 * Flag of removing
	 */
	that.removed = false;

	/**
	 * @type Function
	 */
	that.click = opt.click || function () {};
	that.mouseover = opt.mouseover || function () {};
	that.mousemove = opt.mousemove || function () {};
	that.mouseout = opt.mouseout || function () {};
	that.dragstart = opt.dragstart || function () {};
	that.drag = opt.drag || function () {};
	that.dragend = opt.dragend || function () {};

	/**
	 * @type Array of Map.Tooltip
	 */
	that.Tooltips = [];

	that._map = map;
	that._marker = null;

	/**
	 * @type Object {
     *  lat: number,
     *  lng: number
     * }
	 */
	that.position = opt.position || {
		lat: 0,
		lng: 0
	};

	/**
	 * Rendering the marker
	 */
	that.Render = function () {
		that._marker = new google.maps.Marker({
			position: Map.Point(that.position),
			icon: opt.icon,
			map: that._map,
			visible: false
		});

		that._event('click');
		that._event('mouseover');
		that._event('mousemove');
		that._event('mouseout');
		that._event('dragstart');
		that._event('drag');
		that._event('dragend');
	};

	that._event = function (name) {
		google.maps.event.addListener(that._marker, name, function (e) {
			if (!(that[name] instanceof Function)) return;

			that[name](that);
		});
	};

	/**
	 * Mark marker as removed
	 */
	that.Remove = function () {
		that.removed = true;
		that.Hide();
	};

	/**
	 * Hide the marker
	 */
	that.Hide = function () {
		that._marker.setVisible(false);
	};

	/**
	 * Show the marker
	 */
	that.Show = function () {
		that._marker.setVisible(true);
	};

	/**
	 * @param flag
	 */
	that.Draggable = function (flag) {
		that._marker.setDraggable(flag);
	};

	/**
	 * @param icon
	 */
	that.Icon = function (icon) {
		that._marker.setIcon(icon);
	};

	/**
	 * @param position
	 */
	that.Position = function (position) {
		that._marker.setPosition(Map.Point(position));
	};

	/**
	 * @param opt
	 * @returns {*}
	 */
	that.Tooltip = function (opt) {
		that.Tooltips.push(new Map.Tooltip(that, opt));

		return that.Tooltips[that.Tooltips.length - 1];
	};

	that.Render();
};

Map.Marker.__key = 'Markers';

/**
 * @param marker
 * @param opt
 *
 * @constructor
 */
Map.Tooltip = function (marker, opt) {
	opt = opt || {};

	var that = this;

	/**
	 * @type {boolean}
	 */
	that.custom = opt.custom || false;

	/**
	 * @type Map.Marker
	 * @private
	 */
	that._marker = marker;

	/**
	 * @type Function
	 */
	that.ready = opt.ready;
	that.click = opt.click;

	/**
	 * DOM elements of tooltip
	 */
	that.element = {
		inner: {},
		outer: {},
		_copy: {}
	};

    that._id = 'tooltip' + Quark.GuID();

	/**
	 * @type {google.maps.InfoWindow}
	 * @private
	 */
	that._tooltip = new google.maps.InfoWindow({
		content: '<div id="' + that._id + '">' + opt.content + '</div>'
	});

    //that._element = $('#' + that._id);

	/**
	 * Init & open InfoWindow
	 */
	that.Open = function () {
		that._tooltip.open(that._marker._map, that._marker._marker);
	};

	/**
	 * Close InfoWindow
	 */
	that.Close = function () {
		that._tooltip.close();
	};

	/**
	 * @param html
	 */
	that.Content = function (html) {
		if (!that.element.outer.length) return;

		if (!that.custom) that.element.inner.html(html);
		else that.element.outer
			.html(html)
			.css({
				'margin-left': '50px',
				'margin-top': (-that.element.outer.height() + 60) + 'px'
			});
	};

	/**
	 * Reset all default styles of InfoWindow
	 */
	that.Reset = function () {
		that.custom = true;

		that.element.outer
			.empty()
			.css({
				padding: '0px',
				border: '0px',
				height: 'auto',
				width: 'auto'
			});
	};

	/**
	 * @param css
	 */
	that.css = function (css) {
		that.element.outer.css(css);
	};

	google.maps.event.addListener(that._tooltip, 'domready', function () {
        var elem = $('#' + that._id);
        that._element = elem.parent().parent().parent().parent();
        that._element.html('<div id="' + that._id + '" class="quark-map-tooltip">' + elem.html() + '</div>');

		//that.element.inner = $(this.k.contentNode);
		//that.element.outer = that.element.inner.parent().parent();
		//that.element._copy = that.element.outer.html();

		if (that.custom) {
			that.Reset();
			that.element.outer.html(opt.content);
		}

		if (that.ready instanceof Function) that.ready(that);
	});
};

/**
 * @param map
 * @param opt
 *
 * @constructor
 */
Map.Route = function (map, opt) {
	var that = this;

	that._line = null;

	/**
	 * Redraw the route on any change
	 */
	that.redraw = opt.redraw || true;

	/**
	 * @type Number
	 */
	that.length = 0.0;

	/**
	 * @type Array
	 */
	that.points = [];

	/**
	 * Route visual settings
	 */
	that.style = opt.style || {};
	that.style.geodesic = that.style.geodesic || true;
	that.style.strokeColor = that.style.strokeColor || 'black';
	that.style.strokeOpacity = that.style.strokeOpacity || 1.0;
	that.style.strokeWeight = that.style.strokeWeight || 1;

	/**
	 * @param position
	 */
	that.AddPoint = function (position) {
		that.points.push(Map.Point(position));

		if (that.redraw)
			that.Render();

		that.length = that.CalculateLength();
	};

	/**
	 * @param oldPosition
	 * @param newPosition
	 */
	that.MovePoint = function (oldPosition, newPosition) {
		var point = that.GetPoint(oldPosition);

		if (point == null) return;

		that.points[point].lat = newPosition.lat;
		that.points[point].lng = newPosition.lng;

		if (that.redraw)
			that.Render();

		that.length = that.CalculateLength();
	};

	/**
	 * @param position
	 */
	that.RemovePoint = function (position) {
		var point = that.GetPoint(position);

		if (point == null) return;

		that.points.splice(point, 1);

		if (that.redraw)
			that.Render();

		that.length = that.CalculateLength();

	};

	/**
	 * @param position
	 * @return Number|null
	 */
	that.GetPoint = function (position) {
		var i = 0;

		while (i < that.points.length) {
			if (that.points[i].lat == position.lat && that.points[i].lng == position.lng) return i;

			i++;
		}

		return null;
	};

	/**
	 * @param points
	 * @return Array
	 */
	that.Points = function (points) {
		if (!(points instanceof Array)) points = [];

		that.points = [];

		var i = 0;

		while (i < points.length) {
			that.points.push(Map.Point(points[i]));

			i++;
		}

		that.length = that.CalculateLength();

		return that.points;
	};

	/**
	 * Calculating length of he route
	 */
	that.CalculateLength = function () {
		try {
			return google.maps.geometry.spherical.computeLength(that.points);
		}
		catch (e) {
			return 0.0;
		}
	};

	/**
	 * Rendering the route
	 */
	that.Render = function () {
		that._line = new google.maps.Polyline({
			path: that.points,
			geodesic: that.style.geodesic,

			strokeColor: that.style.strokeColor,
			stokeOpacity: that.style.strokeOpacity,
			strokeWeight: that.style.strokeWeight
		});
	};

	/**
	 * Hide the route
	 */
	that.Hide = function () {
		that._line.setMap(null);
	};

	/**
	 * Show the route
	 */
	that.Show = function () {
		if (that._line == null) that.Render();

		that._line.setMap(map);
	};

	/**
	 * Reset the route
	 */
	that.Reset = function () {
		that.points = [];

		if (that.redraw)
			that.Render();
	};

	that.Points(opt.points);
	that.Render();
};

Map.Route.__key = 'Routes';