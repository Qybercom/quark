$(function () {
	var menu = $('#presence-menu-side-parent');
	menu.after('<a class="quark-button fa fa-bars" id="presence-menu-side-controller"></a>');

	if ($(document).width() < 800) {
		QuarkPresenceControl.MenuSideController.Close(true);
		$('#presence-menu-side-controller')
			.css('display', 'inline-block')
			.fadeIn(0);

		var controller = new Quark.UX('#presence-menu-side-controller');
		controller.Drag({
			handle: '#presence-menu-side-controller',
			defaultCss: false,
			change: {y: 'margin-top'},
			axis: {x: false, y: true},
			drag: function (e) {
				var height = menu.outerHeight();
				QuarkPresenceControl.MenuSideController.Opened = e.position.y > (height / 2);

				menu.css('margin-top', '-' + (height - e.position.y) + 'px');
			},
			stop: function (e) {
				menu.css('margin-top', QuarkPresenceControl.MenuSideController.Opened
					? '5px'
					: '-' + menu.outerHeight() + 'px'
				);
			}
		});
	}
});

var QuarkPresenceControl = QuarkPresenceControl || {};

/**
 * QuarkPresenceControl side menu controller
 */
QuarkPresenceControl.MenuSideController = function () {
	var menu = $('#presence-menu-side-parent');

	if (QuarkPresenceControl.MenuSideController.Opened) QuarkPresenceControl.MenuSideController.Close();
	else QuarkPresenceControl.MenuSideController.Open();
};

QuarkPresenceControl.MenuSideController.Opened = true;

/**
 *
 * QuarkPresenceControl open side menu
 */
QuarkPresenceControl.MenuSideController.Open = function () {
	QuarkPresenceControl.MenuSideController.Opened = true;

	var menu = $('#presence-menu-side-parent');
		menu.animate({'margin-top': '5px'}, 500);
};

/**
 * QuarkPresenceControl close side menu
 */
QuarkPresenceControl.MenuSideController.Close = function (fast) {
	QuarkPresenceControl.MenuSideController.Opened = false;

	var menu = $('#presence-menu-side-parent');
		menu.animate({'margin-top': '-' + menu.outerHeight() + 'px'}, fast ? 0 : 500);
};

/**
 * QuarkPresenceControl toggle side menu
 */
QuarkPresenceControl.MenuSideController.Toggle = function () {
	if ($(document).width() > 800) {
		if (QuarkPresenceControl.MenuSideController.Opened) return;

		QuarkPresenceControl.MenuSideController.Open();
		$('#presence-menu-side-controller').fadeOut().css('display', 'none');
	}
	else {
		if (!QuarkPresenceControl.MenuSideController.Opened) return;

		QuarkPresenceControl.MenuSideController.Close();
		$('#presence-menu-side-controller').fadeIn().css('display', 'inline-block');
	}
};

$(document).on('click', '#presence-menu-side-controller', QuarkPresenceControl.MenuSideController);
$(window).on('resize', QuarkPresenceControl.MenuSideController.Toggle);