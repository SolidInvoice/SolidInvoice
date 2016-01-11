/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(
    ['jquery', 'core/module', 'backbone', 'bootstrap.bootbox', 'routing', 'translator', 'csbillclient/js/view/info', 'csbillclient/js/credit', 'csbillclient/js/contacts', 'csbillclient/js/view/address_collection'],
    function($, Module, Backbone, Bootbox, Routing, __, InfoView, ClientCredit, ClientContact, AddressView) {
        'use strict';

        return Module.extend({
            regions: {
                'clientCredit': '#client-credit',
                'clientInfo': '#client-info'
            },
            _renderCredit: function(options) {
                this.app.getRegion('clientCredit').show(ClientCredit.getView(options));
            },
            _renderContactCollection: function(layoutView, options) {
                layoutView.renderContactsRegion(ClientContact.getView(options));
            },
            _renderClientAddresses: function(layoutView, options) {
                try {
                    layoutView.getRegion('clientAddress').show(new AddressView({
                        collection: new Backbone.Collection(options.addresses)
                    }));
                } catch (e) {
                }
            },
            _renderClientInfo: function(options) {
                var infoView = new InfoView({
                    model: new Backbone.Model(options)
                });

                this.app.getRegion('clientInfo').show(infoView);

                return infoView;
            },
            initialize: function(options) {
                this._renderCredit(options);

                var infoView = this._renderClientInfo(options);

                this._renderContactCollection(infoView, options);
                this._renderClientAddresses(infoView, options);

                $('#delete-client').on('click', this.deleteClient);
            },
            deleteClient: function (event) {
                event.preventDefault();

                var link = $(this);

                Bootbox.confirm(__('client.confirm_delete'), function (confirm) {
                    if (true === confirm) {
                        $('body').modalmanager('loading');

                        $.ajax({
                            "url" : link.attr("href"),
                            "dataType" : "json",
                            "method" : "post"
                        }).done(function() {
                            window.document.location = Routing.generate("_clients_index");
                        });
                    }
                });
            }
        });
    }
);