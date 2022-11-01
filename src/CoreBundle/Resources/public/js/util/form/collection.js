/*
 * This file is part of SolidInvoice package.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import $ from 'jquery';
import { View } from 'backbone.marionette';
import { isEmpty, result, bind, replace } from 'lodash';

export default View.extend({
    addSelector: '.btn-add',
    removeSelector: '.btn-remove',
    addBtn(event) {
        event.preventDefault();

        // eslint-disable-next-line
        const collectionHolder = this.$el.find('div[data-prototype]').first(),
            prototype = collectionHolder.data('prototype');

        let counter = parseInt(collectionHolder.data('counter'), 10) || collectionHolder.children().length;

        if (!isEmpty(prototype)) {
            let prototype_name = collectionHolder.data('prototype-name');

            if (isEmpty(prototype_name)) {
                prototype_name = '__name__';
            }

            const regex = new RegExp(prototype_name, 'g'),
                form = replace(prototype, regex, counter);

            collectionHolder.data('counter', ++counter);

            const el = collectionHolder.append(form);

            this.$el.trigger('collection:add', el);

            this._toggleRemoveBtn();
        }
    },
    removeBtn(event) {
        event.preventDefault();
        const $this = $(event.target),
            $el = $this.closest('.prototype-widget'),
            that = this;

        $el.fadeOut(function() {
            $(this).remove();

            that._toggleRemoveBtn();
        });
    },
    initialize(options) {
        this.addSelector = result(options, 'addSelector', this.addSelector);
        this.removeSelector = result(options, 'removeSelector', this.removeSelector);

        this.delegate('click', this.addSelector, bind(this.addBtn, this));
        this.delegate('click', this.removeSelector, bind(this.removeBtn, this));

        this._toggleRemoveBtn();

        this.$el.trigger('initialize');
    },
    _toggleRemoveBtn() {
        // eslint-disable-next-line
        const collectionHolder = this.$el.find('div[data-prototype] > .prototype-widget');

        if (1 === collectionHolder.length) {
            this.$(this.removeSelector).hide();
        } else {
            this.$(this.removeSelector).show();
        }
    }
});
