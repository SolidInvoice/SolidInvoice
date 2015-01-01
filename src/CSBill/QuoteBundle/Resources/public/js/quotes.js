/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

(function($, Routing, accounting, window) {

    "use strict";

    var Quote = {
        "el" : null,
        "rowElement" : "tr",
        "columnElement" : "td",
        "fields" : [],
        "templates" : {},
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
                row         = $(window.document.createElement(this.rowElement)).hide(),
                rowTotal = $('.column-total', row);

            $.each(this.fields, function(counter, item) {
                var column = $(window.document.createElement(that.columnElement));

                column.html(that.createField(item)).addClass('column-' + item);

                row.append(column);
            });

            this.counter += 1;
            this.el.append(row);

            row.fadeIn(150);

            $('select.quote-item-tax', row).select2({
                allowClear: true
            });

            rowTotal.val(accounting.formatMoney(rowTotal.val() || 0, ''));

            return this;
        },
        "createField" : function(item) {
            return this.templates[item].replace(/__name__/g, this.counter);
        },
        "setEvents" : function() {
            $(this.el)
                .on('keyup change', '.quote-item-price, .quote-item-qty, select.quote-item-tax', function() {
                    Quote.calcTotal(this);
                })
                .on('change', '.quote-item-qty', function() {
                    var qty = $(this),
                        val = qty.val(),
                        decimals,
                        value;

                    if (val.indexOf(accounting.settings.number.decimal) !== -1) {
                        decimals = val.substr(val.indexOf(accounting.settings.number.decimal) + 1);
                        qty.val(accounting.toFixed(qty.val(), decimals.length > 2 ? 2 : decimals.length) || 1);
                    } else {
                        value = accounting.toFixed(qty.val());
                        qty.val(value > 0 ? value : 1);
                    }

                    Quote.calcTotal(this);
                })
                .on('change', '.quote-item-price', function() {
                    var price = $(this);
                    price.val(accounting.formatMoney(price.val(), '', '2'));

                    Quote.calcTotal(this);
                });

            $('select.quote-item-tax', this.el).select2({
                allowClear: true
            });
        },
        "calcTotal" : function(row) {
            var tr = $(row).parents('tr'),
                price = accounting.unformat($('.quote-item-price', tr).val()),
                qty   = $('.quote-item-qty', tr).val(),
                total = $('.column-total', tr);

            var quoteTotal = accounting.formatMoney(qty * price);

            total.text(quoteTotal);

            Quote.updateTotal();
        },
        "updateTotal" : function() {
            var total,
                subTotal = 0,
                totalTax = 0,
                discount,
                discountAmount;

            $('.column-total', this.el).each(function() {
                subTotal += parseFloat(accounting.unformat($(this).text()));
            });

            discount = parseInt($('#quote_discount').val() || 0, 10);

            $('select.quote-item-tax', this.el).each(function() {
                var tax = $(this),
                    selectedOption = tax.find(':selected'),
                    rowTotal = parseFloat(accounting.unformat(tax.closest('tr').find('.column-total').text()))
                if (tax.val() !== '') {
                    var taxAmount = percentage(rowTotal, parseFloat(selectedOption.data('rate')));
                    totalTax += taxAmount;

                    if ('inclusive' === selectedOption.data('type')) {
                        subTotal -= taxAmount;
                    }
                }
            });

            discountAmount = percentage((subTotal + totalTax), discount);
            total = (subTotal - discountAmount) + totalTax;

            $('.quote-discount').html(accounting.formatMoney(discountAmount * -1));
            $('.quote-sub-total').html(accounting.formatMoney(subTotal));
            $('.quote-total').html(accounting.formatMoney(total));
            $('.quote-tax').html(accounting.formatMoney(totalTax));

            $('#quote_baseTotal').val(subTotal);
            $('#quote_tax').val(totalTax);
            $('#quote_total').val(total);
        }
    };

    $(function() {
        $('.add-item').on('click', function(evt) {
            evt.preventDefault();

            Quote.addRow();
        });

        $('.quote-item-price, .column-total', Quote.el).each(function() {
            var $this = $(this);

            if ($this.val() !== '') {
                $this.val(accounting.formatMoney($this.val(), ''));
            }

            Quote.calcTotal(this);
        });

        $('#quote_discount').on('change keyup', Quote.updateTotal);

        $('#quote-create-form').on('submit', function() {
            $('.quote-item-price', this).each(function() {
                var $this = $(this);
                $this.val(accounting.unformat($this.val()));
            });

            return true;
        });

        $(Quote.el.selector).on('click', '.remove-item', function(evt) {
            evt.preventDefault();
            $(this).parents('tr').fadeOut(function() {
                $(this).remove();
                Quote.updateTotal();

                if ($(Quote.rowElement, Quote.el).length === 0) {
                    Quote.addRow();
                }
            });
        });

        Quote.setEvents();
    });

    window.Quote = Quote;
})(window.jQuery, window.Routing, window.accounting, window);