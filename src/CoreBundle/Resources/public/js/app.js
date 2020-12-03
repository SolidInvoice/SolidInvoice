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
import { Application as MnApplication, View } from 'backbone.marionette';
import Backbone from 'backbone';
import { forEach, functionsIn, includes, isFunction, isUndefined, trim } from 'lodash';
import 'admin-lte';
import 'bootstrap';
import 'select2';
import 'jquery-placeholder';
import 'regenerator-runtime/runtime';

const Application = MnApplication.extend({
    module: null,
    regions: {},
    initialize (options) {

        this.regions = options.regions;

        /**
         * Tooltip
         */
        const $tooltip = $('*[rel=tooltip]');
        if ($tooltip.length) {
            $tooltip.tooltip();
        }

        /**
         * Select2
         */
        const $select2 = $('select.select2');
        if ($select2.length) {
            $select2.select2({
                theme: 'bootstrap'
            });
        }

        /**
         * PlaceHolder
         */
        const $placeholder = $('input[placeholder]');
        if ($placeholder.length) {
            $placeholder.placeholder();
        }

        /**
         * VAT Validator
         */
        const $vatInput = $('.vat-validator');
        if ($vatInput.length) {
            import('SolidInvoiceTax/js/vat_validator').then(({ default: VatNumberValidator }) => {
                $vatInput.each((i, el) => {
                    VatNumberValidator(el);
                });
            });
        }

        /**
         * CRON
         */
        const $cronInput = $('.cron-expr');
        if ($cronInput.length) {
            import('SolidInvoiceCore/js/util/form/cron').then(({ default: Cron }) => {
                $cronInput.each((i, el) => {
                    Cron(`#${el.id}`);
                });
            });
        }

        /**
         * GRID
         */
        const $grids = $('script[data-type="grid"]');
        if ($grids.length) {
            import('SolidInvoiceDataGrid/js/grid').then(({ default: Grid }) => {
                $grids.each((i, el) => {
                    new Grid($(el).data('target'), JSON.parse(trim($(el).text())));
                });
            });
        }

        /**
         * Multiple GRID
         */
        const $multipleGrids = $('script[data-type="multiple_grid"]');
        if ($multipleGrids.length) {
            import('SolidInvoiceDataGrid/js/multiple_grid').then(({ default: MultipleGrid }) => {
                $multipleGrids.each((i, el) => {
                    new MultipleGrid({
                        'model': new Backbone.Model({ 'grids': JSON.parse(trim($(el).text())), 'gridId': $(el).data('target') }),
                        'el': $(el).data('target')
                    });
                });
            });
        }
    },
    showChildView (region, content) {
        const view = new ( View.extend({
            'el': this.regions[region],
            'template': () => {
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

        app.on('before:start', () => {
            Accounting.settings = Config.accounting;
        });

        app.on('start', function() {
            this.module = new module(Config.module.data, this);

            if (!Backbone.History.started) {
                Backbone.history.start();
            }
        });

        if (!isUndefined(module.prototype.appEvents)) {
            forEach(module.prototype.appEvents, (action, event) => {
                if (isFunction(action)) {
                    app.on(event, action);
                } else if (includes(functionsIn(module), action)) {
                    app.on(event, module[action])
                } else {
                    throw `Callback specified for event ${event} is not a valid callback`
                }
            });
        }

        app.start();
    });
}
