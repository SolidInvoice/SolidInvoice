/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

(function ($, window) {
    'use strict';

    var addFormGroup = function (event) {
        event.preventDefault();
        event.stopPropagation();

        var $this = $(this),
            regex = new RegExp('__contact_details_prototype__', "g"),
            $formGroupContainer = $this.closest('.prototype-widget'),
            $formGroupContainerCounter = (parseInt($formGroupContainer.data('counter'), 10) + 1);

        var $formGroup = $(
                    $formGroupContainer
                    .siblings('.additional-details')
                    .html()
                    .replace(regex, $formGroupContainerCounter)

                )
                .find('.multiple-form-group')
            ;

        $formGroupContainer.data('counter', $formGroupContainerCounter);

        $this
            .toggleClass('btn-success btn-add btn-danger btn-delete')
            .html('–')
        ;

        $formGroupContainer.append($formGroup);
    };

    var removeFormGroup = function (event) {
        event.preventDefault();
        $(this).closest('.form-group').remove();
    };

    var selectFormGroup = function (event) {
        event.preventDefault();

        var $this = $(this),
            $selectGroup = $this.closest('.input-group-select'),
            param = $this.data('value'),
            concept = $this.text();

        $selectGroup.find('.concept').text(concept);
        $selectGroup.find('.input-group-select-val').val(param);

    };

    var attachContactListeners = window.attachContactListeners = function () {
        $('.client_contacts')
            .on('click', '.btn-add', addFormGroup)
            .on('click', '.btn-delete', removeFormGroup)
            .on('click', '.dropdown-menu a', selectFormGroup)
        ;
    };

    $(function () {
        attachContactListeners();
    });
})(window.jQuery, window);
