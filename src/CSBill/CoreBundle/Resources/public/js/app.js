/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(
    ['jquery', 'marionette', 'backbone', 'material'],
    function($, Mn, Backbone)
    {
        var App = new (Mn.Application.extend({
            'initialize' : function () {
                /**
                 * Tooltip
                 */
                var tooltip = $('*[rel=tooltip]');
                if (tooltip.length) {
                    require(['bootstrap'], function () {
                        tooltip.tooltip();
                    });
                }

                /**
                 * Select2
                 */
                var select2 = $('select.select2');
                if (select2.length) {
                    require(['jquery.select2'], function () {
                        select2.select2({
                            allowClear: true
                        });
                    });
                }

                /**
                 * PlaceHolder
                 */
                var placeholder = $('input[placeholder]');
                if (placeholder.length) {
                    require(['jquery.placeholder'], function () {
                        placeholder.placeholder();
                    });
                }

                /**
                 * Material
                 */
                $.material.init();
            }
        }))();

        App.on('start', function() {
            Backbone.history.start();
        });

        /*/!**
         * Form Collection
         *!/
        $(document.body).on('click', '.btn-add', function(event) {
            event.preventDefault();

            var collectionHolder,
                prototype,
                regex,
                form,
                prototype_name;

            collectionHolder = $(this).siblings('.' + $(this).data('target'));

            prototype = collectionHolder.data('prototype');

            if (undefined !== prototype && null !== prototype) {
                if (collectionHolder.data('prototype-name')) {
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
        });*/

        App.start({});

        return App;
    }
);
