/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import $ from 'jquery';
import Backgrid from 'backgrid';
import Router from 'router';

export default {
    row(route) {
        return Backgrid.Row.extend({
            className: 'redirectable',
            events: {
                'click': 'onClick'
            },
            onClick(e) {
                if (null === route) {
                    return
                }

                const target = $(e.target);

                if (target.is(':input') || target.hasClass('select-row-cell') || target.hasClass('action-cell')) {
                    return;
                }

                window.location = Router.generate(route, { 'id': this.model.get('id') });
            }
        });
    },
};

