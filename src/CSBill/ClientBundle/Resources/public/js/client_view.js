$(function(){
    $('#contacts-ajax-modal').ajaxModal('.add-contact-button', function(){

        var modal = $(this);

        $(this).on('submit', 'form', function(evt) {

            var form = $(this);

        /**
         * EDIT CONTACT
         */
        var contactEdit = function() {
            var modal = $(this.$modal),
                container = this,
                contactEditForm = function(evt) {

                    var form = $(this);
                    evt.preventDefault();
                    modal.modal('loading');

                    $.ajax({
                        "url" : form.attr('action'),
                        "dataType" : "json",
                        "data" : form.serialize(),
                        "method" : "post",
                        "success" : function(data) {

                            var contactCard = container.$trigger.parents('.contact-card');

                            if ('success' === data.status) {

                                $.getJSON(Routing.generate('_clients_contact_card', {"id" : contactCard.data('id')}), function(data) {

                                    var content = $(data.content).hide();
                                    contactCard.replaceWith(content);

                                    content.fadeIn(function(){
                                        $('.edit-contact', this).ajaxModal('#contacts-ajax-modal', contactEdit);
                                    });
                                });
                            }

                            modal.modal('loading');
                            setTimeout(function() {
                                modal.html(data.content);
                                $('form', modal).on('submit', contactEditForm);
                            }, 350);
                        }
                    });
                };

            $('form', modal).on('submit', contactEditForm);
        };

        $('.edit-contact').ajaxModal('#contacts-ajax-modal', contactEdit);
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