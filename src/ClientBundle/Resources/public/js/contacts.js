/*
 * This file is part of SolidInvoice package.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import ContactCollectionView from './view/contact_collection';
import ContactCollectionModel from './model/contact_collection';

export default function(options) {
    const collection = new ContactCollectionModel(options.contacts);

    return new ContactCollectionView({
        collection: collection
    });
}
