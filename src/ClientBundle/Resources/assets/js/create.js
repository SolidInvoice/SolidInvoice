/*
 * This file is part of SolidInvoice package.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import Module from 'SolidInvoiceCore/js/module';
import FormCollection from 'SolidInvoiceCore/js/util/form/collection';
import ContactCollection from './contacts_collection';

export default Module.extend({
    formCollection: null,
    contactCollection: null,
    initialize() {
        this.formCollection = new FormCollection({
            el: '#client-address-collection',
            addSelector: '.add_form_collection_link'
        });

        this.contactCollection = new ContactCollection({
            el: '#client-contacts-collection',
            addSelector: '.add_form_collection_link'
        });
    }
});
