/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(
    ['core/module', './view/credit', './model/credit', './view/contact_collection', 'csbillclient/js/model/contact_collection'],
    function(Module, ClientCredit, ClientCreditModel, ContactView, ContactModel) {
        'use strict';

        return Module.extend({
            regions: {
                'clientCredit': '#client-credit',
                'clientContact': '#client-contacts-list'
            },
            renderCredit: function(options) {
                var model = new ClientCreditModel({
                    credit: options.credit.value,
                    id: options.id
                });

                var view = new ClientCredit({
                    model: model
                });

                this.app.getRegion('clientCredit').show(view);
            },
            renderContactCollection: function (options) {
                var collection = new ContactModel(options.contacts);

                var view = new ContactView({
                    collection: collection
                });

                this.app.getRegion('clientContact').show(view);
            },
            initialize: function(options) {
                this.renderCredit(options);
                this.renderContactCollection(options);
            }
        });
    }
);

/*
(function($, Routing, window) {
    "use strict";

    $(function() {
        /!**
         * ADD CONTACT
         *!/
        $('.add-contact-button').ajaxModal('#contacts-ajax-modal', function() {

            window.attachContactListeners();

            var modal = $(this.$modal),
                addContact = function(evt) {

                    var form = $(this);

                    evt.preventDefault();

                    modal.modal('loading');

                    $.ajax({
                        "url": form.attr('action'),
                        "dataType" : "json",
                        "data" : form.serialize(),
                        "method": "post",
                        "success" : function(data) {

                            var callback = function(func) {
                                return func.call();
                            };

                            if ('success' === data.status && undefined !== data.id) {
                                var promise = $.getJSON(Routing.generate('_clients_contact_card', {"id" : data.id}), function(data) {

                                    var content = $(data.content).hide(),
                                        contactList = $('#client-contacts-list'),
                                        contactListContainers = $('.client_contact_list', contactList);

                                    if (contactListContainers.length > 0) {
                                        content.show();
                                        content = $('<div />')
                                            .addClass('col-lg-6 col-md-6 client_contact_list')
                                            .append(content)
                                            .hide()
                                            ;
                                    }

                                    contactList.append(content);

                                    content.fadeIn(function() {

                                        if ($('.contact-card').length > 1) {
                                            $('.delete-contact.hidden').removeClass('hidden');
                                        } else {
                                            $('.delete-contact').addClass('hidden');
                                        }

                                        $('.edit-contact', this).ajaxModal('#contacts-ajax-modal', contactEdit);
                                    });
                                });

                                callback = promise.done;
                            }

                            callback(function() {
                                modal.modal('loading');
                                modal.html(data.content);
                                $('form', modal).on('submit', addContact);
                            });
                        }
                    });
                };

            $('form', modal).on('submit', addContact);
        });

        /!**
         * EDIT CONTACT
         *!/
        var contactEdit = function() {

            window.attachContactListeners();

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

                            var callback = function(func) {
                                return func.call();
                            };

                            if ('success' === data.status) {
                                var promise = $.getJSON(Routing.generate('_clients_contact_card', {"id" : contactCard.data('id')}), function(data) {

                                    var content = $(data.content).hide();
                                    contactCard.replaceWith(content);

                                    content.fadeIn(function(){
                                        $('.edit-contact', this).ajaxModal('#contacts-ajax-modal', contactEdit);
                                    });
                                });

                                callback = promise.done;
                            }

                            modal.modal('loading');

                            callback(function() {
                                modal.html(data.content);
                                $('form', modal).on('submit', contactEditForm);
                            });
                        }
                    });
                };

            $('form', modal).on('submit', contactEditForm);
        };

        $('.edit-contact').ajaxModal('#contacts-ajax-modal', contactEdit);

        /!**
         * DELETE CONTACT
         *!/
        $('body').on('click', '.delete-contact', function(evt) {
            evt.preventDefault();

            var contact = $(this).parents('.contact-card'),
                contactId = contact.data('id');

            window.bootbox.confirm({
                'message': '<i class="fa fa-exclamation-circle fa-2x"></i> Are you sure you want to delete this contact?',
                'buttons' : {
                    'cancel' : {
                        'className' : 'btn-warning btn-flat',
                        'label' : '<i class="fa fa-times"></i> Cancel'
                    },
                    'confirm' : {
                        'className' : 'btn-success btn-flat',
                        'label' : '<i class="fa fa-check"></i> OK'
                    }
                },
                'callback': function (bool) {
                    if (true === bool) {
                        $('body').modalmanager('loading');

                        $.post(Routing.generate("_clients_delete_contact", {"id": contactId}), function () {

                            $('body').modalmanager('loading');
                            var div = $('<div class="alert alert-success clearfix">Contact removed successfully!</div>');
                            contact.replaceWith(div);

                            if ($('.contact-card').length > 1) {
                                $('.delete-contact.hidden').removeClass('hidden');
                            } else {
                                $('.delete-contact').addClass('hidden');
                            }

                            setTimeout(function () {
                                div.fadeOut('slow', function () {
                                    $(this).remove();
                                });
                            }, 3000);
                        }).fail(function (xhr) {
                            $('body').modalmanager('loading');

                            window.bootbox.alert({
                                'message': '<i class="fa fa-exclamation-circle fa-2x"></i> ' + xhr.responseJSON.message,
                                'buttons': {
                                    'ok': {
                                        'className': 'btn-success btn-flat',
                                        'label': '<i class="fa fa-check"></i> OK'
                                    }
                                }
                            });
                        });
                    }
                }
            });
        });

        /!**
         * Delete Client
         *!/
        $('#delete-client').on('click', function(evt) {
            evt.preventDefault();

            var link = $(this);

            window.bootbox.confirm({
                'message': '<i class="fa fa-exclamation-circle fa-2x"></i> Are you sure you want to delete this client?',
                'buttons' : {
                    'cancel' : {
                        'className' : 'btn-warning btn-flat',
                        'label' : '<i class="fa fa-times"></i> Cancel'
                    },
                    'confirm' : {
                        'className' : 'btn-success btn-flat',
                        'label' : '<i class="fa fa-check"></i> OK'
                    }
                },
                'callback': function (bool) {
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
                }
            });
        });
    });
})(window.jQuery, window.Routing, window);*/
