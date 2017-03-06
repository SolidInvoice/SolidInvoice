/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(
    ['marionette', 'client/model/contact', 'client/view/contact_modal', 'template'],
    function(Mn, Contact, ContactModal, Template) {
	"use strict";

	return Mn.LayoutView.extend({
	    template: Template.client.info,
	    contactCollection: null,
	    regions: {
		'clientContact': '#client-contacts-list',
		'clientAddress': '#client-address-list'
	    },
	    ui: {
		'addContact': '#add-contact-button'
	    },
	    events: {
		'click @ui.addContact': 'addContact'
	    },
	    addContact: function(event) {
		event.preventDefault();

		var collection = this.contactCollection;

		var modal = ContactModal.extend({
		    initialize: function () {
			this.listenTo(this, 'ajax:response', function (response) {
			    collection.add(new Contact(response.contact));
			});
		    }
		});

		new modal({
		    route: this.$(event.currentTarget).prop('href')
		});
	    },
	    renderContactsRegion: function(view) {
		this.contactCollection = view.collection;

		this.getRegion('clientContact').show(view);
	    }
	});
    });