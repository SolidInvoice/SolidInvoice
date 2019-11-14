/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import ItemView from 'SolidInvoiceCore/js/view';
import $ from 'jquery';
import Template from '../../templates/action.hbs';
import { has, isEmpty, map } from 'lodash';
import Router from 'router';
import Alert from 'SolidInvoiceCore/js/alert';

export default ItemView.extend({
    tagName: 'span',
    template: Template,
    ui: {
        button: ".btn"
    },
    events: {
        'click @ui.button': 'confirm'
    },
    confirm () {
        if (isEmpty(this.getOption('grid').getSelectedModels())) {
            return Alert.alert('You need to select at least one row');
        }

        if (!isEmpty(this.model.get('confirm'))) {
            Alert.confirm(this.model.get('confirm'), (response) => {
                if (true === response) {
                    return this._executeAction();
                }
            });
        } else {
            this._executeAction();
        }
    },
    _executeAction () {
        const grid = this.getOption('grid');

        const models = map(grid.getSelectedModels(), 'id'),
            promise = $.ajax({
                url: Router.generate(this.model.get('action')),
                data: { 'data': models },
                method: 'POST'
            });

        promise.done((e) => {
            if (!isEmpty(e) && has(e, 'message')) {
                Alert.alert(e.message);
            } else {
                grid.clearSelectedModels();
                grid.collection.fetch();
            }
        });

        return promise;
    }
});
