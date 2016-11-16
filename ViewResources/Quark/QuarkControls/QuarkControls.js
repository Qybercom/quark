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

	that.Open = function (dialog, confirm, data) {
		dialog = dialog || $($(selector).attr('quark-dialog'));

		that.Reset(dialog);

		dialog.data('button', data);
		dialog.find('.quark-dialog-confirm').attr('href', confirm);

		if (opt.open instanceof Function && opt.open(dialog, data) === false) return;

		dialog.fadeIn(500);
		$(opt.box).fadeIn(500);
	};

	that.Close = function (dialog, action) {
		dialog = dialog || $($(selector).attr('quark-dialog'));

		if (opt.close instanceof Function && opt.close(dialog, action) === false) return;

		dialog.fadeOut(500);
		$(opt.box).fadeOut(500);
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

		that.Close(dialog, action);
	});

	$(document).on('click', selector, function (e) {
		e.preventDefault();

		var button = $(this);
		var dialog = $(button.attr('quark-dialog'));

		that.Open(dialog, button.attr('href'), button);
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
		var item = $(this).parents('.quark-list-item'),
			remove = opt.remove instanceof Function ? opt.remove(item, e) : true;

		if (remove)
			item.remove();

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
		opt.autoState = opt.autoState == undefined ? true : opt.autoState;
		opt.enabled = opt.enabled || {};
			opt.enabled.title = opt.enabled.title || '';
			opt.enabled.html = opt.enabled.html || '';
			opt.enabled.action = opt.enabled.action || function () {};
		opt.disabled = opt.disabled || {};
			opt.disabled.title = opt.disabled.title || '';
			opt.disabled.html = opt.disabled.html || '';
			opt.disabled.action = opt.disabled.action || function () {};

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
			input = elem
				.append('<input type="hidden" name="' + name + '" value="' + (enabled ? 'on' : 'off') + '" />')
				.find('input[type="hidden"]');

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
		var elem = $(this),
			val = elem.attr('quark-enabled'),
			enabled = val == 'true' || val == '1';

		elem.html(enabled ? opt.enabled.html : opt.disabled.html);
		that._attr(enabled, elem);
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

/**
 * @param {string} selector
 * @param opt
 *
 * @constructor
 */
Quark.Controls.Range = function (selector, opt) {
	var that = this;

	opt = opt || {};
		opt.asDefined = opt.asDefined != undefined ? opt.asDefined : true;
		opt.change = opt.change != undefined ? opt.change : function () {};

	that.Elem = $(selector);
	that.Elem.attr('type', 'hidden');

	that.Name = that.Elem.attr('name');
	that.Value = that.Elem.attr('value');
	that.Min = parseFloat(that.Elem.attr('min'));
	that.Max = parseFloat(that.Elem.attr('max'));

	that.Sliders = [];

	var _id = Quark.GuID();

	$('body').append('<div class="quark-range-slider" id="' + _id + '"></div>');

	var _m = $('#' + _id);
	var sliderWidth = _m.outerWidth();
	_m.remove();

	that.Elem.each(function () {
		var elem = $(this);

		var sliders = elem.attr()
			.map(function (item) {
				if (item.name.indexOf('quark-slider-') == -1) return;

				return {
					name: item.name.replace('quark-slider-', ''),
					value: item.value
				};
			})
			.filter(function (item) {
				return item != undefined;
			});

		if (sliders.length == 0 && opt.asDefined == true)
			sliders.push({
				name: that.Name,
				value: that.Value
			});

		elem = that.Elem.wrap('<div class="quark-input range"></div>').parent();
		elem.html('');

		var width = parseFloat(elem.width()) - sliderWidth;
		var i = 0;
		while (i < sliders.length) {
			var margin = (sliders[i].value / 100) * width;

			elem.append(
				'<div class="quark-range-slider" quark-slider-name="' + sliders[i].name + '" style="margin-left: ' + margin + 'px;">' +
				'<input type="hidden" name="' + sliders[i].name + '" value="' + sliders[i].value + '" />' +
				'</div>'
			);

			i++;
		}

		var slide = new Quark.UX(elem.find('.quark-range-slider'));
		slide.Drag({
			axis: {y:false},
			delegateParent: false,
			defaultCss: false,
			drag: function (e) {
				var val = e.target.data('_slide') == undefined
					? parseInt(e.target.css('margin-left').replace('px'))
					: (e.current.x - parseInt(e.target.data('_slide')));

				if (val < 0 || val > width) return;

				var frame = {
					name: e.target.attr('quark-slider-name'),
					range: elem,
					slider: e.target,
					value: (val / width) * 100
				};

				opt.change(frame);

				e.target.css('margin-left', val + 'px');
				e.target.data('_slide', e.current.x - val);
				e.target.find('input[type="hidden"]').val(frame.value);
			}
		});
	});
};

/**
 * @param selector
 * @param opt
 *
 * @constructor
 */
Quark.Controls.LocalizedInput = function (selector, opt) {
	var that = this,
		_encode = function (json) {
			try { return Quark.Base64.Encode(JSON.stringify(json)); }
			catch (e) { return null; }
		},
		_decode = function (json) {
			try { return JSON.parse(Quark.Base64.Decode(json)); }
			catch (e) { return null; }
		};

	opt = opt || {};
		opt.labels = opt.labels != undefined ? opt.labels : {'*': 'Any'};

	that.Elem = $(selector);

	that.Elem.each(function () {
		var elem = $(this),
			val = elem.val(),
			language = '',
			languages = elem.attr('quark-languages'),
			selected = elem.attr('quark-language'),
			select = '<select class="quark-input quark-language-select" name="' + elem.attr('name') + '_language">',
			json = _decode(val),
			i = 0;

		languages = languages == undefined ? [] : languages.split(',');
		languages.unshift('*');

		while (i < languages.length) {
			select += '<option value="' + languages[i] + '"' + (languages[i] == selected ? ' selected="selected"' : '') + '>'
					+ (opt.labels[languages[i]] != undefined ? opt.labels[languages[i]] : languages[i])
					+ '</option>';

			i++;
		}

		select += '</select>';

		var copy = elem.clone();
		copy
			.attr('name', copy.attr('name') + '_localized')
			.val(json != null && json[selected] != undefined ? json[selected] : '');

		elem
			.css('display', 'none')
			.after(select)
			.after(copy);

		elem.parent().find('[name="' + elem.attr('name') + '_localized"]').on('change', function () {
			var localized = $(this),
				name = localized.attr('name').replace(/_localized$/, ''),
				orig = localized.parent().find('[name="' + name + '"]'),
				lang = orig.parent().find('[name="' + name + '_language"]'),
				json = _decode(orig.val()),
				selected = lang.val();

			if (json == null)
				json = {};

			json[selected] = localized.val();
			var val = _encode(json);

			if (opt.change instanceof Function)
				opt.change(json[selected], json ,val);

			orig.val(val);
		});
	});

	$(document).on('change', 'select.quark-language-select', function () {
		var lang = $(this),
			name = lang.attr('name').replace(/_language$/, ''),
			orig = lang.parent().find('[name="' + name + '"]'),
			display = lang.parent().find('[name="' + orig.attr('name') + '_localized"]'),
			json = _decode(orig.val()),
			selected = lang.val(),
			val = json != null && json[selected] != undefined ? json[selected] : '';

		if (opt.localize instanceof Function)
			opt.localize(selected, val);

		display.val(val);
	});

	that.Localize = function (language, value, selector) {
		$(selector || that.Elem).each(function () {
			var elem = $(this),
				name = elem.attr('name'),
				lang = elem.parent().find('[name="' + name + '_language"]'),
				display = elem.parent().find('[name="' + name + '_localized"]'),
				json = _decode(elem.val());

			language = language || lang.val();

			if (value != undefined) {
				json[language] = value;
				elem.val(_encode(json));
			}

			var val = json != null && json[language] != undefined ? json[language] : '';

			if (opt.localize instanceof Function && opt.localizeSelf)
				opt.localize(language, val);

			lang.val(language);
			display.val(val);
		});
	};
};

Quark.Controls.Scrollable = function (selector, opt) {
	var that = this;

	opt = opt || {};
		opt.scroll = opt.scroll != undefined ? opt.scroll : function () {};

	that.Elem = $(selector);

	that.Elem.each(function () {
		var elem = $(this);

		elem.css('overflow', 'hidden');
		elem = elem.wrap('<div class="quark-scrollable"></div>').addClass('quark-scrollable-content').parent();
		elem.append('<div class="quark-scroll-bar"><div class="quark-scroll-trigger"></div></div>');

		var height = 100;

		var scroll = new Quark.UX(elem.find('.quark-scroll-trigger'));
		scroll.Drag({
			axis: {x:false},
			delegateParent: false,
			defaultCss: false,
			drag: function (e) {
				var val = e.target.data('_scroll') == undefined
					? parseInt(e.target.css('margin-top').replace('px'))
					: (e.current.y - parseInt(e.target.data('_scroll')));

				if (val < 0 || val > height) return;

				var frame = {
					name: e.target.attr('quark-slider-name'),
					range: elem,
					slider: e.target,
					value: (val / height) * 100
				};

				opt.scroll(frame);

				e.target.css('margin-top', val + 'px');
				e.target.data('_scroll', e.current.y - val);
			}
		});
	});
};