/**
 * JS part of SaaS PHP framework
 *
 * @type {Quark}
 */
var Quark = Quark || {};

/**
 * Quark.MVC namespace
 */
Quark.MVC = {};


/**
 * @param data
 * @constructor
 */
Quark.MVC.Model = function (data) {
	var that = this;

	that._templates = {};

	that._events = new Quark.Event([
		Quark.EventValidateError,
		Quark.EventSubmitError,
		Quark.EventSubmitSuccess
	]);

	that.On = that._events.On;
	that.Data = data || {};

	/**
	 * @param selector
	 * @param handlers
	 */
	that.Form = function (selector, handlers) {
        if (Quark.MVC.Model._selectors.indexOf(selector) + 1) return;

		if (!$(selector).is('form'))
			console.warn('[Quark.MVC.Model]' + ' ' + 'Specifying form handlers for a non-form selector "' + selector + '" does not have sense');

        handlers = Quark.Extend(handlers, {
            beforeValidate: false,
            beforeSubmit: false,
            afterSubmit: false,
            beforeAction: false,
            afterAction: false
        });

        Quark.MVC.Model._selectors.push(selector);

        $(document).on('submit', selector, function (e) {
            e.preventDefault();

            if (handlers.beforeValidate instanceof Function)
				handlers.beforeValidate($(this));

			var form = $(this),
                data = Quark.MVC._form($(this));

			if (data === false) return false;

			if (handlers.beforeSubmit instanceof Function)
				data = handlers.beforeSubmit(form, data) || data;

			Quark.MVC.Request(form.attr('method'), form.attr('action'), data, handlers, form);

            if (handlers.afterSubmit instanceof Function)
                handlers.afterSubmit(form, data);

			return false;
		});

        $(document).on('click', selector + ' .quark-mvc-action', function (e) {
            e.preventDefault();

            var button = $(this);
            var method = 'GET';

            if (handlers.beforeAction instanceof Function)
                method = handlers.beforeAction(that, button) || method;

            Quark.MVC.Request(method, button.attr('href'), data, handlers);

            if (handlers.afterAction instanceof Function)
                handlers.afterAction(that, button);

            return false;
        });
	};

	/**
	 * @param template
     *
     * @return string
	 */
	that.Map = function (template) {
		if (!that._templates[template])
			that._templates[template] = new Quark.MVC.Template(template);

		that._templates[template].Tags = that.Data;

		return that._templates[template].Compile();
	};
};

Quark.MVC.Model._selectors = [];

/**
 * @param method
 * @param url
 * @param data
 * @param handlers
 * @param additional
 */
Quark.MVC.Request = function (method, url, data, handlers, additional) {
	handlers = handlers || {};
	handlers.unknown = handlers.unknown instanceof Function
		? handlers.unknown
		: function () {};

	$.ajax({
		url: url,
		method: method,

		data: data,
		dataType: 'json',

		error: function (response) {
			if (handlers.error instanceof Function)
				handlers.error(response, additional);
		},
		success: function (response) {
			if (handlers[response.status] instanceof Function)
				handlers[response.status](response, additional);
			else handlers.unknown(response, additional);
		}
	});
};

/**
 * @param url
 * @param data
 * @param handlers
 */
Quark.MVC.Get = function (url, data, handlers) {
	Quark.MVC.Request('GET', url, data, handlers);
};

/**
 * @param url
 * @param data
 * @param handlers
 */
Quark.MVC.Post = function (url, data, handlers) {
	Quark.MVC.Request('POST', url, data, handlers);
};

/**
 * @param selector
 * @return {*}
 * @private
 */
Quark.MVC._form = function (selector) {
	var input = $(selector).find('input,select,textarea'), output = {}, i = 0;

	while (i < input.length) {
		input.eq(i).trigger('input');

		if (input.eq(i).attr('q-valid') == 'false') return false;

		output = Quark.MVC._structure(output, Quark.MVC._tree(input.eq(i).attr('name')), input.eq(i).val());

		i++;
	}

	return output;
};

/**
 * @param gate
 * @param tree
 * @param value
 * @return {*}
 * @private
 */
Quark.MVC._structure = function (gate, tree, value, i) {
	i = i || 0;

	if (gate[tree[i]] == undefined) gate[tree[i]] = [];

	if ((i + 1) < tree.length) gate[tree[i]] = Quark.MVC._structure(gate[tree[i]], tree, value, i+1);
	else {
		if (tree.isArray) gate[tree[i]].push(value);
		else gate[tree[i]] = value;
	}

	return gate;
};

/**
 * @param key
 * @return {Array}
 * @private
 */
Quark.MVC._tree = function (key) {
    key = key || '';

	var tree = key.split(/\[(.?)\]/gim), i = 0, spaces = 0, output = [];

	while (i < tree.length) {
		if (tree[i].length == 0) spaces++;
		else output.push(tree[i]);

		i++;
	}

	output.isArray = output.length < spaces;

	return output;
};

/**
 * @param handlers
 * @constructor
 */
Quark.MVC.Validator = function (handlers) {
	handlers = handlers || {};

	var that = this;

	that.ok = handlers.ok;
	that.error = handlers.error;

	that._callbacks = {
		false: that.error,
		true: that.ok
	};

	that.Rule = function (element, rule, message) {
		if (element == undefined || !(rule instanceof Function)) return;

		var field = $(element), ok = rule(field);

		if (that._callbacks[ok] instanceof Function)
			that._callbacks[ok](field, message);

		field.attr('q-valid', ok);
	};
};

/**
 * @param field
 * @return {boolean}
 */
Quark.MVC.Validator.Required = function (field) {
	return field.val().length != 0;
};

/**
 * @param field
 * @return {boolean}
 */
Quark.MVC.Validator.Email = function (field) {
	var regex = new RegExp('(.*)@(.*)');

	return regex.test(field.val());
};

/**
 *
 * @param field
 * @return {boolean}
 */
Quark.MVC.Validator.Date = function (field) {
	return Date.parse(field.val()) != NaN;
};


/**
 * Client-side template engine
 *
 * @author Alex Furnica
 * @version 1.0.3
 *
 * @param selector
 * @param tags
 *
 * @constructor
 */
Quark.MVC.Template = function (selector, tags) {
	var that = this;

	that.Tags = tags || {};

	that.elem = $(selector);
	that.elem.css('display', 'none');

	that._content = that.elem.html();

	/**
	 * @param key
	 * @param value
	 */
	that.Tag = function (key, value) {
		that.Tags[key] = value;
	};

	/**
	 * @return {*}
	 */
	that.Compile = function () {
		return that._compile(that.Tags, that._content);
	};

	/**
	 * @param tags
	 * @param content
	 * @param prefix
	 *
	 * @return {*}
	 *
	 * @private
	 */
	that._compile = function (tags, content, prefix) {
		if (content == undefined) return;

		var append = '';

		for (key in tags) {
			if (tags[key] == undefined) continue;
            append = (prefix ? prefix : '') + key;

			content = tags[key].constructor == Object
				? that._compile(tags[key], content, append + '.')
				: content.replace(new RegExp('{' + that._escape(append) + '}', 'gim'), tags[key].toString());
		}

		return content;
	};

    /**
     * http://stackoverflow.com/a/6969486
     * @private
     */
    that._escape = function (str) {
        return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
    };
};