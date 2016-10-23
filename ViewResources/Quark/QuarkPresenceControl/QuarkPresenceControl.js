$(function () {
	var menu = $('#presence-menu-side-parent');
	menu.after('<a class="quark-button fa fa-bars" id="presence-menu-side-controller"></a>');

	if ($(document).width() < 400) {
		QuarkPresenceControl.MenuSideController.Close(true);
		$('#presence-menu-side-controller')
			.css('display', 'inline-block')
			.fadeIn(0);
	}
});

var QuarkPresenceControl = QuarkPresenceControl || {};

/**
 * QuarkPresenceControl side menu controller
 */
QuarkPresenceControl.MenuSideController = function () {
	var menu = $('#presence-menu-side-parent');

	if (menu.data('_closed')) QuarkPresenceControl.MenuSideController.Open();
	else QuarkPresenceControl.MenuSideController.Close();
};

QuarkPresenceControl.MenuSideController.Opened = true;

/**
 *
 * QuarkPresenceControl open side menu
 */
QuarkPresenceControl.MenuSideController.Open = function () {
	QuarkPresenceControl.MenuSideController.Opened = true;

	var menu = $('#presence-menu-side-parent');
		menu.data('_closed', false);
		menu.animate({'margin-top': '5px'}, 500);
};

/**
 * QuarkPresenceControl close side menu
 */
QuarkPresenceControl.MenuSideController.Close = function (fast) {
	QuarkPresenceControl.MenuSideController.Opened = false;

	var menu = $('#presence-menu-side-parent');
		menu.data('_closed', true);
		menu.animate({'margin-top': '-' + menu.outerHeight() + 'px'}, fast ? 0 : 500);
};

/**
 * QuarkPresenceControl toggle side menu
 */
QuarkPresenceControl.MenuSideController.Toggle = function () {
	if ($(document).width() > 400) {
		if (QuarkPresenceControl.MenuSideController.Opened) return;

		QuarkPresenceControl.MenuSideController.Open();
		$('#presence-menu-side-controller').fadeOut();
	}
	else {
		if (!QuarkPresenceControl.MenuSideController.Opened) return;

		QuarkPresenceControl.MenuSideController.Close();
		$('#presence-menu-side-controller').fadeIn();
	}
};

$(document).on('click', '#presence-menu-side-controller', QuarkPresenceControl.MenuSideController);
$(window).on('resize', QuarkPresenceControl.MenuSideController.Toggle);