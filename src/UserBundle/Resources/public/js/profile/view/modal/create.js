define(['jquery', 'translator', 'core/ajaxmodal'], function($, __, AjaxModal) {
    "use strict";

    return AjaxModal.extend({
        'modal': {
            'title': __('profile.api.form.title'),
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
                'modal:save': 'saveApiToken'
            }
        },
        'saveApiToken': function() {

            this.showLoader();

            var modal = this;

            $.ajax({
                "url": this.getOption('route'),
                "data": this.$('form').serialize(),
                "type": "post",
                "success": function(response) {
                    modal.trigger('ajax:response', response);

                    if (_.has(modal, 'model')) {
                        modal.model.set(response);
                    }

                    modal.$el.modal('hide');
                },
                "error": function(response) {
                    modal.options.template = response;
                    modal.hideLoader();
                    modal.render();
                }
            });
        }
    });
});