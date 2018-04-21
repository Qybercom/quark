_chat = _chat || null;

/**
 * @param {object} opt
 *
 * @constructor
 */
var ChatKit = function (opt) {
	opt = opt || {};
		opt.uri = opt.uri || _chat;
		opt.responses = opt.responses || {};
		opt.events = opt.events || {};

		opt.open = opt.open instanceof Function ? opt.open : function () {};
		opt.error = opt.error instanceof Function ? opt.error : function () {};
		opt.close = opt.close instanceof Function ? opt.close : function () {};
		opt.reconnect = opt.reconnect == undefined ? false : opt.reconnect;

		opt.payload = opt.payload instanceof Function ? opt.payload : function () {};
		opt.date = opt.date instanceof Function ? opt.date : function (data) { return ChatKit.LocalTime(data.date); };
		opt.sender = opt.sender instanceof Function ? opt.sender : function (data) { return data.from != undefined ? data.from.id || data.from._id : false; };
		opt.scrollTop = opt.scrollTop == undefined ? 999999 : opt.scrollTop;

		opt.isCurrentChannel = opt.isCurrentChannel instanceof Function ? opt.isCurrentChannel : function () { return true; };
		opt.isLastMemberMessage = opt.isLastMemberMessage instanceof Function ? opt.isLastMemberMessage : function () { return false; };
		opt.isOwnMessage = opt.isOwnMessage instanceof Function ? opt.isOwnMessage : function () { return false; };

		/**
		 * @param {object} data
		 * @param {string} payload
		 */
		opt.messageMemberLast = opt.messageMemberLast instanceof Function ? opt.messageMemberLast : function (data, payload) {
			var date = $('#ChatKit-history .ChatKit-message:last').find('.ChatKit-date');

			date.before(payload);
			date.html(opt.date(data));
		};
		/**
		 * @param {object} data
		 * @param {string} payload
		 * @param {Quark.MVC.Model} message
		 */
		opt.messageMemberNew = opt.messageMemberNew instanceof Function ? opt.messageMemberNew : function (data, payload, message) {
			message.Data._payload = payload;
			message.Data._date = opt.date(message.Data);
			message.Data._own = opt.isOwnMessage(data) ? ' own' : '';

			$('#ChatKit-history > .quark-presence-column').append(message.Map('#ChatKit-message-template'));
		};

	var that = this;

	/**
	 * @type {Quark.Network.Client} Connection
	 */
	that.Connection = new Quark.Network.Client(opt.uri.host, opt.uri.port);
	that.Connection.secure = opt.uri.secure;
	that.Connection.on.open = opt.open;
	that.Connection.on.error = opt.error;
	that.Connection.on.close = !opt.reconnect ? opt.close : function (e) {
		opt.close(e);
		that.Connect();
	};

	/**
	 * @type {object} Session
	 */
	that.Session = opt.session || {};

	/**
	 * @type {object} Conversation
	 */
	that.Conversation = {
		Current: null,
		Last: null,
		History: []
	};

	var response = '';
	for (response in opt.responses)
		that.Connection.Response(response, opt.responses[response]);

	var event = '';
	for (event in opt.events)
		that.Connection.Event(event, opt.events[event]);

	/**
	 * Connect to server
	 */
	that.Connect = function () {
		that.Connection.Connect();
	};

	/**
	 * Disconnect from server
	 */
	that.Close = function () {
		that.Connection.Close();
	};

	/**
	 * @param {string} url
	 * @param {object=} data
	 */
	that.Service = function (url, data) {
		that.Connection.Service(url, data, that.Session);
	};

	/**
	 * @param {object} data
	 */
	that.Message = function (data) {
		var current = opt.isCurrentChannel(data);

		if (current === false) return;
		that.Conversation.Current = current;

		var payload = opt.payload(data);

		if (that.Conversation.History.length != 0 && opt.isLastMemberMessage(data)) opt.messageMemberLast(data, payload);
		else opt.messageMemberNew(data, payload, new Quark.MVC.Model(data));

		that.Conversation.Last = opt.sender(data);
		that.Conversation.History.push(data);

		$('#ChatKit-history').scrollTop(opt.scrollTop);
	};

	/**
	 * @param {Array} history
	 *
	 * @return {boolean}
	 */
	that.History = function (history) {
		if (!(history instanceof Array)) return false;

		that.Conversation.History = history;

		var message = new Quark.MVC.Model();
		var i = 0, out = '', last = 0, j = 0, sender = '';

		while (i < history.length) {
			sender = opt.sender(history[i]);

			if (sender === false) {
				i++;
				continue;
			}

			last = sender;

			message.Data = history[i];
			message.Data._payload = '';
			message.Data._own = opt.isOwnMessage(history[i]) ? ' own' : '';

			j = i;
			while (history[j] != undefined && opt.sender(history[j]) === last) {
				message.Data._payload += opt.payload(history[j]);
				message.Data._date = opt.date(history[j]);

				j++;
			}

			out += message.Map('#ChatKit-message-template');

			that.Conversation.Last = last;

			if (i == j) i++;
			else i = j;
		}

		$('#ChatKit-history > .quark-presence-column').html(out).parents('#ChatKit-history').scrollTop(opt.scrollTop);
		$('#ChatKit-input').fadeIn(500);

		return true;
	};

	/**
	 * Scroll history block
	 */
	that.HistoryScroll = function () {
		$('#ChatKit-history').scrollTop(that.scrollTop);
	};

	/**
	 * Call error handler
	 */
	that.Error = function () {
		opt.error();
	};
};

/**
 * @param {string} date
 * @param {string=} format
 *
 * @return {string}
 */
ChatKit.LocalTime = function (date, format) {
	format = format || '<b>HH:mm:ss</b>' + '[&nbsp;]' + 'DD.MM.YYYY';

	return moment(moment.utc(date).toDate()).format(format);
};