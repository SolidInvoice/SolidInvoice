import $ from 'jquery';
import AjaxModal from 'SolidInvoiceCore/js/ajaxmodal';
import Translator from 'translator';
import { has } from 'lodash';

export default AjaxModal.extend({
    'modal': {
        'title': Translator.trans('profile.api.form.title'),
        'buttons': {
            'Close': {
                'class': 'warning',
                'close': true,
                'flat': true
            },
            'Save': {
                'class': 'success',
                'save': true,
                'flat': true
            }
        },
        'events': {
            'modal:save': 'saveApiToken'
        }
    },
    saveApiToken () {

        this.showLoader();

        const modal = this;

        $.ajax({
            'url': this.getOption('route'),
            'data': this.$('form').serialize(),
            'type': 'post',
            success: (response) => {
                modal.trigger('ajax:response', response);

                if (has(modal, 'model')) {
                    modal.model.set(response);
                }

                modal.$el.modal('hide');
            },
            error: (response) => {
                modal.options.template = response;
                modal.hideLoader();
                modal.render();
            }
        });
    }
});
