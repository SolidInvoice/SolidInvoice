/*
 * This file is part of SolidInvoice package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(
    ['jquery', 'core/module', 'backbone', 'core/alert', 'routing', 'translator', 'client/view/info', './credit', './contacts', 'client/view/address_collection', 'client/model/address_collection'],
    function($, Module, Backbone, Alert, Routing, __, InfoView, ClientCredit, ClientContact, AddressView, AddressCollection) {
        'use strict';

        return Module.extend({
            regions: {
                'clientCredit': '#client-credit',
                'clientInfo': '#client-info'
            },
            _renderCredit: function(options) {
                this.app.showChildView('clientCredit', ClientCredit.getView(options));
            },
            _renderContactCollection: function(layoutView, options) {
                layoutView.renderContactsRegion(ClientContact.getView(options));
            },
            _renderClientAddresses: function(layoutView, options) {
                var addressCollection = new AddressCollection(options.addresses, {'id': options.id});

                var that = this;

                addressCollection.on('remove', function() {
                    addressCollection
                        .fetch({reset: true, url: Routing.generate('_xhr_clients_address_list', {'id': options.id})})
                        .done(function() {
                            layoutView.model.set('addresses', addressCollection.toArray());
                            options.addresses = layoutView.model.get('addresses');

                            layoutView.render();
                            that._renderContactCollection(layoutView, options);
                            if (addressCollection.length > 0) {
                                layoutView.getRegion('clientAddress').show(new AddressView({collection: addressCollection}));
                            }
                        }
                    );
                });

                if (addressCollection.length > 0) {
                    layoutView.getRegion('clientAddress').show(new AddressView({collection: addressCollection}));
                }
            },
            _renderClientInfo: function(options) {
                var infoView = new InfoView({
                    model: new Backbone.Model(options)
                });

                this.app.showChildView('clientInfo', infoView);

                return infoView;
            },
            initialize: function(options) {
                this._renderCredit(options);

                var infoView = this._renderClientInfo(options);
                this.render(infoView, options);

                $('#delete-client').on('click', this.deleteClient);
            },
            render: function(infoView, options) {
                this._renderContactCollection(infoView, options);
                this._renderClientAddresses(infoView, options);
            },
            deleteClient: function(event) {
                event.preventDefault();

                var link = $(this);

                Alert.confirm(__('client.confirm_delete'), function(confirm) {
                    if (true === confirm) {
                        $('body').modalmanager('loading');

                        return $.ajax({
                            "url": link.attr("href"),
                            "dataType": "json",
                            "method": "delete"
                        }).done(function() {
                            window.document.location = Routing.generate("_clients_index");
                        });
                    }
                });
            }
        });
    }
);