require(['client/create', 'marionette', 'util/form/collection', 'client/contacts_collection'], function (Create, Mn, FormCollection, ContactCollection) {
    describe('it instantiates contact and form collection', function() {
        var client = new Create({}, new (Mn.Application.extend({})));

        it('contactCollection is an instance of ContactCollection', function() {
            expect(client.contactCollection).toBeInstanceOf(ContactCollection);
        });

        it('formCollection is an instance of FormCollection', function() {
            expect(client.formCollection).toBeInstanceOf(FormCollection);
        });
    });
});
