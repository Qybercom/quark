var Quark = {
    EventValidateError: 'quark.validation.error',
    EventSubmitError: 'quark.submit.error',
    EventSubmitSuccess: 'quark.submit.success'
};

Quark.Event = function (events) {
    var that = this, i = 0;

    that._events = {};

    while (i < events.length) {
        that._events[event[i]] = [];

        i++;
    }

    that.On = function (name, callback) {
        if (!(callback instanceof Function)) return false;
        if (!(that._events[name] instanceof Array)) return false;

        that._events[name].push(callback);

        return true;
    };

    that.Off = function (name, callback) {

    };

    that.Dispatch = function (name, args) {
        if (!(that._events[name] instanceof Array)) return false;

        var i = 0;

        while (i < that._events[name].length) {
            that._events[name][i](args);

            i++;
        }
    };
};

/**
 * @param data
 * @constructor
 */
Quark.Model = function (data) {
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
        $(document).on('submit', selector, function () {
            if (handlers.beforeValidate instanceof Function)
                handlers.beforeValidate($(this));

            var form = $(this), data = Quark._form(selector);

            if (data === false) return false;

            form.notice = {
                success: form.find('.q-notice.success'),
                error: form.find('.q-notice.error')
            };

            var defaults = {
                success: form.notice.success.html(),
                error: form.notice.error.html()
            };

            if (handlers.beforeSubmit instanceof Function)
                data = handlers.beforeSubmit(form, data) || data;

            Quark.Request(form.attr('method'), form.attr('action'), data, handlers, form);

            form.notice.success.html(defaults.success);
            form.notice.error.html(defaults.error);

            return false;
        });
    };

    /**
     * @param frame
     * @param template
     */
    that.Frame = function (frame, template) {
        if (!that._templates[template])
            that._templates[template] = new Quark.Template(template);

        that._templates[template].Tags = that.Data;

        $(frame).html(that._templates[template].Compile());
    };
};

Quark.Request = function (method, url, data, handlers, additional) {
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

Quark.Get = function (url, data, handlers) {
    Quark.Request('GET', url, data, handlers);
};

Quark.Post = function (url, data, handlers) {
    Quark.Request('POST', url, data, handlers);
};

Quark._form = function (selector) {
    var input = $(selector).find('input,select,textarea'), output = {}, i = 0;

    while (i < input.length) {
        input.eq(i).trigger('input');

        if (input.eq(i).attr('q-valid') == 'false') return false;

        output = Quark._structure(output, Quark._tree(input.eq(i).attr('name')), input.eq(i).val());

        i++;
    }

    return output;
};

Quark._structure = function (gate, tree, value, i) {
    i = i || 0;

    if (gate[tree[i]] == undefined) gate[tree[i]] = [];

    if ((i + 1) < tree.length) gate[tree[i]] = Quark._structure(gate[tree[i]], tree, value, i+1);
    else {
        if (tree.isArray) gate[tree[i]].push(value);
        else gate[tree[i]] = value;
    }

    return gate;
};

Quark._tree = function (key) {
    var tree = key.split(/\[(.?)\]/gim), i = 0, spaces = 0, output = [];

    while (i < tree.length) {
        if (tree[i].length == 0) spaces++;
        else output.push(tree[i]);

        i++;
    }

    output.isArray = output.length < spaces;

    return output;
};

Quark.Validator = function (handlers) {
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

Quark.Validator.Required = function (field) {
    return field.val().length != 0;
};

Quark.Validator.Email = function (field) {
    var regex = new RegExp('(.*)@(.*)');

    return regex.test(field.val());
};

Quark.Validator.Date = function (field) {
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
Quark.Template = function (selector, tags) {
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
                : content.replace(new RegExp('{' + append + '}', 'gim'), tags[key].toString());
        }

        return content;
    };
};