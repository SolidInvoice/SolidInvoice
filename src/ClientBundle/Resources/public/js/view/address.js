define(
    ['core/view', './address_modal', 'template', 'core/alert', 'translator'],
    function(ItemView, AddressModal, Template, Alert, __) {
        'use strict';

        return ItemView.extend({
            template: Template.client.address,

            ui: {
                'deleteAddress': '.delete-address',
                'editAddress': '.edit-address'
            },

            events: {
                "click @ui.deleteAddress": 'deleteAddress',
                "click @ui.editAddress": 'editAddress'
            },

            initialize: function() {
                this.listenTo(this.model, 'sync', this.modelSynced);
            },

            modelSynced: function() {
                this.render();
            },

            deleteAddress: function(event) {
                event.preventDefault();

                let view = this;

                Alert.confirm(__('client.address.delete_confirm'), function(confirm) {
                    if (true === confirm) {
                        return view.model.destroy(
                            {
                                wait: true,
                                error: function(model, xhr) {
                                    Alert.alert(xhr.responseJSON.message);
                                }
                            }
                        );
                    }
                });
            },

            editAddress: function(event) {
                event.preventDefault();

                this.trigger('before:model:edit');

                new AddressModal({
                    model: this.model,
                    route: this.$(event.currentTarget).prop('href')
                });

                this.trigger('model:edit');
            }
        });
    });