/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

(function($, window) {

    "use strict";

    var Invoice = {
        "el" : null,
        "rowElement" : "tr",
        "columnElement" : "td",
        "fields" : [],
        "templates" : {},
        "trashTemplate" : '<div class="pull-right"><a href="#" class="remove-item" rel="tooltip" title="Remove Item"><i class="fa fa-trash-o"></i></a></div>',
        "counter" : 0,
        "addTemplate" : function(type, template) {
            this.templates[type] = template;
            return this;
        },
        "addField" : function(field) {
            this.fields.push(field);
            return this;
        },
        "setSelector" : function(selector) {
            this.el = $('tbody', selector);
            return this;
        },
        "addRow" : function() {
            var that         = this,
                row         = $(window.document.createElement(this.rowElement)),
                rowTotal = $('.invoice-item-total', row);


            $.each(this.fields, function(counter, item) {
                var column = $(window.document.createElement(that.columnElement));

                column.html(that.createField(item)).addClass('column-' + item);

                row.append(column);
            });

            this.counter += 1;

            row.hide();

            this.el.append(row);

            row.fadeIn(150, this.setEvents);

            rowTotal.val(accounting.formatMoney(rowTotal.val() || 0, ''));

            return this;
        },
        "createField" : function(item) {
            return this.templates[item].replace(/__name__/g, this.counter);
        },
        "setEvents" : function() {
            $('.invoice-item-price, .invoice-item-qty').unbind('keyup change').on('keyup', function() {
                Invoice.calcTotal(this);
            });

            $('.invoice-item-qty').on('change', function() {
                var qty = $(this),
                    val = qty.val(),
                    decimals,
                    value;

                if (val.indexOf('.') !== -1) {
                    decimals = val.substr(val.indexOf('.') + 1);
                    qty.val(accounting.toFixed(qty.val(), decimals.length > 2 ? 2 : decimals.length) || 1);
                } else {
                    value = accounting.toFixed(qty.val());
                    qty.val(value > 0 ? value : 1);
                }
                Invoice.calcTotal(this);
            });

            $('.invoice-item-price').on('change', function() {
                var price = $(this);
                price.val(accounting.formatMoney(price.val(), '', '2'));

                Invoice.calcTotal(this);
            });
        },
        "calcTotal" : function(row) {
            var tr = $(row).parents('tr'),
                price = accounting.unformat($('.invoice-item-price', tr).val()),
                qty   = $('.invoice-item-qty', tr).val(),
                total = $('.invoice-item-total', tr);

            total.val(accounting.formatMoney(qty * price, ''));

            Invoice.updateTotal();
        },
        "updateTotal" : function() {

            var subTotal = 0,
                discount;

            $('.invoice-item-total', this.el).each(function() {
                subTotal += parseFloat(accounting.unformat($(this).val()));
            });

            discount = (subTotal * parseInt($('#invoice_discount').val() || 0, 10) / 100);

            $('.invoice-discount').html(accounting.formatMoney(discount * -1));

            $('.invoice-sub-total').html(accounting.formatMoney(subTotal));

            $('.invoice-total').html(accounting.formatMoney(subTotal - discount));
        }
    };

    $(function() {
        $('.add-item').on('click', function(evt) {
            evt.preventDefault();

            Invoice.addRow();
        });

        $('.invoice-item-price, .invoice-item-total', Invoice.el).each(function() {
            var $this = $(this);

            if ($this.val() !== '') {
                $this.val(accounting.formatMoney($this.val(), ''));
            }

            Invoice.calcTotal(this);
        });

        $('#invoice_discount').on('change keyup', Invoice.updateTotal);

        $('#invoice-create-form').on('submit', function() {
            $('.invoice-item-price', this).each(function() {
                var $this = $(this);
                $this.val(accounting.unformat($this.val()));
            });

            return true;
        });

        $(Invoice.el.selector).on('click', '.remove-item', function(evt) {
            evt.preventDefault();
            $(this).parents('tr').fadeOut(function() {
                $(this).remove();
                Invoice.updateTotal();

                if ($(Invoice.rowElement, Invoice.el).length === 0) {
                    Invoice.addRow();
                }
            });
        });

        Invoice.setEvents();
    });

    window.Invoice = Invoice;
})(jQuery, window);