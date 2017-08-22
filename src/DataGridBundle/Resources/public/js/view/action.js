/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(
    ['core/view', 'jquery', 'template', 'lodash', 'routing', 'core/alert'],
    function(ItemView, $, Template, _, Routing, Alert) {
        return ItemView.extend({
            tagName: 'span',
            template: Template.datagrid.action,
            ui: {
                button: ".btn"
            },
            events: {
                'click @ui.button': 'confirm'
            },
            confirm: function() {
                if (_.isEmpty(this.getOption('grid').getSelectedModels())) {
                    return Alert.alert('You need to select at least one row');
                }

                if (!_.isEmpty(this.model.get('confirm'))) {
                    var view = this;
                    Alert.confirm(this.model.get('confirm'), function(response) {
                        if (true === response) {
                            return view._executeAction();
                        }
                    });
                } else {
                    this._executeAction();
                }
            },
            _executeAction: function() {
                var grid = this.getOption('grid');

                var models = _.map(
                    grid.getSelectedModels(),
                    function(model) {
                        return model.id;
                    }),

                    promise = $.ajax({
                        url: Routing.generate(this.model.get('action')),
                        data: {'data': models},
                        method: 'POST'
                    });

                promise.done(_.bind(function() {
                    grid.collection.fetch({
                        success: function() {
                            grid.clearSelectedModels();
                        }
                    });
                }, this));

                return promise;
            }
        })
    }
);