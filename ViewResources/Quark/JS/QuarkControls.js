/**
 * JS part of SaaS PHP framework
 *
 * @type {Quark}
 */
var Quark = Quark || {};

/**
 * Quark.Controls namespace
 */
Quark.Controls = {};

/**
 * @param data
 * @param append
 *
 * @return {string}
 *
 * @private
 */
Quark.Controls._toForm = function (data, append) {
	append = append || '';

	var out = '', end = '';

	for (key in data) {
		end = append == '' ? key : (append + '[' + key + ']');

		if (data[key] != undefined && (data[key].constructor == Object || data[key].constructor == Array))
			out += Quark.Controls._toForm(data[key], end);
		else out += Quark.Controls._field(end, data[key] !== undefined ? data[key] : '');
	}

	return out;
};

/**
 * @param key
 * @param value
 *
 * @return {string}
 *
 * @private
 */
Quark.Controls._field = function (key, value) {
	return '<input name="' + key + '" value="' + value + '" />';
};

/**
 * @param selector
 * @param data
 *
 * @constructor
 */
Quark.Controls.Chart = function (selector, data) {
	var that = this;

	that.Render = function () {

	};
};

/**
 * @param selector
 * @param opt
 *
 * @constructor
 */
Quark.Controls.Select = function (selector, opt) {
	var that = this;

	opt = opt || {};
	opt.values = opt.values || {};
	opt.default = opt.default || false;

	that.Elem = $(selector);

	that.Elem.each(function () {
		var i = 0, html = '', selected = '', key = '';

		for (key in opt.values) {
			if (i == 0)
				selected = key;

			html +='<a class="quark-button block"><span style="display:none;">' + opt.values[key] + '</span>' + key + '</a>';

			i++;
		}

		if (!opt.default)
			selected = '<a class="quark-button block"><span style="display:none;">' + opt.values[selected] + '</span>' + selected + '</a>';

		$(this).append(selected + '<span class="quark-tool-group">' + html + '</span>');
	});

	that.Tool = function (html) {
		that.Elem.append(html);
	};

	that.Elem.on('click', function (e) {
		that.Elem.children('.quark-tool-group').css({
			display: 'block'
		});

		e.stopPropagation();
	});

	that.Elem.children('.quark-tool-group').on('click', function (e) {
		that.Elem.children('.quark-tool-group').css({
			display: 'none'
		});

		e.stopPropagation();
	});
};

/**
 * @param selector
 * @param opt
 *
 * @constructor
 */
Quark.Controls.File = function (selector, opt) {
	var that = this;

	that.Elem = $(selector);
	that.Elem.on('click', opt, Quark.Controls.File._send);
};

Quark.Controls.File._send = function (e) {
	Quark.Controls.File.To($(this).attr('quark-url'), $(this).attr('quark-name'), Quark.Extend(e.data, {
		data: {
			_s: $(this).attr('quark-signature')
		}
	}));
};

/**
 * @param url
 * @param name
 * @param opt
 */
Quark.Controls.File.To = function (url, name, opt) {
	opt = Quark.Extend(opt, {
		multiple: false,
		json: true,
		data: {},
		beforeSelect: function (elem) { },
		beforeSubmit: function (elem) { },
		success: function (response) { },
		error: function (response) { }
	});

	var key = '-upload';

	if ($('#target' + key).length == 0)
		$('body').append(
			'<iframe id="target' + key + '" name="target' + key + '" style="display: none;"></iframe>' +
			'<form id="form' + key + '" action="' + url + '" target="target' + key + '" method="POST" enctype="multipart/form-data" style="display: none;">' +
				Quark.Controls._toForm(opt.data) +
				'<input type="file" name="' + name + (opt.multiple ? '[]' : '') + '" />' +
			'</form>'
		);

	var frame = $('#target' + key);
	var form = $('#form' + key);

	frame.on('load', function (e) {
		var response = $(this).contents().text();

		if (opt.json)
			response = JSON.parse(response);

		opt.success(response);
	});

	form
		.children('[type="file"]')
		.on('click', function (e) {
			$(this).val('');

			opt.beforeSelect($(this));
		})
		.on('change', function (e) {
			opt.beforeSubmit($(this));

			if ($(this).val().length != 0)
				form.submit();
		})
		.click();
};

/**
 * @param {string} selector
 * @param {object} opt
 *
 * @constructor
 */
Quark.Controls.Toggle = function (selector, opt) {
	opt = opt || {};
		opt.enabled = opt.enabled || {};
			opt.enabled.title = opt.enabled.title || '';
			opt.enabled.html = opt.enabled.html || '';
			opt.enabled.action = opt.enabled.action || false;
		opt.disabled = opt.disabled || {};
			opt.disabled.title = opt.disabled.title || '';
			opt.disabled.html = opt.disabled.html || '';
			opt.disabled.action = opt.disabled.action || false;

	var that = this;

	that.Elem = $(selector);

	/**
	 * @param elem
	 * @param {boolean} available
	 */
	that.Available = function (elem, available) {
		elem.removeClass(available ? 'disabled' : '');
		elem.addClass(available ? '' : 'disabled');
	};

	that._attr = function (enabled, elem) {
		elem.removeClass(enabled ? 'fa-toggle-off off' : 'fa-toggle-on on');
		elem.addClass(enabled ? 'fa-toggle-on on' : 'fa-toggle-off off');
		elem.attr('quark-enabled', enabled ? 'true' : 'false');

		if (elem.attr('disabled') == 'disabled')
			that.Available(elem, false);

		var name = opt.name == undefined
			? (elem.attr('name') || '')
			: opt.name;

		if (name == '') return;

		var input = elem.find('input[type="hidden"]');

		if (input.length == 0)
			elem.append('<input type="hidden" name="' + name + '" value="' + (enabled ? 'on' : 'off') + '" />');

		input.val(enabled ? 'on' : 'off');
	};

	if (opt.enable != undefined)
		that._attr(enabled, that.Elem);

	$(document).on('click', selector, function (e) {
		e.preventDefault();

		if ($(this).attr('disabled') != 'disabled')
			that.Toggle($(this));
	});

	that.Elem.each(function () {
		that._attr($(this).attr('quark-enabled') == 'true', $(this));
	});

	/**
	 * @param elem
	 */
	that.Toggle = function (elem) {
		if (elem.attr('quark-enabled') == 'true') opt.enabled.action(that, elem);
		else opt.disabled.action(that, elem);

		if (opt.autoState) that.State(elem);
	};

	/**
	 * @param elem
	 */
	that.State = function (elem) {
		var enabled = elem.attr('quark-enabled') != 'true';

		elem.attr('title', enabled ? opt.enabled.title : opt.disabled.title);
		elem.html(enabled ? opt.enabled.html : opt.disabled.html);

		that._attr(enabled, elem);
	};
};