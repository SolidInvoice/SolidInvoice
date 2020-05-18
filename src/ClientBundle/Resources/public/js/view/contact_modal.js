import $ from 'jquery';
import AjaxModal from 'SolidInvoiceCore/js/ajaxmodal';
import { has } from 'lodash';
import Translator from 'translator';
import ContactsCollection from '../contacts_collection'

export default AjaxModal.extend({
    'modal': {
        'title': Translator.trans('client.modal.edit_contact'),
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
            'modal:save': 'saveContact',
            'render': 'initFormCollection',
        }
    },
    constructor(options) {
        if (has(options, 'title')) {
            this.modal.title = options.title;
        }

        AjaxModal.call(this, options);
    },
    initFormCollection() {
        new ContactsCollection({
            'el' : '#client-contact'
        });
    },
    saveContact() {
        this.showLoader();

        $.ajax({
            'url': this.getOption('route'),
            'data': this.$('form').serialize(),
            'type': 'post',
            success: (response) => {
                this.trigger('ajax:response', response);

                if (has(this, 'model')) {
                    this.model.set(response);
                    this.model.trigger('sync');
                }

                this.$el.modal('hide');
            },
            error: (response) => {
                // @TODO: If there are any validation errors, then we should re-render the modal with the content
                this.options.template = response;
                this.hideLoader();
                this.render();
            }
        });
    }
});
