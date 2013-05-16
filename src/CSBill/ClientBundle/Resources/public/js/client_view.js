$(function(){
    $('#contacts-ajax-modal').ajaxModal('.add-contact-button', function(){

        var modal = $(this);

        $(this).on('submit', 'form', function(evt) {

            var form = $(this);
            evt.preventDefault();
            modal.modal('loading');

            $.post(form.attr('action'), form.serialize()).done(function(data) {
                modal.modal('loading');
                setTimeout(function(){
                    modal.html(data);
                }, 350);
            });

            return false;
        });
    });
});