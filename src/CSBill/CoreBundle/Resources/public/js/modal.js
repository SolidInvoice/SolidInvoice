define(['jquery', 'bootstrap.modal', 'bootstrap.modalmanager'], function ($) {
    // @TODO: The styles for the loading spinner should go in a CSS file
    $.fn.modal.defaults.spinner = $.fn.modalmanager.defaults.spinner =
        '<div class="loading-spinner" style="width: 200px; margin-left: -100px;">' +
            '<div class="progress progress-striped active">' +
                '<div class="progress-bar" style="width: 100%;"></div>' +
            '</div>' +
        '</div>';

    $.fn.modal.defaults.maxHeight = function() {
        // subtract the height of the modal header and footer
        return $(window).height() - 165;
    };
});
