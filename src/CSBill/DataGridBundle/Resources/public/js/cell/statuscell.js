define(['backgrid', 'lodash', 'status_labels'], function (Backgrid, _, Labels) {

    var statusCell = function (name) {
	return Backgrid.Cell.extend({
	    render: function () {
		this.$el.empty();
		var rawValue = this.model.get(this.column.get('name'));
		var formattedValue = this.formatter.fromRaw(rawValue, this.model);
		this.$el.append(Labels[name][formattedValue]);
		this.delegateEvents();
		return this;
	    }
	});
    };

    _.each(Labels, function (values, name) {
	var cellName = _.startCase(name) + '_statusCell';
	Backgrid[cellName] = statusCell(name);
    });
});