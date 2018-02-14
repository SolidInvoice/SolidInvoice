/*
 * This file is part of SolidInvoice package.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import jQuery from 'jquery';
import {Observable} from 'rxjs';

export default class {
    addSelector = '.btn-add';
    removeSelector = '.btn-remove';

    $el = null;

    constructor() {
        console.log('abc');
        /*this.addSelector = _.result(options, 'addSelector', this.addSelector);
        this.removeSelector = _.result(options, 'removeSelector', this.removeSelector);*/

        jQuery(() => {
            Observable.fromEvent(document.querySelectorAll(this.addSelector), 'click')
                .subscribe(this.addBtn);
        });

        //this.delegate('click', this.addSelector, _.bind(this.addBtn, this));
        //this.delegate('click', this.removeSelector, _.bind(this.removeBtn, this));

        //this._toggleRemoveBtn();

        //this.$el.trigger('initialize');
    }

    addBtn(event) {
        event.preventDefault();
        console.log(event.target);
        console.log(jQuery('#' + jQuery(event.target).data('target')));

        /*event.preventDefault();

        var collectionHolder = this.$el.find('div[data-prototype]').first();

        var prototype = collectionHolder.data('prototype');
        var counter = parseInt(collectionHolder.data('counter'), 10) || collectionHolder.children().length;

        if (!_.isEmpty(prototype)) {
            var prototype_name = collectionHolder.data('prototype-name');

            if (_.isEmpty(prototype_name)) {
                prototype_name = '__name__';
            }

            var regex = new RegExp(prototype_name, "g");
            var form = prototype.replace(regex, counter);

            collectionHolder.data('counter', ++counter);

            var el = collectionHolder.append(form);

            this.$el.trigger('collection:add', el);

            this._toggleRemoveBtn();
        }*/
    }

    _toggleRemoveBtn() {
        let collectionHolder = this.$el.find('div[data-prototype] > .prototype-widget');

        if (collectionHolder.length === 1) {
            this.$(this.removeSelector).hide();
        } else {
            this.$(this.removeSelector).show();
        }
    }
}

/*
define(
    ['jquery', 'backbone', 'lodash'],
    function($, Backbone, _) {
        'use strict';

        return Backbone.View.extend({


            removeBtn: function(event) {
                event.preventDefault();
                var $this = $(event.target),
                    el = $this.closest('.prototype-widget'),
                    that = this;

                el.fadeOut(function() {
                    $(this).remove();

                    that._toggleRemoveBtn();
                });
            },
            initialize: function(options) {

            },

        });
    });
*/
