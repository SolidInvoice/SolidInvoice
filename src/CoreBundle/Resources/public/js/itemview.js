define(['marionette', 'core/ajaxmodal', 'bootstrap.modalmanager'], function(Mn) {
    "use strict";

    return Mn.View.extend({
        constructor: function(options) {
            this.listenTo(this, 'render', function() {
                setTimeout(function() {
                    var select2 = this.$('select.select2');
                    if (select2.length) {
                        require(['jquery.select2'], function() {
                            select2.select2({
                                allowClear: true
                            });
                        });
                    }

                    var tooltip = this.$('*[rel=tooltip]');
                    if (tooltip.length) {
                        require(['bootstrap'], function() {
                            tooltip.tooltip();
                        });
                    }
                }, 0);
            });

            Mn.View.call(this, options);
        },
        showLoader: function() {
            return this.$el.modalmanager('loading');
        },
        hideLoader: function() {
            return this.$el.modalmanager('loading');
        }
    });
});
