/*
 * This file is part of SolidInvoice package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(
    ['marionette', 'client/view/contact_collection', 'client/model/contact_collection'],
    function(Mn, ContactCollectionView, ContactCollectionModel) {
        'use strict';

        var contacts = Mn.MnObject.extend({
            getView: function(options) {
                var collection = new ContactCollectionModel(options.contacts);

                return new ContactCollectionView({
                    collection: collection
                });
            }
        });

        return new contacts;
    }
);