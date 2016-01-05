/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(
    ['marionette', 'client/view/credit', 'client/model/credit'],
    function(Mn, CreditView, CreditModel) {
        'use strict';

        var credit = Mn.Object.extend({
            getView: function(options) {
                var model = new CreditModel({
                    credit: options.credit.value,
                    id: options.id
                });

                return new CreditView({
                    model: model
                });
            }
        });

        return new credit;
    }
);