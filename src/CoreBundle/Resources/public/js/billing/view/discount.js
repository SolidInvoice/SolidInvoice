define(['marionette'], function (Mn) {
    return Mn.ItemView.extend({
	el: '#discount',
	events: {
	    'keyup @ui.discount': 'setDiscount'
	},
	setDiscount: function (event) {
	    this.model.set('total', $(event.target).val());

	    this.getOption('collection').trigger('change');
	},
	initialize: function () {
	    this.setDiscount({target: this.ui.discount});
	}
    });
});