define(
    ['core/ajaxmodal', 'accounting', 'template', 'translator', 'parsley'],
    function(AjaxModal, Accounting, Template, __, Parsley) {
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
                    success: function (response) {
                        if (response.status !== 'success') {
                            view.options.template = response.content;
                            view.hideLoader();
                            view.render();
                        } else {
                            view.model.fetch({"success": function () {
                                view.$el.modal('hide');
                            }});
                        }
                    }
                });
            }
        });
    });