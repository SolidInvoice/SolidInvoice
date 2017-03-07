define(
    ['marionette', './address'],
    function(Mn, AddressView) {
        'use strict';

        return Mn.CollectionView.extend({
            childView: AddressView,

            initialize : function () {
                this.listenTo(this.collection, 'remove', this.render);
                this.listenTo(this.collection, 'add', this.render);
            }
        });
    });