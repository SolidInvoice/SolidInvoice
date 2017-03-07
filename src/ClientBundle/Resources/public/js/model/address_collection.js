/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['backbone', './address'], function(Backbone, Address) {
    "use strict";

    return Backbone.Collection.extend({
        model: Address
    });
});