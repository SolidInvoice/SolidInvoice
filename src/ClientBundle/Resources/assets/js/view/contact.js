import ItemView from 'SolidInvoiceCore/js/view';
import ContactModal from './contact_modal';
import Template from '../../templates/contact.hbs';
import Alert from 'SolidInvoiceCore/js/alert';
import Translator from 'translator';

export default ItemView.extend({
    template: Template,
    className: 'col-6 py-2',
    templateContext () {
        return {
            'canDelete': 1 < this.model.collection.length
        };
    },

    ui: {
        'deleteContact': '.delete-contact',
        'editContact': '.edit-contact'
    },

    events: {
        'click @ui.deleteContact': 'deleteContact',
        'click @ui.editContact': 'editContact'
    },

    initialize () {
        this.listenTo(this.model, 'sync', this.modelSynced);
    },

    modelSynced () {
        this.render();
    },

    deleteContact (event) {
        event.preventDefault();

        Alert.confirm(Translator.trans('client.contact.delete_confirm'), (confirm) => {
            if (true === confirm) {
                return this.model.destroy(
                    {
                        wait: true,
                        error (model, xhr) {
                            Alert.alert(xhr.responseJSON);
                        }
                    }
                );
            }
        });
    },

    editContact (event) {
        event.preventDefault();

        this.trigger('before:model:edit');

        new ContactModal({
            model: this.model,
            route: this.$(event.currentTarget).prop('href')
        });

        this.trigger('model:edit');
    }
});
