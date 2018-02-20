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

	var out = '', end = '', key = '';

	for (key in data) {
		end = append === '' ? key : (append + '[' + key + ']');

		if (data[key] !== undefined && (data[key].constructor === Object || data[key].constructor === Array))
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
				that._message(form, data.status !== undefined && data.status === 200 ? '.ok' : '.warn');
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
		opt.box = opt.box === undefined ? '#quark-dialog-box' : opt.box;
		opt.reset = opt.reset !== undefined ? opt.reset : true;
		//opt.type = opt.type !== undefined ? opt.type : 'GET';
		opt.successCriteria = opt.successCriteria instanceof Function
			? opt.successCriteria
			: function (data) { return data.status !== undefined && data.status === 200; };

	var that = this;

	that.Reset = function (dialog) {
		dialog.find('.quark-dialog-state').slideUp();
		dialog.find('.quark-dialog-confirm').slideDown();
	};

	that.Wait = function (dialog) {
		dialog.find('.quark-dialog-state').slideUp();
		dialog.find('.quark-dialog-state.wait').slideDown();
	};

	that.Success = function (trigger, dialog, data) {
		dialog.find('.quark-dialog-state').slideUp();
		dialog.find('.quark-dialog-state.success').slideDown();
		dialog.find('.quark-dialog-confirm').slideUp();

		if (opt.success instanceof Function)
			opt.success(trigger, dialog, data);
	};

	that.Error = function (trigger, dialog, data) {
		if (data !== undefined && data !== null && data.errors instanceof Array) {
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

		if (opt.error instanceof Function)
			opt.error(trigger, dialog, data);
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

	that.Close = function (dialog, action, callback) {
		dialog = dialog || $($(selector).attr('quark-dialog'));

		if (opt.close instanceof Function && opt.close(dialog, action) === false) return;

		dialog.fadeOut(500);
		dialog.find('.quark-dialog-state').slideUp();
		$(opt.box).fadeOut(500);

		if (callback instanceof Function)
			callback(dialog, action);
	};

	that.Submit = function (dialog, action) {
		var _continue = !(opt.confirm instanceof Function) || opt.confirm(dialog, action, that);
		if (!_continue) return;

		$.ajax({
			url: action.attr('href'),
			dataType: 'json',
			type: opt.type || dialog.attr('method'),
			data: dialog.serialize(),

			beforeSend: function () {
				that.Wait(dialog);
			},

			success: function (data) {
				if (opt.successCriteria(data)) that.Success(dialog.data('button'), dialog, data);
				else that.Error(dialog.data('button'), dialog, data);
			},

			error: function () {
				that.Error(null, dialog, null);
			}
		});
	};

	that._id = Quark.GuID();

	$(function () {
		if ($(opt.box).length === 0)
			$('body').prepend('<div id="' + opt.box.replace('#', '') + '"></div>');

		$(opt.box).hide(0);

		$(selector).each(function () {
			var elem = $(this),
				dialog = $(elem.attr('quark-dialog'));

			dialog.data('quark-dialog-object', that);
		});

		$('.quark-dialog').each(function () {
			var dialog = $(this);

			dialog.data('quark-dialog-id', that._id);

			dialog.hide(0);

			dialog.appendTo(opt.box);
			dialog.find('.quark-dialog-state').hide(0);
		});
	});

	$(document).on('click', '.quark-dialog-confirm', function (e) {
		e.preventDefault();

		var action = $(this),
			dialog = action.parent('.quark-dialog');

		if (dialog.data('quark-dialog-id') !== that._id) return;

		var container = dialog.data('quark-dialog-object') || that;
		that.Submit(dialog, action);
	});

	$(document).on('click', '.quark-dialog-close', function (e) {
		e.preventDefault();

		var action = $(this),
			dialog = action.parent('.quark-dialog');

		if (dialog.data('quark-dialog-id') !== that._id) return;

		var container = dialog.data('quark-dialog-object') || that;
		that.Close(dialog, action);
	});

	$(document).on('click', selector, function (e) {
		e.preventDefault();

		var button = $(this),
			dialog = $(button.attr('quark-dialog')),
			action = function () { that.Open(dialog, button.attr('href'), button); };

		if (opt.call instanceof Function && opt.call(button, dialog) === false)
			return;

		if (button.attr('quark-dialog-exclusive') != 'true') action();
		else that.Close($('.quark-dialog'), null, function () {
			var timer = setTimeout(function () {
				action();
				clearTimeout(timer);
			}, 500);
		});
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
			if (i === 0)
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

	if ($(target).length === 0)
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

			if ($(this).val().length !== 0)
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
		opt.name = opt.name === undefined ? 'list' : opt.name;
		opt.placeholder = opt.placeholder === undefined ? '' : opt.placeholder;
		opt.prepend = opt.prepend === undefined ? false : opt.prepend;
		opt.preventDefault = opt.preventDefault === undefined ? false : opt.preventDefault;
		opt.item = opt.item === undefined
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
		opt.autoState = opt.autoState === undefined ? true : opt.autoState;
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

		if (elem.attr('disabled') === 'disabled')
			that.Available(elem, false);

		var name = opt.name === undefined
			? (elem.attr('name') || '')
			: opt.name;

		if (name === '') return;

		var input = elem.find('input[type="hidden"]');

		if (input.length === 0)
			input = elem
				.append('<input type="hidden" name="' + name + '" value="' + (enabled ? 'on' : 'off') + '" />')
				.find('input[type="hidden"]');

		input.val(enabled ? 'on' : 'off');
	};

	if (opt.enable !== undefined)
		that._attr(enabled, that.Elem);

	$(document).on('click', selector, function (e) {
		e.preventDefault();

		if ($(this).attr('disabled') !== 'disabled')
			that.Toggle($(this));
	});

	that.Elem.each(function () {
		var elem = $(this),
			val = elem.attr('quark-enabled'),
			enabled = val === 'true' || val === '1';

		elem.html(enabled ? opt.enabled.html : opt.disabled.html);
		that._attr(enabled, elem);
	});

	/**
	 * @param elem
	 */
	that.Toggle = function (elem) {
		var ok = elem.attr('quark-enabled') === 'true'
			? opt.enabled.action(that, elem)
			: opt.disabled.action(that, elem);

		if (opt.autoState && ok !== false) that.State(elem);
	};

	/**
	 * @param elem
	 */
	that.State = function (elem) {
		var enabled = elem.attr('quark-enabled') !== 'true';

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
		opt.box = opt.box === undefined ? '#quark-gallery-box' : opt.box;

	var that = this;

	$(function () {
		if ($(opt.box).length === 0)
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
		opt.asDefined = opt.asDefined !== undefined ? opt.asDefined : true;
		opt.ready = opt.ready || false;
		opt.change = opt.change !== undefined ? opt.change : function () {};
		opt.offset = opt.offset || 0;
		opt.rounder = opt.rounder instanceof Function ? opt.rounder : function (val) { return val; };

	that.Elem = $(selector);
	that.Elem.attr('type', 'hidden');

	that.Name = that.Elem.attr('name');
	that.Value = that.Elem.attr('value') === undefined ? 0 : parseFloat(that.Elem.attr('value'));
	that.Min = that.Elem.attr('min') === undefined ? 0 : parseFloat(that.Elem.attr('min'));
	that.Max = that.Elem.attr('max') === undefined ? 0 : parseFloat(that.Elem.attr('max'));

	that.Sliders = [];

	var _id = Quark.GuID();

	$('body').append('<div class="quark-range-slider" id="' + _id + '"></div>');

	var _m = $('#' + _id);
	var sliderWidth = _m.outerWidth();
	_m.remove();

	that.Elem.each(function () {
		var elem = $(this),
			range = that.Max - that.Min,
			sliders = elem.attr()
			.map(function (item) {
				if (item.name.indexOf('quark-slider-') === -1) return;

				return {
					name: item.name.replace('quark-slider-', ''),
					value: item.value
				};
			})
			.filter(function (item) {
				return item !== undefined;
			});

		if (sliders.length === 0 && opt.asDefined === true)
			sliders.push({
				name: that.Name,
				value: that.Value
			});

		elem = that.Elem.wrap('<div class="quark-input range"></div>').parent();
		elem.html('');

		var width = parseFloat(elem.width()) - sliderWidth, i = 0, margin = 0;
		while (i < sliders.length) {
			margin = ((sliders[i].value - that.Min) * width / range) + opt.offset;

			elem.append(
				'<div class="quark-range-progress" quark-slider-name="' + sliders[i].name + '" style="width: ' + (margin + sliderWidth) + 'px;"></div>' +
				'<div class="quark-range-regress" quark-slider-name="' + sliders[i].name + '" style="width: ' + (width - (margin)) + 'px; margin-left: ' + (margin + sliderWidth) + 'px;"></div>' +
				'<div class="quark-range-slider" quark-slider-name="' + sliders[i].name + '" style="margin-left: ' + margin + 'px;">' +
					'<input type="hidden" name="' + sliders[i].name + '" value="' + opt.rounder(sliders[i].value) + '" />' +
				'</div>' +
				'<br />'
			);

			if (opt.ready instanceof Function)
				opt.ready({
					name: sliders[i].name,
					range: elem,
					slider: elem.find('.quark-range-slider'),
					progress: ((margin - opt.offset) / width) * 100,
					value: opt.rounder(((margin - opt.offset) * range / width) + that.Min),
					val: margin - opt.offset
				});

			i++;
		}

		elem.append(
			'<div class="quark-range-value min">' + that.Min + '</div>' +
			'<div class="quark-range-value max">' + that.Max + '</div>'
		);

		var slide = new Quark.UX(elem.find('.quark-range-slider'));
		slide.Drag({
			axis: {y:false},
			delegateParent: false,
			defaultCss: false,
			drag: function (e) {
				if (!e.target.is('.quark-range-slider')) return;

				var val = e.target.data('_slide') === undefined
					? parseInt(e.target.css('margin-left').replace('px'))
					: (e.current.x - parseInt(e.target.data('_slide')));

				if (val < 0 || val > width) return;

				var frame = {
					name: e.target.attr('quark-slider-name'),
					range: elem,
					slider: e.target,
					progress: (val / width) * 100,
					value: opt.rounder((val * range / width) + that.Min),
					val: val
				};

				opt.change(frame);

				var offset = val + e.target.width() / 2 + 1;

				e.target.css('margin-left', (val + opt.offset) + 'px');
				e.target.data('_slide', e.current.x - val);
				e.target.find('input[type="hidden"]').val(opt.rounder(frame.value));
				e.target.parent().find('.quark-range-progress').css('width', offset + 'px');
				e.target.parent().find('.quark-range-regress').css({
					'margin-left': offset + 'px',
					width: (width + Math.ceil(sliderWidth / 2) - val + 1) + 'px'
				});
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
			catch (e) { return {}; }
		};

	opt = opt || {};
		opt.localize = opt.localize !== undefined ? opt.localize : false;
		opt.labels = opt.labels !== undefined ? opt.labels : {'*': 'Any'};

	/**
	 * @param {string} name
	 * @param {string=} postfix
	 *
	 * @return {string}
	 *
	 * @private
	 */
	that._name = function (name, postfix) {
		postfix = postfix || '';
		var i = name.lastIndexOf(']');

		return i === -1
			? (name + postfix)
			: name.substr(0, i) + postfix + ']';
	};

	/**
	 * @param {string} name
	 * @param {string=} postfix
	 *
	 * @return {string}
	 *
	 * @private
	 */
	that._nameQuery = function (name, postfix) {
		return '[name="' + that._name(name, postfix) + '"]';
	};

	/**
	 * @param elem
	 * @param {string} name
	 * @param {string=} postfix
	 *
	 * @return {string}
	 *
	 * @private
	 */
	that._field = function (elem, name, postfix) {
		return elem.parent().find(that._nameQuery(name, postfix));
	};

	that.Elem = $(selector);

	that.Elem.each(function () {
		var elem = $(this),
			val = elem.val(),
			language = '',
			languages = elem.attr('quark-languages'),
			selected = elem.attr('quark-language'),
			select = '<select class="quark-input quark-language-select" name="' + that._name(elem.attr('name'), '_language') + '">',
			json = _decode(val),
			i = 0;

		languages = languages === undefined ? [] : languages.split(',');

		if (languages.indexOf('*') === -1)
			languages.unshift('*');

		while (i < languages.length) {
			select += '<option value="' + languages[i] + '"' + (json[selected] !== undefined && languages[i] === selected ? ' selected="selected"' : '') + '>'
					+ (opt.labels[languages[i]] !== undefined ? opt.labels[languages[i]] : languages[i])
					+ '</option>';

			i++;
		}

		select += '</select>';

		var copy = elem.clone();
		copy
			.attr('name', that._name(copy.attr('name'), '_localized'))
			.val(json !== null && json[selected] !== undefined ? json[selected] : (json['*'] !== undefined ? json['*'] : ''));

		elem
			.css('display', 'none')
			.after(select)
			.after(copy);

		elem.parent()
			.find(that._nameQuery(elem.attr('name'), '_localized'))
			.on('change', function () {
				var localized = $(this),
					name = localized.attr('name').replace(/_localized(]?)$/, '$1'),
					orig = localized.parent().find('[name="' + name + '"]'),
					lang = that._field(orig, name, '_language'),
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
			name = lang.attr('name').replace(/_language(]?)$/, '$1'),
			orig = lang.parent().find('[name="' + name + '"]'),
			display = that._field(lang, orig.attr('name'), '_localized'),
			json = _decode(orig.val()),
			selected = lang.val(),
			val = json !== null && json[selected] !== undefined ? json[selected] : '';

		if (opt.localize instanceof Function)
			opt.localize(selected, val);

		display.val(val);
	});

	that.Localize = function (language, value, selector) {
		$(selector || that.Elem).each(function () {
			var elem = $(this),
				name = elem.attr('name'),
				lang = that._field(elem, name, '_language'),
				display = that._field(elem, name, '_localized'),
				json = _decode(elem.val());

			language = language || lang.val();

			if (value !== undefined) {
				json[language] = value;
				elem.val(_encode(json));
			}

			var val = json !== null && json[language] !== undefined ? json[language] : '';

			if (opt.localize instanceof Function && opt.localizeSelf)
				opt.localize(language, val);

			lang.val(language);
			display.val(val);
		});
	};
};

/**
 * @param selector
 * @param opt
 *
 * @constructor
 */
Quark.Controls.Progress = function (selector, opt) {
	opt = opt || {};

	var that = this;

	that.Elem = $(selector);

	that.Elem.each(function () {
		var elem = $(this),
			value = elem.attr('quark-progress');

		elem.append('<div class="quark-progress-mark" style="width: ' + (value.indexOf('%') != -1 ? value : value + '%') + '"></div>');
	});
};

/**
 * @param selector
 * @param opt
 *
 * @constructor
 */
Quark.Controls.Scrollable = function (selector, opt) {
	var that = this;

	opt = opt || {};
		opt.scroll = opt.scroll !== undefined ? opt.scroll : function () {};

	that.Elem = $(selector);

	that.Elem.each(function () {
		var elem = $(this);

		elem.css('overflow', 'hidden');
		elem = elem.wrap('<div class="quark-scrollable' + (opt.class !== undefined ? ' ' + opt.class : '') + '"' + (opt.id !== undefined ? ' id="' + opt.id + '"' : '') + '></div>').addClass('quark-scrollable-content').parent();
		elem.append('<div class="quark-scroll-bar"><div class="quark-scroll-trigger"></div></div>');

		var scroll_content = elem.find('.quark-scrollable-content'),
			scroll_bar = elem.find('.quark-scroll-bar'),
			scroll_trigger = elem.find('.quark-scroll-trigger'),
			height_content = scroll_content.height(),
			height_content_full = elem.find('.quark-scrollable-content >').height(),
			height_bar = (elem.height() / height_content_full) * 100;

		scroll_bar.css('height', height_content + 'px');
		scroll_trigger.css('height', height_bar + '%');

		elem.on('mousewheel', function (e) {
			var delta = parseInt(e.originalEvent.wheelDelta),
				dir = delta / Math.abs(delta) * -1,
				val = scroll_content.scrollTop() + dir * 40;

			scroll_content.scrollTop(val);
			scroll_bar.css('margin-top', scroll_content.scrollTop() / 2 + 'px');
		});

		var initial = 0;
		elem.on('touchstart', function (e) {
			e.preventDefault();

			initial = e.originalEvent.touches[0].pageY;
		});

		elem.on('touchmove', function (e) {
			e.preventDefault();

			scroll_content.scrollTop(initial - e.originalEvent.touches[0].pageY);
			scroll_bar.css('margin-top', scroll_content.scrollTop() / 2 + 'px');
		});

		var scroll = new Quark.UX(scroll_trigger);
		scroll.Drag({
			//axis: {x:false},
			//handle: selector,
			delegateParent: false,
			defaultCss: false,
			preventDefault: false,
			drag: function (e) {
				if (!e.target.is('.quark-scroll-trigger')) return;

				var val = e.target.data('_scroll') === undefined
					? parseInt(e.target.css('margin-top').replace('px'))
					: (e.current.y - parseInt(e.target.data('_scroll')));

				if (val < 0 || (val + scroll_trigger.height() - 25) > height_content) return;

				var frame = {
					name: e.target.attr('quark-slider-name'),
					range: elem,
					slider: e.target,
					value: (val / height_content) * 100
				};

				opt.scroll(frame);

				scroll_content.scrollTop(val);

				e.target.css('margin-top', scroll_content.scrollTop() / 1.15 + 'px');
				e.target.data('_scroll', e.current.y - val);
			}
		});
	});
};