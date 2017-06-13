define(
    ['core/itemview', './contact_modal', 'bootstrap.bootbox', 'translator'],
    function(ItemView, ContactModal, Bootbox, __) {
        'use strict';

        return ItemView.extend({
            template: require('../../templates/contact.hbs'),

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

                Bootbox.confirm(__('client.contact.delete_confirm'), function(confirm) {
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