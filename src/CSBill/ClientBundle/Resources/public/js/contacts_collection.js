(function($) {
    'use strict';

    /**
     * Form Collection
     */
    $(document.body).on('click', '.contact_details_collection li', function(event) {

        var value,
            collectionHolder,
            prototype,
            regex,
            form,
            prototype_name,
            collectionContainer = $(this).closest('.contact-type-list').siblings('.collection-container');

        collectionHolder = collectionContainer.find('.' + $(this).parents('ul').data('target'));
        collectionHolder = collectionHolder.filter('[data-type=' + $(this).data('type')  + ']');

        value = $(this).data('value');

        prototype = collectionHolder.data('prototype');

        if(undefined !== prototype && null !== prototype) {
            if(collectionHolder.data('prototype-name')) {
                prototype_name = collectionHolder.data('prototype-name');
            } else {
                prototype_name = '__name__';
            }

            regex = new RegExp(prototype_name, "g");
            form = prototype.replace(regex, collectionHolder.children().length - 1);

            collectionContainer.append(form);
        }
    });

})(window.jQuery);
