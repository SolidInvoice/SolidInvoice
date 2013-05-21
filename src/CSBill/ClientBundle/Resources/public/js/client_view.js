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

        /**
         * DELETE CONTACT
         */
        $('body').on('click', '.delete-contact', function(evt) {
            evt.preventDefault();

            var contact = $(this).parents('.contact-card'),
                contactId = contact.data('id');

            bootbox.confirm('<i class="icon-exclamation-sign icon-2x"></i> Are you sure you want to delete this contact?', function(bool) {
                if (true === bool) {
                    setTimeout(function() {
                        $('body').modalmanager('loading');
                    }, 300);

                    $.post(Routing.generate("_clients_delete_contact", {"id" : contactId}), function() {
                        $('body').modalmanager('loading');
                        var div = $('<div class="alert alert-success clearfix">Contact removed successfully!</div>');
                        contact.replaceWith(div);
                        setTimeout(function() {
                            div.fadeOut('slow', function() {
                                $(this).remove();
                            });
                        }, 3000);
                    });
                }
            });

            return false;
        });
    });
});