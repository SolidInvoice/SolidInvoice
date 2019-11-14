import ItemView from 'SolidInvoiceCore/js/view';
import AddressModal from './address_modal';
import Template from '../../templates/address.hbs';
import Alert from 'SolidInvoiceCore/js/alert';
import Translator from 'translator';

export default ItemView.extend({
    template: Template,

    ui: {
        'deleteAddress': '.delete-address',
        'editAddress': '.edit-address'
    },

    events: {
        'click @ui.deleteAddress': 'deleteAddress',
        'click @ui.editAddress': 'editAddress'
    },

    deleteAddress(event) {
        event.preventDefault();

        Alert.confirm(Translator.trans('client.address.delete_confirm'), (confirm) => {
            if (true === confirm) {
                return this.model.destroy(
                    {
                        wait: true,
                        error(model, xhr) {
                            Alert.alert(xhr.responseJSON.message);
                        }
                    }
                );
            }
        });
    },

    editAddress(event) {
        event.preventDefault();

        this.trigger('before:model:edit');

        new AddressModal({
            model: this.model,
            route: this.$(event.currentTarget).prop('href')
        });

        this.trigger('model:edit');
    }
});
