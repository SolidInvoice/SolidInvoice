/*
 * This file is part of SolidInvoice package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import Accounting from 'accounting';
import $ from 'jquery';
import { Application as MnApplication } from 'backbone.marionette';
import Backbone from 'backbone';
import { each, functionsIn, indexOf, isFunction, isUndefined } from 'lodash';
import 'admin-lte';
import 'bootstrap';
import 'select2';
import 'jquery-placeholder';
import 'regenerator-runtime/runtime';
import VatNumberValidator from 'SolidInvoiceTax/js/vat_validator';

const Application = MnApplication.extend({
    module: null,
    regions: {},
    initialize: function(options) {

        this.regions = options.regions;

        /**
         * Tooltip
         */
        const tooltip = $('*[rel=tooltip]');
        if (tooltip.length) {
            tooltip.tooltip();
        }

        /**
         * Select2
         */
        const select2 = $('select.select2');
        if (select2.length) {
            select2.select2({
                theme: 'bootstrap'
            });
        }

        /**
         * PlaceHolder
         */
        const placeholder = $('input[placeholder]');
        if (placeholder.length) {
            placeholder.placeholder();
        }

        /**
         * VAT Validator
         */
        const vatInput = $('.vat-validator');
        if (vatInput.length) {
            vatInput.each((i, el) => {
                VatNumberValidator(el);
            });
        }

    },
    showChildView: function(region, content) {
        const view = new ( Mn.View.extend({
            'el': this.regions[region],
            'template': function() {
                return content.render.apply(content).$el;
            }
        }) );

        view.render.apply(view);

        return view;
    }
});

export default function(module) {
    $(async () => {
        const Config = ( await import(/* webpackMode: "eager" */ '~/config') ).default;
        const app = new Application({
            regions: module.prototype.regions
        });

        app.on('before:start', function() {
            Accounting.settings = Config.accounting;
        });

        app.on('start', function() {
            this.module = new module(Config.module.data, this);

            Backbone.history.start();
        });

        if (!isUndefined(module.prototype.appEvents)) {
            each(module.prototype.appEvents, function(action, event) {
                if (isFunction(action)) {
                    app.on(event, action);
                } else if (-1 !== indexOf(functionsIn(module), action)) {
                    app.on(event, module[action])
                } else {
                    throw "Callback specified for event " + event + " is not a valid callback"
                }
            });
        }

        app.start();
    });
};
