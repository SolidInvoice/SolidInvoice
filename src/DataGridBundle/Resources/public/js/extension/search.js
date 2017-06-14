define(['backgrid', 'backgrid-filter'], function(Backgrid) {
    return Backgrid.Extension.ServerSideFilter.extend({
        template: require('../../templates/search.hbs'),
        name: "q"
    });
});