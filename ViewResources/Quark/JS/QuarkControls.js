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
 * @param {string} selector
 *
 * @constructor
 */
Quark.Controls.Form = function (selector) {
	var that = this;

	that._message = function (form, show) {
		form.find('.quark-message').slideUp();
		form.find('.quark-message' + show).slideDown();
	};

	$(document).on('submit', selector || 'form', function (e) {
		e.preventDefault();

		var form = $(this);

		that._message(form, '.info');

		$.ajax({
			url: form.attr('action'),
			type: form.attr('method'),
			dataType: 'json',
			data: form.serialize(),

			success: function (data) {
				that._message(form, data.status != undefined && data.status == 200 ? '.ok' : '.warn');
			},

			error: function () {
				that._message(form, '.warn');
			}
		});
	});
};

/**
 * @param {string} selector
 * @param opt
 *
 * @constructor
 */
Quark.Controls.Dialog = function (selector, opt) {
	opt = opt || {};
		opt.box = opt.box == undefined ? '#quark-dialog-box' : opt.box;
		opt.reset = opt.reset != undefined ? opt.reset : true;
		opt.type = opt.type != undefined ? opt.type : 'GET';
		opt.successCriteria = opt.successCriteria instanceof Function
			? opt.successCriteria
			: function (data) { return data.status != undefined && data.status == 200; };

	var that = this;

	that.Reset = function (dialog) {
		dialog.find('.quark-dialog-state').slideUp();
		dialog.find('.quark-dialog-confirm').slideDown();
	};

	that.Wait = function (dialog) {
		dialog.find('.quark-dialog-state').slideUp();
		dialog.find('.quark-dialog-state.wait').slideDown();
	};

	that.Success = function (dialog, trigger) {
		dialog.find('.quark-dialog-state').slideUp();
		dialog.find('.quark-dialog-state.success').slideDown();
		dialog.find('.quark-dialog-confirm').slideUp();

		if (opt.success instanceof Function)
			opt.success(trigger, dialog);
	};

	that.Error = function (dialog, data) {
		if (data != undefined && data.errors instanceof Array) {
			var i = 0;
			var errors = '';

			while (i < data.errors.length) {
				errors += '<li>' + data.errors[i] + '</li>';

				i++;
			}

			dialog.find('.quark-dialog-state.error').html('<ul>' + errors + '</ul>');
		}

		dialog.find('.quark-dialog-state').slideUp();
		dialog.find('.quark-dialog-state.error').slideDown();
	};

	$(function () {
		if ($(opt.box).length == 0)
			$('body').prepend('<div id="' + opt.box.replace('#', '') + '"></div>');

		$(opt.box).hide(0);

		$('.quark-dialog').each(function () {
			var dialog = $(this);

			dialog.hide(0);

			dialog.appendTo(opt.box);
			dialog.find('.quark-dialog-state').hide(0);
		});
	});

	$(document).on('click', '.quark-dialog-confirm', function (e) {
		e.preventDefault();

		var action = $(this);
		var dialog = action.parent('.quark-dialog');

		$.ajax({
			url: action.attr('href'),
			dataType: 'json',
			type: opt.type,
			data: dialog.serialize(),

			beforeSend: function () {
				that.Wait(dialog);
			},

			success: function (data) {
				if (opt.successCriteria(data)) that.Success(dialog, dialog.data('button'));
				else that.Error(dialog, data);
			},

			error: function () {
				that.Error(dialog);
			}
		});
	});

	$(document).on('click', '.quark-dialog-close', function (e) {
		e.preventDefault();

		var action = $(this);
		var dialog = action.parent('.quark-dialog');

		if (opt.close instanceof Function && opt.close(dialog, action) === false) return;

		dialog.fadeOut(500);
		$(opt.box).fadeOut(500);
	});

	$(document).on('click', selector, function (e) {
		e.preventDefault();

		var button = $(this);
		var dialog = $(button.attr('quark-dialog'));

		that.Reset(dialog);

		dialog.data('button', button);
		dialog.find('.quark-dialog-confirm').attr('href', button.attr('href'));

		if (opt.open instanceof Function && opt.open(dialog, button) === false) return;

		dialog.fadeIn(500);
		$(opt.box).fadeIn(500);
	});
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
	$(document).on('click', selector, function (e) {
		Quark.Controls.File.To($(this).attr('quark-url'), $(this).attr('quark-name'), Quark.Extend(opt, {
			data: {
				_s: $(this).attr('quark-signature')
			}
		}), $(this));
	});
};

/**
 * @param {string} url
 * @param {string} name
 * @param opt
 * @param uploader
 */
Quark.Controls.File.To = function (url, name, opt, uploader) {
	opt = Quark.Extend(opt, {
		multiple: false,
		json: true,
		data: {},
		beforeSelect: function (elem) { },
		beforeSubmit: function (elem) { },
		success: function (response) { },
		error: function (response) { }
	});

	var key = '-upload-' + Quark.GuID(),
		target = '#target' + key;

	if ($(target).length == 0)
		$('body').append(
			'<iframe id="target' + key + '" name="target' + key + '" style="display: none;"></iframe>' +
			'<form id="form' + key + '" action="' + url + '" target="target' + key + '" method="POST" enctype="multipart/form-data" style="display: none;">' +
				Quark.Controls._toForm(opt.data) +
				'<input type="file" name="' + name + (opt.multiple ? '[]' : '') + '" />' +
			'</form>'
		);

	var frame = $(target);
	var form = $('#form' + key);

	frame.on('load', function (e) {
		var response = $(this).contents().text();

		try {
			if (opt.json)
				response = JSON.parse(response);

			opt.success(response, uploader);
		}
		catch (e) {
			opt.error(response, uploader);
		}
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
 * @param opt
 *
 * @constructor
 */
Quark.Controls.DynamicList = function (selector, opt) {
	opt = opt || {};
		opt.name = opt.name == undefined ? 'list' : opt.name;
		opt.placeholder = opt.placeholder == undefined ? '' : opt.placeholder;
		opt.prepend = opt.prepend == undefined ? false : opt.prepend;
		opt.preventDefault = opt.preventDefault == undefined ? false : opt.preventDefault;
		opt.item = opt.item == undefined
			? (
				'<div class="quark-list-item">' +
					'<input class="quark-input item-value" name="' + opt.name + '[]" placeholder="' + opt.placeholder + '" />' +
					'<a class="quark-button fa fa-times item-remove"></a>' +
				'</div>'
			)
			: opt.item;

	$(document).on('click', selector, function (e) {
		e.preventDefault();

		var button = $(this);
		var list = $(button.attr('quark-list'));

		if (opt.prepend) list.prepend(opt.item);
		else list.append(opt.item);
	});

	$(document).on('click', '.quark-list-item .item-remove', function (e) {
		$(this).parents('.quark-list-item').remove();

		if (opt.preventDefault)
			return false;
	});
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
		var val = $(this).attr('quark-enabled'),
			enabled = val == 'true' || val == '1';

		that._attr(enabled, $(this));
		$(this).html(enabled ? opt.enabled.html : opt.disabled.html);
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

/**
 * @param {string} selector
 * @param {object} opt
 *
 * @constructor
 */
Quark.Controls.Gallery = function (selector, opt) {
	opt = opt || {};
		opt.box = opt.box == undefined ? '#quark-gallery-box' : opt.box;

	var that = this;

	$(function () {
		if ($(opt.box).length == 0)
			$('body').prepend('<div id="' + opt.box.replace('#', '') + '"></div>');

		$(opt.box).hide(0);

		$('.quark-gallery').each(function () {
			var gallery = $(this);

			gallery.hide(0);
			gallery.appendTo(opt.box);
		});
	});

	$(document).on('click', selector, function (e) {
		e.preventDefault();

		var image = $(this),
			gallery = $(image.attr('quark-gallery'));

		gallery.find('.quark-gallery-viewport').css('background-image', 'url(' + image.attr('quark-gallery-item') + ')');
		gallery.fadeIn(500);

		$(opt.box).fadeIn(500);
	});

	$(document).on('click', opt.box + ', .quark-gallery-close', function (e) {
		e.preventDefault();

		var action = $(this),
			gallery = action.parent('.quark-gallery');

		gallery.fadeOut(500);
		$(opt.box).fadeOut(500);
	});
};