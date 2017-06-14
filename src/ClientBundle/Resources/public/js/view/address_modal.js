/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(
    ['jquery', 'core/ajaxmodal', 'accounting', 'lodash', 'translator', 'parsley'],
    function($, AjaxModal, Accounting, _, __, Parsley) {
        "use strict";

        return AjaxModal.extend({
            'modal': {
                'title': 'client.modal.edit_address', //__('client.modal.edit_address'),
                'buttons': {
                    'close': {
                        'class': 'warning',
                        'close': true,
                        'flat': true
                    },
                    'save': {
                        'class': 'success',
                        'save': true,
                        'flat': true
                    }
                },
                'events': {
                    'modal:save': 'saveAddress'
                }
            },
            onBeforeModalSave: Parsley.validate,
            'saveAddress': function() {

                this.showLoader();

                var view = this;

                $.ajax({
                    "url": this.getOption('route'),
                    "data": this.$('form').serialize(),
                    "type": "post",
                    "success": function(response) {
                        view.trigger('ajax:response', response);

                        if (_.has(view, 'model')) {
                            view.model.fetch({
                                "success": function() {
                                    view.$el.modal('hide');
                                }
                            });
                        } else {
                            view.$el.modal('hide');
                        }
                    },
                    "error": function(response) {
                        view.options.template = response;
                        view.hideLoader();
                        view.render();
                    }
                });
            }
        });
    });