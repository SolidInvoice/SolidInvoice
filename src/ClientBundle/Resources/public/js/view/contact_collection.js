define(
    ['marionette', './contact'],
    function(Mn, ContactView) {
        'use strict';

        return Mn.CollectionView.extend({
            childView: ContactView
        });
    });