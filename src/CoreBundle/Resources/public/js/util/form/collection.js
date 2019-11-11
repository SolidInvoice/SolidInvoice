/*
 * This file is part of SolidInvoice package.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import $ from 'jquery';
import Mn from 'backbone.marionette';
import _ from 'lodash';
import 'select2';

export default Mn.View.extend({
    addSelector: '.btn-add',
    removeSelector: '.btn-remove',
    addBtn: function(event) {
        event.preventDefault();

        const collectionHolder = this.$el.find('div[data-prototype]').first(),
            prototype = collectionHolder.data('prototype');

        let counter = parseInt(collectionHolder.data('counter'), 10) || collectionHolder.children().length;

        if (!_.isEmpty(prototype)) {
            let prototype_name = collectionHolder.data('prototype-name');

            if (_.isEmpty(prototype_name)) {
                prototype_name = '__name__';
            }

            const regex = new RegExp(prototype_name, "g"),
                form = prototype.replace(regex, counter);

            collectionHolder.data('counter', ++counter);

            const el = collectionHolder.append(form);
            const select2 = $('select.select2');
            if (select2.length) {
                select2.select2({
                    theme: 'bootstrap'
                });
            }

            this.$el.trigger('collection:add', el);

            this._toggleRemoveBtn();
        }
    },
    removeBtn: function(event) {
        event.preventDefault();
        const $this = $(event.target),
            el = $this.closest('.prototype-widget'),
            that = this;

        el.fadeOut(function() {
            $(this).remove();

            that._toggleRemoveBtn();
        });
    },
    initialize: function(options) {
        this.addSelector = _.result(options, 'addSelector', this.addSelector);
        this.removeSelector = _.result(options, 'removeSelector', this.removeSelector);

        this.delegate('click', this.addSelector, _.bind(this.addBtn, this));
        this.delegate('click', this.removeSelector, _.bind(this.removeBtn, this));

        this._toggleRemoveBtn();

        this.$el.trigger('initialize');
    },
    _toggleRemoveBtn: function() {
        const collectionHolder = this.$el.find('div[data-prototype] > .prototype-widget');

        if (collectionHolder.length === 1) {
            this.$(this.removeSelector).hide();
        } else {
            this.$(this.removeSelector).show();
        }
    }
});
