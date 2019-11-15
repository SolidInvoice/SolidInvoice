import $ from 'jquery';
import ItemView from 'SolidInvoiceCore/js/view';
import { isEmpty, isUndefined } from 'lodash';
import Router from 'router';

export default ItemView.extend({
    clientForm: null,
    ui: {
        'clientChange': '#client-select-change',
        'clientSelect': '#client-select'
    },
    events: {
        'click @ui.clientChange': 'clientChange',
        'change @ui.clientSelect': 'clientSelect'
    },
    initialize () {
        this.template = () => {
            return this.getOption('clientForm');
        };
    },
    onRender () {
        if (!this.model.isEmpty()) {
            this.ui.clientSelect.hide();
        }
        this.selectDefaultClientContact();
    },
    clientChange (event) {
        event.preventDefault();

        this._toggleContactInfo();
    },
    clientSelect (event) {
        let val = $(event.target).val();
        if (parseInt(val, 10) === parseInt(this.model.id, 10)) {
            this._toggleContactInfo();
            return;
        }

        if (isEmpty(val)) {
            return;
        }

        this.showLoader();

        $.getJSON(
            Router.generate('_xhr_clients_info', { id: val, type: this.getOption('type') }),
            (data) => {
                this.$('#client-select-container').html(data.content);
                this._toggleContactInfo(true);

                if (this.options.hideLoader) {
                    this.hideLoader();
                }

                if (data.currency) {
                    data.client = val;
                    this.trigger('currency:update', data);
                }
            }
        );
    },
    _toggleContactInfo (show) {
        const clientSelect = this.$('#client-select');
        const clientSelectContainer = this.$('#client-select-container');
        clientSelect.toggle();

        if (clientSelect.is(':visible')) {
            // eslint-disable-next-line
            clientSelect.find('select').select2().select2('val', 0);
        }

        if (!isUndefined(show)) {
            if (true === show) {
                clientSelectContainer.show();
            } else {
                clientSelectContainer.hide();
            }
        } else {
            clientSelectContainer.toggle();
        }
    },
    selectDefaultClientContact () {
        const clientSelectContainer = this.$('#client-select-container');
        // eslint-disable-next-line
        const clientSelectContainerCheckbox = clientSelectContainer.find('input[type="checkbox"]');
        if (1 === clientSelectContainerCheckbox.length) {
            clientSelectContainerCheckbox.prop('checked', true);
        }
    }
});
