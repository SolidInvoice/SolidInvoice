/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import $ from 'jquery';
import AjaxModal from 'SolidInvoiceCore/js/ajaxmodal';
import { has } from 'lodash';
import Translator from 'translator';

export default AjaxModal.extend({
    'modal': {
        'title': Translator.trans('client.modal.edit_address'),
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
    saveAddress () {

        this.showLoader();

        $.ajax({
            'url': this.getOption('route'),
            'data': this.$('form').serialize(),
            'type': 'post',
            success (response) {
                this.trigger('ajax:response', response);

                if (has(this, 'model')) {
                    this.model.fetch({
                        success() {
                            this.$el.modal('hide');
                        }
                    });
                } else {
                    this.$el.modal('hide');
                }
            },
            error (response) {
                this.options.template = response;
                this.hideLoader();
                this.render();
            }
        });
    }
});
