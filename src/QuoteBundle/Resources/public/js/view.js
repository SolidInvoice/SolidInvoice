define(['template', 'core/billing/view/base'], function (Template, BaseView) {
    return BaseView.extend({
        template: Template.quote.table,
        childViewContainer: "#quote-rows"
    });
});