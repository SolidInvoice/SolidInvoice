/*
 * This file is part of SolidInvoice package.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import { View } from 'backbone.marionette';
import Template from '../../templates/info.hbs';
import Contact from '../model/contact';
import ContactModal from './contact_modal';
import Translator from 'translator';

export default View.extend({
    template: Template,
    contactCollection: null,
    regions: {
        'clientContact': '#client-contacts-list',
        'clientAddress': '#client-address-list'
    },
    ui: {
        'addContact': '#add-contact-button'
    },
    events: {
        'click @ui.addContact': 'addContact'
    },
    addContact(event) {
        event.preventDefault();

        const collection = this.contactCollection,
            modal = ContactModal.extend({
                initialize() {
                    this.listenTo(this, 'ajax:response', (response) => {
                        collection.add(new Contact(response));
                    });
                }
            });

        new modal({
            title: Translator.trans('client.modal.add_contact'),
            route: this.$(event.currentTarget).prop('href')
        });
    },
    renderContactsRegion(view) {
        this.contactCollection = view.collection;

        this.getRegion('clientContact').show(view);
    }
});
