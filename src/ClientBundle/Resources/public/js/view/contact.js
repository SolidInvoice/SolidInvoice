define(
    ['core/view', './contact_modal', 'template', 'core/alert', 'translator'],
    function(ItemView, ContactModal, Template, Alert, __) {
        'use strict';

        return ItemView.extend({
            template: Template.client.contact,

            templateContext: function() {
                return {
                    'canDelete': this.model.collection.length > 1
                };
            },

            ui: {
                'deleteContact': '.delete-contact',
                'editContact': '.edit-contact'
            },

            events: {
                "click @ui.deleteContact": 'deleteContact',
                "click @ui.editContact": 'editContact'
            },

            initialize: function() {
                this.listenTo(this.model, 'sync', this.modelSynced);
            },

            modelSynced: function() {
                this.render();
            },

            deleteContact: function(event) {
                event.preventDefault();

                var view = this;

                Alert.confirm(__('client.contact.delete_confirm'), function(confirm) {
                    if (true === confirm) {
                        return view.model.destroy(
                            {
                                wait: true,
                                error: function(model, xhr) {
                                    Alert.alert(xhr.responseJSON);
                                }
                            }
                        );
                    }
                });
            },

            editContact: function(event) {
                event.preventDefault();

                this.trigger('before:model:edit');

                new ContactModal({
                    model: this.model,
                    route: this.$(event.currentTarget).prop('href')
                });

                this.trigger('model:edit');
            }
        });
    });