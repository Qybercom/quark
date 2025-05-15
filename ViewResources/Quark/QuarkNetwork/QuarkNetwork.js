/**
 * JS part of SaaS PHP framework
 *
 * @type {Quark}
 */
Quark = Quark || {};

/**
 * Quark.Network namespace
 */
Quark.Network = {};

/**
 * @param {*} opt
 *
 * @constructor
 */
Quark.Network.Socket = function (opt) {
	opt = opt || {};

	var that = this,
		connected = false,
		reconnectAttempt = true,
		reconnectDisabled = false,
		reconnect = setInterval(function () {
			if (connected || reconnectDisabled) return;

			if (that.onReconnect instanceof Function)
				that.onReconnect();

			reconnectAttempt = true;
		}, opt.reconnect ? opt.reconnect : 50),
		heartbeat = setInterval(function () {
			if (connected) {
				return;
			}
			
			if (reconnectAttempt) {
				reconnectAttempt = false;
				
				that.Connect();
			}
		}, 50);

	that.Socket = null;

	that.url = opt.url || null;
	that.host = opt.host || null;
	that.port = opt.port || null;
	that.secure = opt.secure || false;
	that.path = opt.path || null;
	that.reconnect = opt.reconnect || false;
	that.onConnect = opt.onConnect || null;
	that.onMessage = opt.onMessage || null;
	that.onClose = opt.onClose || null;
	that.onError = opt.onError || null;
	that.onReconnect = opt.onReconnect || null;

	/**
	 * @returns {string}
	 */
	that.URL = function () {
		var url = opt.url;

		if (opt.host) {
			url = 'ws' + (opt.secure ? 's' : '') + '://' + opt.host;

			if (opt.port)
				url += ':' + opt.port;
		}

		if (opt.path)
			url += opt.path;

		return url;
	};

	/**
	 * @returns {boolean}
	 */
	that.Connect = function () {
		try {
			reconnectDisabled = false;

			that.Socket = new WebSocket(that.URL());

			that.Socket.onopen = function (e) {
				connected = true;

				if (that.onConnect instanceof Function)
					that.onConnect(e);
			};

			that.Socket.onmessage = function (e) {
				if (that.onMessage instanceof Function)
					that.onMessage(e);
			};
			
			that.Socket.onerror = function (e) {
				if (that.onError instanceof Function)
					opt.onError(e);
			};

			that.Socket.onclose = function (e) {
				connected = false;

				if (that.onClose instanceof Function)
					that.onClose(e);
			};

			return true;
		}
		catch (e) {
			if (opt.onError instanceof Function)
				opt.onError(e);

			return false;
		}
	};

	/**
	 * @return {boolean}
	 */
	that.Close = function () {
		if (that.Socket == null) return false;

		that.Socket.close();
		that.Socket = null;

		reconnectDisabled = true;

		return true;
	};

	/**
	 * @param {object} data
	 *
	 * @return {boolean}
	 */
	that.Send = function (data) {
		if (!(that.Socket instanceof WebSocket)) return false;
		if (that.Socket.readyState !== that.Socket.OPEN) return false;

		that.Socket.send(data);
		return true;
	};
};

/**
 * @param {*} opt
 *
 * @constructor
 */
Quark.Network.Client = function (opt) {
	opt = opt || {};
		opt.onMessage = opt.onMessage || null;
		opt.onError = opt.onError || null;

	var that = this,
		_event = {},
		_response = {};

	that.Socket = new Quark.Network.Socket(opt);

	that.Socket.onMessage = function (e) {
		if (opt.onMessage instanceof Function)
			opt.onMessage(e);

		var input = JSON.parse(e.data),
			key = '';

		if (input.response !== undefined)
			for (key in _response)
				if (input.response.match(new RegExp('^' + key, 'i')) && _response[key] instanceof Function)
					return _response[key](input.response, input.data, input.session);

		if (input.event !== undefined)
			for (key in _event)
				if (input.event.match(new RegExp('^' + key, 'i')) && _event[key] instanceof Function)
					return _event[key](input.event, input.data, input.session);
	};

	/**
	 * @param {string} url
	 * @param {Function=} [event]
	 *
	 * @return {Function}
	 */
	that.Event = function (url, event) {
		if (event instanceof Function)
			_event[url] = event;

		return _event[url] instanceof Function ? _event[url] : null;
	};

	/**
	 * @param {string} url
	 * @param {Function=} [response]
	 *
	 * @return {Function}
	 */
	that.Response = function (url, response) {
		if (response instanceof Function)
			_response[url] = response;

		return _response[url] instanceof Function ? _response[url] : null;
	};

	/**
	 * @param {string} url
	 * @param {object=} [data]
	 * @param {object=} [session]
	 *
	 * @returns {boolean}
	 */
	that.Service = function (url, data, session) {
		try {
			var out = {
				url: url,
				data: data
			};

			if (session !== undefined)
				out.session = session;
			
			return that.Socket.Send(JSON.stringify(out));
		}
		catch (e) {
			if (opt.onError instanceof Function)
				opt.onError(e);
			
			return false;
		}
	};

	/**
	 * @returns {boolean}
	 */
	that.Connect = function () {
		return that.Socket.Connect();
	};

	/**
	 * @returns {boolean}
	 */
	that.Close = function () {
		return that.Socket.Close();
	};
};