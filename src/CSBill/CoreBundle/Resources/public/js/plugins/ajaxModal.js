(function($) {
    "use strict";

    var AjaxModal = function(modal, trigger, route, callback) {
        //this.$content   = null;
        this.$modal     = modal;
        this.$route     = route;
        this.$callback  = callback;

        var parent = this;

        $(trigger).on('click', function(evt) {
            evt.preventDefault();

            //if (null === parent.$content) {
                // create the backdrop and wait for next modal to be triggered
                $('body').modalmanager('loading');

                var modalLoader = parent.$modal;

                /*if (parent.$modal.find('.modal-body').length > 0) {
                    modalLoader = $('.modal-body', parent.$modal);
                }*/

                modalLoader.load(parent.$route, function() {
                    //parent.$content = content;
                    parent.$modal.modal();

                    if(undefined !== parent.$callback && $.isFunction(parent.$callback)) {
                        parent.$callback.apply(this);
                    }
                });
            /*} else {
                parent.$modal.modal();
            }*/
        });
    }

    $.fn.ajaxModal = function(trigger, route, callback) {

        if($.isFunction(route) && callback === undefined) {
            callback = route;
            if($(this).data('target')) {
               route = $(this).data('target');
            }
        }

        var $modal = $(this);

        $(trigger).each(function(){
            return new AjaxModal($modal, $(this), route, callback);
        });
    }
})(window.jQuery);