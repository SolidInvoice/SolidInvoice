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
import Mn from 'backbone.marionette';
import Backbone from 'backbone';
import _ from 'lodash';
import 'admin-lte';
import 'bootstrap';
import 'select2';
import 'jquery-placeholder';
import Module from './module';
import Config from '~/config';

var Application = Mn.Application.extend({
    module: null,
    regions: {},
    initialize: function(options) {

        this.regions = options.regions;

        /**
         * Tooltip
         */
        var tooltip = $('*[rel=tooltip]');
        if (tooltip.length) {
            tooltip.tooltip();
        }

        /**
         * Select2
         */
        var select2 = $('select.select2');
        if (select2.length) {
            select2.select2({
                theme: 'bootstrap'
            });
        }

        /**
         * PlaceHolder
         */
        var placeholder = $('input[placeholder]');
        if (placeholder.length) {
            placeholder.placeholder();
        }
    },
    showChildView: function(region, content) {
        var view = new ( Mn.View.extend({
            'el': this.regions[region],
            'template': function() {
                return content.render.apply(content).$el;
            }
        }) );

        view.render.apply(view);

        return view;
    }
});

var App = new Application({
    regions: Module.prototype.regions
});

App.on('before:start', function() {
    Accounting.settings = Config.accounting;
});

$(() => App.start());

App.on('start', function() {
    this.module = new Module(Config.moduleData, this);

    Backbone.history.start();
});

if (!_.isUndefined(Module.prototype.appEvents)) {
    _.each(Module.prototype.appEvents, function(action, event) {
        if (_.isFunction(action)) {
            App.on(event, action);
        } else if (-1 !== _.indexOf(_.functionsIn(Module), action)) {
            App.on(event, Module[action])
        } else {
            throw "Callback specified for event " + event + " is not a valid callback"
        }
    });
}

export default App;
