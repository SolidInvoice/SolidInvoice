/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(
    ['core/module', 'util/form/collection', 'csbillclient/js/contacts_collection'],
    function(Module, FormCollection, ContactCollection) {
        'use strict';

        return Module.extend({
            initialize: function() {
                new FormCollection({
                    el: '#client-address-collection',
                    addSelector: '.add_form_collection_link'
                });

                new ContactCollection({
                    el: '#client-contacts-collection',
                    addSelector: '.add_form_collection_link'
                });
            }
        });
    }
);