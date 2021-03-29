/*
 * This file is part of SolidInvoice package.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import $ from 'jquery';
import Module from 'SolidInvoiceCore/js/module';
import Backbone from 'backbone';
import Alert from 'SolidInvoiceCore/js/alert';
import Router from 'router';
import Translator from 'translator';
import InfoView from './view/info';
import ClientCredit from './credit';
import ClientContact from './contacts';
import AddressView from './view/address_collection';
import AddressCollection from './model/address_collection';

export default Module.extend({
    regions: {
        'clientCredit': '#client-credit',
        'clientInfo': '#client-info'
    },
    _renderCredit (options) {
        this.app.showChildView('clientCredit', ClientCredit(options));
    },
    _renderContactCollection (layoutView, options) {
        layoutView.renderContactsRegion(ClientContact(options));
    },
    _renderClientAddresses (layoutView, options) {
        const addressCollection = new AddressCollection(options.addresses, { 'id': options.id });

        addressCollection.on('remove', () => {
            addressCollection
                .fetch({ reset: true, url: Router.generate('_xhr_clients_address_list', { 'id': options.id }) })
                .done(() => {
                        layoutView.model.set('addresses', addressCollection.toArray());
                        options.addresses = layoutView.model.get('addresses');

                        layoutView.render();
                        this._renderContactCollection(layoutView, options);
                        if (0 < addressCollection.length) {
                            layoutView.getRegion('clientAddress').show(new AddressView({ collection: addressCollection }));
                        }
                    }
                );
        });

        if (0 < addressCollection.length) {
            layoutView.getRegion('clientAddress').show(new AddressView({ collection: addressCollection }));
        }
    },
    _renderClientInfo (options) {
        const infoView = new InfoView({
            model: new Backbone.Model(options)
        });

        this.app.showChildView('clientInfo', infoView);

        return infoView;
    },
    initialize (options) {
        this._renderCredit(options);

        const infoView = this._renderClientInfo(options);
        this.render(infoView, options);

        $('#delete-client').on('click', this.deleteClient);
    },
    render (infoView, options) {
        this._renderContactCollection(infoView, options);
        this._renderClientAddresses(infoView, options);
    },
    deleteClient (event) {
        event.preventDefault();

        const $link = $(this);

        Alert.confirm(Translator.trans('client.confirm_delete'), (confirm) => {
            if (true === confirm) {
                $('body').modalmanager('loading');

                return $.ajax({
                    'url': $link.attr('href'),
                    'dataType': 'json',
                    'method': 'delete'
                }).done(() => {
                    window.document.location = Router.generate('_clients_index');
                });
            }
        });
    }
});
