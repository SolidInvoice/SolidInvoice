define(['core/billing/view/discount'], function (Discount) {
    return Discount.extend({
	ui: {
	    discount: '#quote_discount'
	}
    });
});