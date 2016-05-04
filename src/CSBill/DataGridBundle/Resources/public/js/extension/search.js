define(['template', 'grid/backgrid-filter'], function (Template) {
    return Backgrid.Extension.ServerSideFilter.extend({
	template: Template['grid/search'],
	name: "q"
    });
});