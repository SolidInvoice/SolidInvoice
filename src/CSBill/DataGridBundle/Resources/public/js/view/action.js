define(['core/view', 'jquery', 'template', 'lodash', 'routing', 'bootstrap.bootbox'], function(ItemView, $, Template, _, Routing, Bootbox) {
    return ItemView.extend({
	tagName: 'span',
	template: Template['grid/action'],
	ui: {
	    button: ".btn"
	},
	events: {
	    'click @ui.button': 'confirm'
	},
	confirm: function() {
	    if (_.isEmpty(this.getOption('grid').getSelectedModels())) {
		return Bootbox.alert('You need to select at least one row');
	    }

	    if (!_.isEmpty(this.model.get('confirm'))) {
		var view = this;
		Bootbox.confirm(this.model.get('confirm'), function(response) {
		    if (true === response) {
			view._executeAction();
		    }
		});
	    } else {
		this._executeAction();
	    }
	},
	_executeAction: function() {
	    var grid = this.getOption('grid');

	    this.showLoader();

	    var models = _.map(
		grid.getSelectedModels(),
		function(model) {
		    return model.id;
		}),

		view = this,
		promise = $.ajax({
		    url: Routing.generate(this.model.get('action')),
		    data: {'data': models},
		    method: 'POST'
		});

	    promise.done(_.bind(function() {
		grid.collection.fetch({
		    success: function() {
			grid.clearSelectedModels();
			view.hideLoader();
		    }
		});
	    }, this));
	}
    })
});