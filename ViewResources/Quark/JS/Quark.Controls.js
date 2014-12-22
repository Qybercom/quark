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

			html +='<a class="qui-button block"><span style="display:none;">' + opt.values[key] + '</span>' + key + '</a>';

			i++;
		}

		if (!opt.default)
			selected = '<a class="qui-button block"><span style="display:none;">' + opt.values[selected] + '</span>' + selected + '</a>';

		$(this).append(selected + '<span class="qui-tool-group">' + html + '</span>');
	});

	that.Tool = function (html) {
		that.Elem.append(html);
	};

	that.Elem.on('click', function (e) {
		that.Elem.children('.qui-tool-group').css({
			display: 'block'
		});

		e.stopPropagation();
	});

	that.Elem.children('.qui-tool-group').on('click', function (e) {
		that.Elem.children('.qui-tool-group').css({
			display: 'none'
		});

		e.stopPropagation();
	});
};

Quark.Controls.File = function () {

};

Quark.Controls.File.To = function (url, name, opt) {
	opt = Quark.Extend(opt, {
		multiple: false,
		beforeSelect: function (elem) {},
		beforeSubmit: function (elem) {},
		success: function (response) {},
		error: function (response) {}
	});

	var key = '-ggg';

	if ($('#target' + key).length == 0)
		$('body').append(
			'<iframe id="target' + key + '" name="target' + key + '" style="display: none;"></iframe>' +
			'<form id="form' + key + '" action="' + url + '" target="target' + key + '" method="POST" enctype="multipart/form-data" style="display: none;">' +
				'<input type="file" name="' + name + '" />' +
			'</form>'
		);

	var frame = $('#target' + key);
	var form = $('#form' + key);

	frame.on('load', function (e) {
		//opt.success($(this).html());
		console.log('[ ok ]', $(this).contents().text());
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