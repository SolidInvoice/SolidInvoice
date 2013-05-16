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
});