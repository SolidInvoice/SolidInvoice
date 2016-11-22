/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(
    /** global: requirejs */
    ['jquery', 'marionette', 'backbone', 'lodash', requirejs.s.contexts._.config.module, 'material', 'bootstrap', 'core/module'],
    function($, Mn, Backbone, _, Module) {
        'use strict';

        if (_.isUndefined(Module)) {
            Module = require('core/module');
        }

        var ModuleData = requirejs.s.contexts._.config.moduleData,

            Application = Mn.Application.extend({
                module : null,
                initialize: function() {
                    /**
                     * Tooltip
                     */
                    var tooltip = $('*[rel=tooltip]');
                    if (tooltip.length) {
                        require(['bootstrap'], function() {
                            tooltip.tooltip();
                        });
                    }

                    /**
                     * Select2
                     */
                    var select2 = $('select.select2');
                    if (select2.length) {
                        require(['jquery.select2'], function() {
                            select2.select2({
                                allowClear: true
                            });
                        });
                    }

                    /**
                     * PlaceHolder
                     */
                    var placeholder = $('input[placeholder]');
                    if (placeholder.length) {
                        require(['jquery.placeholder'], function() {
                            placeholder.placeholder();
                        });
                    }

                    /**
		     * Material
		     */
		    $.material.init();
		}
	    });

        var App = new Application({
            regions : Module.prototype.regions
        });

        App.on('start', function() {
            this.module = new Module(ModuleData, this);

            Backbone.history.start();
        });

        if (!_.isUndefined(Module.prototype.appEvents)) {
            _.each(Module.prototype.appEvents, function (action, event) {
                if (_.isFunction(action)) {
                    App.on(event, action);
                } else if (-1 !== _.indexOf(_.functions(Module), action)) {
                    App.on(event, Module[action])
                } else {
                    throw "Callback specified for event " + event + " is not a valid callback"
                }
            });
        }

        return App;
    }
);
