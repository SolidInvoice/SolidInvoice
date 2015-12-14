define(['marionette', 'core/ajaxmodal'], function(Mn, AjaxModal) {
    "use strict";

    return Mn.ItemView.extend({
        onShow  : function() {
            this._onRender();
        },
        onRender: function() {
            this._onRender();
        },
        _onRender: function() {
            var tooltip = this.$('*[rel=tooltip]');
            if (tooltip.length) {
                require(['bootstrap'], function() {
                    tooltip.tooltip();
                });
            }
        },
        ajaxModal: function(event) {
            event.preventDefault();

            var ajaxModel = new AjaxModal({
                model: this.model,
                el   : this.$el
            });

            return ajaxModel.load(event.target);
        }
    });
});
