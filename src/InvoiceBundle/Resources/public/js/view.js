define(['template', 'core/billing/view/base'], function(Template, BaseView) {
    return BaseView.extend({
	template: Template.invoice.table,
	childViewContainer: "#invoice-rows"
    });
});