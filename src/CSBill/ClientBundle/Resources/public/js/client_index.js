/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

(function($) {
    "use strict";

    $(function() {
        /**
         * Delete Client
         */
        $('.delete-client').on('click', function(evt) {
            evt.preventDefault();

            var link = $(this);

            bootbox.confirm(link.data('confirm'), function(bool) {
                if (true === bool) {
                    $('body').modalmanager('loading');
                    $.ajax({
                        "url" : link.attr("href"),
                        "dataType" : "json",
                        "method" : "post"
                    }).done(function() {
                        window.document.location = Routing.generate("_clients_index");
                    });
                }
            });
        });
    });

})(window.jQuery);