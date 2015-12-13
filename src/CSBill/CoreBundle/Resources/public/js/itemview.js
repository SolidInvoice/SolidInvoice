define(['marionette', 'core/ajaxmodal'], function(Mn, AjaxModal) {
    return Mn.ItemView.extend({
        onShow: function(view, region, options) {
            var tooltip = $('*[rel=tooltip]');
            if (tooltip.length) {
                require(['bootstrap'], function () {
                    tooltip.tooltip();
                });
            }
        },
        ajaxModel: function (event) {
            event.preventDefault();

            var ajaxModel = new AjaxModal({
                model: this.model,
                el: this.$el
            });

            ajaxModel.load(event.target);
        }
    });
});
