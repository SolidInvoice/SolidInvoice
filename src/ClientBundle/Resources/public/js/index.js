define(['grid/multiple_grid', 'backbone'], function (Grid, Backbone) {
    return new Grid({
        'model': new Backbone.Model({'grids': ['active_client_grid', 'archive_client_grid']}),
        'el': '#client-grid'
    });
});