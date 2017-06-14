define(
    ['jquery', 'core/ajaxmodal', 'accounting', 'lodash', 'translator', 'parsley'],
    function($, AjaxModal, Accounting, _, __, Parsley) {
        "use strict";

        return AjaxModal.extend({
            'modal': {
                'title': 'client.modal.edit_contact',//__('client.modal.edit_contact'),
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
            saveContact: function() {
                this.showLoader();

                var view = this;

                $.ajax({
                    "url": this.getOption('route'),
                    "data": this.$('form').serialize(),
                    "type": "post",
                    "success": function(response) {
                        view.trigger('ajax:response', response);

                        if (_.has(view, 'model')) {
                            view.model.set(response);
                            view.model.trigger('sync');
                        }

                        view.$el.modal('hide');
                    },
                    "error": function(response) {
                        // @TODO: If there are any validation errors, then we should re-render the modal with the content
                        view.options.template = response;
                        view.hideLoader();
                        view.render();
                    }
                });
            }
        });
    });