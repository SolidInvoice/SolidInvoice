(function($) {
    "use strict";

    var AjaxModal = function(modal, trigger, route, callback) {
        this.$modal     = modal;
        this.$route     = route;
        this.$callback  = callback;
        this.$trigger   = trigger;

        var parent = this;

        // if we don't have a valid route, just return
        if (!route) {
            return;
        }

        trigger.on('click', function(evt) {
            evt.preventDefault();

            // create the backdrop and wait for next modal to be triggered
            $('body').modalmanager('loading');

            var modalLoader = parent.$modal;

            $.getJSON(parent.$route, function(data) {

                if (data.content !== undefined) {
                    modalLoader.html(data.content);
                }

                parent.$modal.modal();

                if (undefined !== parent.$callback && $.isFunction(parent.$callback)) {
                    parent.$callback.apply(parent);
                }
            });
        });
    };

    $.fn.ajaxModal = function(modal, route, callback) {
        var $modal = $(modal);

        $(this).each(function() {

            var trigger = $(this);

            if ($.isFunction(route) && callback === undefined) {
                this.callback = route;
                if (trigger.data('target')) {
                    this.route = trigger.data('target');
                } else if (trigger.attr('href')) {
                    this.route = trigger.attr('href');
                }
            } else {
                this.route = route;
                this.callback = callback;
            }

            return new AjaxModal($modal, trigger, this.route, this.callback);
        });
    };
})(window.jQuery);