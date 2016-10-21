/**
 * JS part of SaaS PHP framework
 *
 * @type {Quark}
 */
var Quark = Quark || {};

/**
 * Quark.Network namespace
 */
Quark.Network = {};

/**
 * @type {{
 *  host: string,
 *  port: number,
 *  socket: WebSocket,
 *  on: {
 *      message: Function,
 *      error: Function
 *  },
 *  constructor: Function,
 *  Connect: Function,
 *  Close: Function,
 *  Send: Function
 * }}
 */
Quark.Network.Socket = {
    host: '',
    port: 0,
    socket: null,
    connected: false,
    on: {
        open: function () {},
        close: function () {},
        message: function () {},
        error: function (e) { console.warn(e); }
    },

    /**
     * @param {string=} host
     * @param {number=} port
     * @param {object=} [on={open,close,error}]
     *
     * @constructor
     */
    constructor: function (host, port, on) {
        this.host = host;
        this.port = port;
        this.on = on;
    },

    /**
     * API methods
     */
    Connect: function () {
    	var that = this;

        this.socket = new WebSocket('ws://' + this.host + ':' + this.port);

        this.socket.onmessage = this.on.message;
        this.socket.onerror = this.on.error;

        this.socket.onopen = function (e) {
            that.connected = true;
            that.on.open(e);
        };

        this.socket.onclose = function (e) {
            that.connected = false;
            that.on.close(e);
        };
    },

    /**
     * @return {boolean}
     */
    Close: function () {
        if (this.socket == null) return false;

        this.socket.close();
        this.socket = null;

        return true;
    },

    /**
     * @param {object} data
     *
     * @return {boolean}
     */
    Send: function (data) {
        if (!(this.socket instanceof WebSocket)) return false;

        this.socket.send(data);
        return true;
    }
};

/**
 * @param {string=} [host=document.location.hostname]
 * @param {number=} [port=25000]
 * @param {object=} [on={open,close,error}]
 *
 * @constructor
 */
Quark.Network.Client = function (host, port, on) {
    on = on || this.on;

    var that = this;

    var _event = {};
    var _response = {};

    on.message = function (e) {
        try {
            var input = JSON.parse(e.data), key = '';

			if (input.response != undefined)
				for (key in _response)
					if (input.response.match(new RegExp('^' + key, 'i')) && _response[key] instanceof Function)
                		return _response[key](input.response, input.data, input.session);

			if (input.event != undefined)
				for (key in _event)
					if (input.event.match(new RegExp('^' + key, 'i')) && _event[key] instanceof Function)
                		return _event[key](input.event, input.data, input.session);
        }
        catch (e) {
            on.error(e);
        }
    };

    /**
     * @param {string} url
     * @param {Function=} [event]
     *
     * @return {Function|undefined}
     */
    that.Event = function (url, event) {
        if (event instanceof Function)
            _event[url] = event;

        return _event[url] == undefined ? undefined : _event[url];
    };

    /**
     * @param {string} url
     * @param {Function=} [response]
     *
     * @return {Function|undefined}
     */
    that.Response = function (url, response) {
        if (response instanceof Function)
            _response[url] = response;

        return _response[url] == undefined ? undefined : _response[url];
    };

    /**
     * @param {string} url
     * @param {object=} [data]
     * @param {object=} [session]
     */
    that.Service = function (url, data, session) {
        try {
            var out = {
                url: url,
                data: data
            };

            if (session != undefined)
                out.session = session;

            that.Send(JSON.stringify(out));
        }
        catch (e) {
            on.error(e);
        }
    };

    that.constructor(host || document.location.hostname, port || 25000, on);
};

Quark.Network.Client.prototype = Quark.Network.Socket;

/**
 * Get a connection from cluster controller, specified by host and port, to the most suitable cluster node
 *
 * @param {string=} [host=document.location.hostname]
 * @param {number=} [port=25900]
 * @param {Function} available
 * @param {Function} error
 */
Quark.Network.Client.From = function (host, port, available, error) {
    var terminal = new Quark.Network.Terminal(host || document.location.hostname, port || 25900);

    terminal.Command('endpoint', function (cmd, endpoint) {
        if (!endpoint) error();
        else available(endpoint);
    });
};

/**
 * @param {string=} [host=document.location.hostname]
 * @param {number=} [port=25900]
 * @param {object=} [on={close,error}]
 *
 * @constructor
 */
Quark.Network.Terminal = function (host, port, on) {
    on = on || this.on;

    var that = this;

    var commands = {};

    var _signature = '';
    var _infrastructure = function () {};

    on.open = function () {
        that.Send(JSON.stringify({
            cmd: 'authorize',
            data: {},
            signature: _signature
        }));
    };

    on.message = function (e) {
        try {
            var input = JSON.parse(e.data);

            if (input.cmd == undefined) return;

            input.cmd = input.cmd.toLowerCase();

            if (input.cmd == 'infrastructure' && _infrastructure instanceof Function)
                _infrastructure(input.data);

            if (commands[input.cmd] instanceof Array) {
                var i = 0;

                while (i < commands[input.cmd].length) {
                    commands[input.cmd][i](input.cmd, input.data);

                    i++;
                }
            }
        }
        catch (e) {
            on.error(e);
        }
    };

    /**
     * @param {string=} [signature]
     *
     * @return {string}
     */
    that.Signature = function (signature) {
        if (signature != undefined)
            _signature = signature;

        return _signature;
    };

    /**
     * @param {Function=} [infrastructure]
     *
     * @return {Function}
     */
    that.Infrastructure = function (infrastructure) {
        if (infrastructure instanceof Function)
            _infrastructure = infrastructure;

        return _infrastructure;
    };

    /**
     * @param {string} cmd
     * @param {Function} listener
     *
     * @return {boolean}
     */
    that.Command = function (cmd, listener) {
        if (!(listener instanceof Function)) return false;

        cmd = cmd.toLowerCase();

        if (commands[cmd] == undefined)
            commands[cmd] = [];

        commands[cmd].push(listener);

        return true;
    };

    that.constructor(host || document.location.hostname, port || 25900, on);
};

Quark.Network.Terminal.prototype = Quark.Network.Socket;