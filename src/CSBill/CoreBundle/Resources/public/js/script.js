/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

$(function(){
    /**
     * Tooltip
     */
    $('body').tooltip({
        "selector" : '[rel=tooltip]'
    });

    /**
     * Select2
     */
    $('select.select2').select2({
        allowClear: true
    });

    /**
     * PlaceHolder
     */
    $('input[placeholder]').placeholder();

    /**
     * Form Collection
     */
    $(document.body).on('click', '.btn-add', function(event) {

        event.preventDefault();

        var collectionHolder,
            prototype,
            regex,
            form,
            prototype_name;

        collectionHolder = $(this).siblings('.' + $(this).data('target'));

        prototype = collectionHolder.data('prototype');

        if(undefined !== prototype && null !== prototype) {
            if(collectionHolder.data('prototype-name')) {
                prototype_name = collectionHolder.data('prototype-name');
            } else {
                prototype_name = '__name__';
            }

            regex = new RegExp(prototype_name, "g");
            form = prototype.replace(regex, collectionHolder.children().length);

            collectionHolder.append(form);
        }
    });

    $(document.body).on('click', '.btn-remove', function(event) {
        event.preventDefault();
        var name = $(this).attr('data-related'),
            el = $(this).closest('div[data-content^="' + name + '"]');

        el.fadeOut(function() {
            $(this).remove();
        });

        return false;
    });


    $.fn.modal.defaults.spinner = $.fn.modalmanager.defaults.spinner =
        '<div class="loading-spinner" style="width: 200px; margin-left: -100px;">' +
            '<div class="progress progress-striped active">' +
            '<div class="progress-bar" style="width: 100%;"></div>' +
            '</div>' +
            '</div>';

    $.fn.modal.defaults.maxHeight = function(){
        // subtract the height of the modal header and footer
        return $(window).height() - 165;
    };

    /**
     * Material
     */
    $.material.init();

    /**
     * Datepicker
     */

    $(':input.datepicker').each(function () {
        var el = $(this),
            minDate = el.data('min-date') ? new Date(el.data('min-date')) : null,
            time = el.data('min-date') || false,
            format = el.data('min-date') || 'YYYY-MM-DD';

        var options = {
            'time' : time,
            'format' : format,
            'minDate' : minDate
        };

        el.bootstrapMaterialDatePicker(options);

        console.log(el.data('depends'));

        if (el.data('depends')) {
            var dependecy = $('#' + el.data('depends'));

            dependecy.on('change', function(e, date) {
                el.bootstrapMaterialDatePicker('setMinDate', date);
            });
        }
    });
});

function percentage(amount, percentage)
{
    "use strict";

    return (amount * percentage) / 100;
}
