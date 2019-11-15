import Create from 'SolidInvoiceClient/js/create';
import { Application } from 'backbone.marionette';
import FormCollection from 'SolidInvoiceCore/js/util/form/collection';
import ContactCollection from '../js/contacts_collection';

describe('it instantiates contact and form collection', function() {
    const client = new Create({}, new (Application.extend({})));

    it('contactCollection is an instance of ContactCollection', function() {
        expect(client.contactCollection).toBeInstanceOf(ContactCollection);
    });

    it('formCollection is an instance of FormCollection', function() {
        expect(client.formCollection).toBeInstanceOf(FormCollection);
    });
});
