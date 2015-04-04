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

	/**
	 * @type Function
	 */
	that.click = opt.click || function () {};
	that.mouseover = opt.mouseover || function () {};
	that.mousemove = opt.mousemove || function () {};
	that.mouseout = opt.mouseout || function () {};

    $(selector).each(function () {
        /**
         * Google Maps settings
         */
        that.Settings = $.extend(true, {}, opt, {
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
	 */
	that.Event = function (name) {
		var i = 0;
		while (i < that._maps.length) {
			GoogleMap.On(that._maps[i], name, that[name]);
			i++;
		}
	};

	that.Child = function (child) {
		var i = 0;
		while (i < that._maps.length) {
			child.Render(that._maps[i]);
			i++;
		}
	};

	that.Event('click');
	that.Event('mouseover');
	that.Event('mousemove');
	that.Event('mouseout');
};

/**
 * @param position
 * @param alt
 *
 * @return google.maps.LatLng
 */
GoogleMap.Point = function (position, alt) {
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
 * Map types
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
	google.maps.event.addListener(obj, name, callback);
};

/**
 * @param map
 * @param opt
 *
 * @constructor
 */
GoogleMap.Object = function (map, opt) {
	this._object = {};
	this._opt = opt || {};

	/**
	 * @type Function
	 */
	this.ready = opt.ready || function () {};
	this.click = opt.click || function () {};
	this.mouseover = opt.mouseover || function () {};
	this.mousemove = opt.mousemove || function () {};
	this.mouseout = opt.mouseout || function () {};
	this.dragstart = opt.dragstart || function () {};
	this.drag = opt.drag || function () {};
	this.dragend = opt.dragend || function () {};

	this.Init = function () { };
	this.Render = function (map) { };
	this.Set = this._object.setOptions;

	this.Init();
	GoogleMap.On(this._object, 'domready', this.ready);
	this.Render(map);
};

/**
 * @constructor
 */
GoogleMap.Marker = function () {
	var that = this;

	this.Init = function () {
		that._object = new google.maps.Marker({
			position: GoogleMap.Point(that._opt.position),
			icon: that._opt.icon,
			visible: false
		});
	};

	this.Render = function (map) {
		that._object.setMap(map);
	};
};
GoogleMap.Marker.prototype = GoogleMap.Object;

/**
 * @param marker
 * @param opt
 *
 * @constructor
 */
GoogleMap.Tooltip = function (marker, opt) {
	opt = opt || {};

	var that = this;

	/**
	 * @type {boolean}
	 */
	that.custom = opt.custom || false;

	/**
	 * @type GoogleMap.Marker
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
		else that.element.outer.html(html);
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
        that.element.inner = $('#' + that._id);
		that.element.outer = that.element.inner.parent().parent().parent().parent();
		that.element._copy = that.element.outer.html();

        opt.content = '<div id="' + that._id + '" class="quark-map-tooltip">' + opt.content + '</div>';

        if (that.custom) that.element.outer.html(opt.content);
        else that.element.inner.html(opt.content);

        if (that.ready instanceof Function) that.ready(that);
	});
};

/**
 * @param map
 * @param opt
 *
 * @constructor
 */
GoogleMap.Route = function (map, opt) {
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

GoogleMap.Route.__key = 'Routes';

/**
 * @param map
 * @param opt
 *
 * @constructor
 */
GoogleMap.Polygon = function (map, opt) {
    opt = opt || {};
    var that = this;

    that._polygon = null;

    /**
     * Redraw the polygon on any change
     */
    that.redraw = opt.redraw || true;

    /**
     * @type Number
     */
    that.area = 0.0;

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
    that.style.fillColor = that.style.fillColor || 'lime';
    that.style.fillOpacity = that.style.fillOpacity || 0.3;

    /**
     * @param position
     */
    that.AddPoint = function (position) {
        that.points.push(Map.Point(position));

        if (that.redraw)
            that.Render();

        that.area = that.CalculateArea();
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

        that.area = that.CalculateArea();
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

        that.area = that.CalculateArea();

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

        that.area = that.CalculateArea();

        return that.points;
    };

    /**
     * Calculating area of the polygon
     */
    that.CalculateArea = function () {
        try {
            return google.maps.geometry.spherical.computeArea(that.points);
        }
        catch (e) {
            return 0.0;
        }
    };

    /**
     * Rendering the route
     */
    that.Render = function () {
        that._polygon = new google.maps.Polygon({
            paths: that.points,
            geodesic: that.style.geodesic,

            strokeColor: that.style.strokeColor,
            stokeOpacity: that.style.strokeOpacity,
            strokeWeight: that.style.strokeWeight,

            fillColor: that.style.fillColor,
            fillOpacity: that.style.fillOpacity
        });
    };

    /**
     * Hide the route
     */
    that.Hide = function () {
        that._polygon.setMap(null);
    };

    /**
     * Show the route
     */
    that.Show = function () {
        if (that._polygon == null) that.Render();

        that._polygon.setMap(map);
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

GoogleMap.Polygon.__key = 'Polygons';