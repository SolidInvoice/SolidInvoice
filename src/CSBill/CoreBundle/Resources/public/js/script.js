$(function(){
    $('body').tooltip({
        "selector" : '[rel=tooltip]'
    });

    $('select.chosen').chosen();

    $('input[placeholder]').placeholder();
});