/*
 * This file is part of SolidInvoice package.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
import $ from 'jquery';
import _ from 'lodash';
import FormCollection from 'SolidInvoiceCore/js/util/form/collection';

export default FormCollection.extend({
    events: {
        'collection:add': 'onCollectionAdd',
        'initialize': 'onInitialize'
    },
    onCollectionAdd: function(event, form) {
        const clientContacts = $('.client_contacts', form);

        clientContacts.on('click', '.btn-add', _.bind(this.addFormGroup, this));
        clientContacts.on('click', '.btn-delete', _.bind(this.removeFormGroup, this));
        clientContacts.on('click', '.dropdown-menu a', _.bind(this.selectFormGroup, this));
    },
    onInitialize: function(event) {
        this.onCollectionAdd(event, this.$('.prototype-widget'));
    },
    addFormGroup: function(event) {
        event.preventDefault();
        event.stopPropagation();

        const $this = $(event.target),
            regex = new RegExp('__contact_details_prototype__', "g"),
            $formGroupContainer = $this.closest('.prototype-widget'),
            $formGroupContainerCounter = ( parseInt($formGroupContainer.data('counter'), 10) + 1 ),
            $formGroup = $(
                $formGroupContainer
                    .siblings('.additional-details')
                    .html()
                    .replace(regex, $formGroupContainerCounter)
            )
                .find('.multiple-form-group');

        $formGroupContainer.data('counter', $formGroupContainerCounter);

        $this
            .toggleClass('btn-success btn-add btn-danger btn-delete')
            .html('–')
        ;

        $formGroupContainer.append($formGroup);
    },
    removeFormGroup: function(event) {
        event.preventDefault();
        $(event.target).closest('.form-group').remove();
    },
    selectFormGroup: function(event) {
        event.preventDefault();

        const $this = $(event.target),
            $selectGroup = $this.closest('.input-group-select'),
            param = $this.data('value'),
            concept = $this.text();

        $selectGroup.find('.concept').text(concept);
        $selectGroup.find('.input-group-select-val').val(param);

    }
});
