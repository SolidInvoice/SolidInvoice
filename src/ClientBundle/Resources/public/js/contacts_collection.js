/*
 * This file is part of SolidInvoice package.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
import $ from 'jquery';
import FormCollection from 'SolidInvoiceCore/js/util/form/collection';
import { replace } from 'lodash';

export default FormCollection.extend({
    events: {
        'collection:add': 'onCollectionAdd',
        'initialize': 'onInitialize'
    },
    onCollectionAdd (event, form) {
        const $clientContacts = $('.client_contacts', form);

        $clientContacts.on('click', '.btn-add', (e) => this.addFormGroup(e));
        $clientContacts.on('click', '.btn-delete', (e) => this.removeFormGroup(e));
        $clientContacts.on('click', '.dropdown-menu a', (e) => this.selectFormGroup(e));
    },
    onInitialize (event) {
        this.onCollectionAdd(event, this.$('.prototype-widget'));
    },
    addFormGroup (event) {
        event.preventDefault();
        event.stopPropagation();

        const $this = $(event.target),
            regex = new RegExp('__contact_details_prototype__', 'g'),
            $formGroupContainer = $this.closest('.prototype-widget'),
            $formGroupContainerCounter = ( parseInt($formGroupContainer.data('counter'), 10) + 1 ),
            // eslint-disable-next-line
            $formGroup = $(
                replace($formGroupContainer
                    .siblings('.additional-details')
                    .html()
                ), regex, $formGroupContainerCounter)
                .find('.multiple-form-group');

        $formGroupContainer.data('counter', $formGroupContainerCounter);

        $this
            .toggleClass('btn-success btn-add btn-danger btn-delete')
            .html('–')
        ;

        $formGroupContainer.append($formGroup);
    },
    removeFormGroup (event) {
        event.preventDefault();
        $(event.target).closest('.form-group').remove();
    },
    selectFormGroup (event) {
        event.preventDefault();

        const $this = $(event.target),
            $selectGroup = $this.closest('.input-group-select'),
            param = $this.data('value'),
            concept = $this.text();

        // eslint-disable-next-line
        $selectGroup.find('.concept').text(concept);
        // eslint-disable-next-line
        $selectGroup.find('.input-group-select-val').val(param);

    }
});
