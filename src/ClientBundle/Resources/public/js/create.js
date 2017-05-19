/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(
    ['core/module', 'util/form/collection', './contacts_collection'],
    function(Module, FormCollection, ContactCollection) {
        'use strict';

        return Module.extend({
            formCollection: null,
            contactCollection: null,
            initialize: function() {
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
    }
);