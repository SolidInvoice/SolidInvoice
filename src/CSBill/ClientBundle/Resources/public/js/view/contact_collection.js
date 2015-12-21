define(
    ['marionette', './contact'],
    function(Mn, ContactView) {
        'use strict';

        return Mn.CollectionView.extend({
            childView: ContactView,

            initialize : function () {
                this.listenTo(this.collection, 'remove', this.render);
                this.listenTo(this.collection, 'add', this.render);
            }
        });
    });