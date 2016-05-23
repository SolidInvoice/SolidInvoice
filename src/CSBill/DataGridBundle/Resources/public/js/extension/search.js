define(['template', 'grid/backgrid-filter'], function(Template) {
    return Backgrid.Extension.ServerSideFilter.extend({
        template: Template.datagrid.search,
        name: "q"
    });
});