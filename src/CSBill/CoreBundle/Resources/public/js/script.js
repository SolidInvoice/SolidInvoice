$(function(){
    /**
     * Tooltip
     */
    $('body').tooltip({
        "selector" : '[rel=tooltip]'
    });

    /**
     * Chosen
     */
    $('select.chosen').chosen();

    /**
     * PlaceHolder
     */
    $('input[placeholder]').placeholder();

    /**
     * Form Collection
     */
    $(document.body).on('click', '.btn-add', function(event) {
        event.preventDefault();

        console.log($(this).data('target'));

        var collectionHolder = $(this).siblings('.' + $(this).data('target')),
            prototype = collectionHolder.data('prototype'),
            regex,
            form,
            prototype_name;

        if(collectionHolder.data('prototype-name')) {
            prototype_name = collectionHolder.data('prototype-name');
        } else {
            prototype_name = '__name__';
        }

        regex = new RegExp(prototype_name, "g");
        form = prototype.replace(regex, collectionHolder.children().length);

        collectionHolder.append(form);

        return false;
    });

    $(document.body).on('click', '.btn-remove', function(event) {
        event.preventDefault();
        var name = $(this).attr('data-related'),
            el = $(this).closest('div[data-content^="' + name + '"]');

        el.remove();

        return false;
    });
});