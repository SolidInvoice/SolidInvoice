define(['jquery', 'bootstrap.modal', 'bootstrap.modalmanager'], function ($) {
    "use strict";

    $.fn.modal.defaults.spinner = $.fn.modalmanager.defaults.spinner =
	'<div class="loading-spinner">' +
	    '<div class="progress progress-striped active">' +
		'<div class="progress-bar"></div>' +
	    '</div>' +
	'</div>';

    $.fn.modal.defaults.maxHeight = function() {
	// subtract the height of the modal header and footer
	return $(window).height() - 165;
    };

    return $.fn.modal;
});