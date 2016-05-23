define(
    ['core/view', 'template'],
    function(ItemView, Template) {
        'use strict';

        return ItemView.extend({
            template: Template.client.address
        });
    });