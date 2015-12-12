define(['core/view', 'marionette'], function(App, Mn) {
    return Mn.ItemView.extend({
        onShow: function(view, region, options) {
            var tooltip = $('*[rel=tooltip]');
            if (tooltip.length) {
                require(['bootstrap'], function () {
                    tooltip.tooltip();
                });
            }
        }
    });
});
