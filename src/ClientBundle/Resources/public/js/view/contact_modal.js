define(
    ['jquery', 'core/ajaxmodal', 'accounting', 'lodash', 'translator', 'parsley'],
    function($, AjaxModal, Accounting, _, __, Parsley) {
        "use strict";

        return AjaxModal.extend({
            'modal': {
                'title': __('client.modal.edit_contact'),
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
                    'modal:save': 'saveContact'
                }
            },
            onBeforeModalSave: Parsley.validate,
            'saveContact': function() {

                this.showLoader();

                var view = this;

                $.ajax({
                    "url" : this.getOption('route'),
                    "data" : this.$('form').serialize(),
                    "type" : "post",
                    "success": function (response) {
                        view.trigger('ajax:response', response);

                        if (response.status !== 'success') {
                            view.options.template = response.content;
                            view.hideLoader();
                            view.render();
                        } else {
                            if (_.has(view, 'model')) {
                                view.model.fetch({"success": function () {
                                    view.$el.modal('hide');
                                }});
                            } else {
                                view.$el.modal('hide');
                            }
                        }
                    }
                });
            }
        });
    });