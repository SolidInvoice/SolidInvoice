(function($){

    $.fn.dropdownSelect = function(option) {

        var defaults = {
            "groupSelector"     : ".btn-group",
            "dropdownSelector"  : ".dropdown-menu a",
            "listElement"       : "li",
            "valueData"         : "value",
            "buttonElement"     : "button span:first-child",
            "input"             : false,
            "value"             : 0
        };

        var options = $.extend(defaults, option);

        $(this).each(function(){

            var group = $(this).siblings(options.groupSelector);

            if(0 !== options.value) {
                var selected = $(options.dropdownSelector, group).filter(function(){
                    return $(this).closest(options.listElement).data('value') == options.value;
                });

                if(undefined !== selected) {
                    $(options.buttonElement, group).html(selected.html());
                }
            }

            $(group).on('click', options.dropdownSelector, function(evt) {
                evt.preventDefault();

                var link = $(this),
                    type = $(this).parents(options.listElement).data(options.valueData);

                $(options.buttonElement, group).html(link.html());

                if(options.input) {
                    $(options.input).val(type);
                }
            });
        });
    }
})(window.jQuery);