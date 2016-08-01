define(
    ['core/view', 'template', 'bootstrap.bootbox', 'translator'],
    function(ItemView, Template, Bootbox, __) {
        'use strict';

        return ItemView.extend({
            template: Template.client.address,
            ui: {
                'deleteAddress': '.delete-address',
                //'editAddress': '.edit-address'
            },

            events: {
                "click @ui.deleteAddress": 'deleteAddress',
                //"click @ui.editAddress": 'editAddress'
            },

            initialize: function() {
                this.listenTo(this.model, 'sync', this.modelSynced);
            },

            modelSynced: function() {
                this.render();
            },
            deleteAddress: function(event) {
                event.preventDefault();

                var view = this;

                Bootbox.confirm(__('client.address.delete_confirm'), function(confirm) {
                    if (true === confirm) {
                        view.model.destroy(
                            {
                                wait: true,
                                error: function(model, xhr) {
                                    Bootbox.alert(xhr.responseJSON);
                                }
                            }
                        );
                    }
                });
            },

            /*editAddress: function (event) {
             event.preventDefault();

             this.trigger('before:model:edit');

             new AddressModal({
             model: this.model,
             route: this.$(event.currentTarget).prop('href')
             });

             this.trigger('model:edit');
             }*/
        });
    });