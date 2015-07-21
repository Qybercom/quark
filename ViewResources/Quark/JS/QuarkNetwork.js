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
 * @param host
 * @param port
 * @param on
 *
 * @constructor
 */
Quark.Network.Client = function (host, port, on) {
    on = on || {};
        on.open = on.open || function () {};
        on.close = on.close || function () {};
        on.error = on.error || function () {};

    var that = this;
    var events = {};
    var response = function () {};
    var message = function (e) {
        try {
            var input = JSON.parse(e.data);

            if (input.response != undefined)
                response(input.response, input.data, input.session);

            if (input.event != undefined && events[input.event] != undefined)
                events[input.event](input.event, input.data, input.session);
        }
        catch (e) {
            on.error(e);
        }
    };

    that.host = host;
    that.port = port;
    that.socket = null;
    that.session = {};

    /**
     * API methods
     */
    that.Connect = function () {
        that.socket = new WebSocket('ws://' + host + ':' + port);

        that.socket.onmessage = message;

        that.socket.onopen = on.open;
        that.socket.onclose = on.close;
        that.socket.onerror = on.error;
    };

    /**
     * @return {boolean}
     */
    that.Close = function () {
        if (that.socket == null) return false;

        that.socket.close();
        that.socket = null;

        return true;
    };

    /**
     * @param data
     * @return {boolean}
     */
    that.Send = function (data) {
        if (!(that.socket instanceof WebSocket)) return false;

        that.socket.send(data);
        return true;
    };

    /**
     * @param url
     * @param listener
     *
     * @return {boolean}
     */
    that.Event = function (url, listener) {
        if (!(listener instanceof Function)) return false;

        if (events[url] == undefined)
            events[url] = [];

        events[url].push(listener);

        return true;
    };

    /**
     * @param listener
     * @return {boolean}
     */
    that.Response = function (listener) {
        if (!(listener instanceof Function)) return false;

        response = listener;

        return true;
    };

    /**
     * @param url
     * @param data
     * @param session
     */
    that.Service = function (url, data, session) {
        try {
            var out = {
                url: url,
                data: data
            };

            if (session != undefined && that.session[session] != undefined)
                out.session = that.session[session];

            that.Send(JSON.stringify(out));
        }
        catch (e) {
            on.error(e);
        }
    };

    /**
     * @param name
     * @param opt
     */
    that.Session = function (name, opt) {
        that.session[name] = opt;
    };
};

/**
 * Get a connection from cluster controller, specified by host and port, to the most suitable cluster node
 *
 * @param host
 * @param port
 */
Quark.Network.Client.From = function (host, port) {

};