import BaseView from 'SolidInvoiceCore/js/billing/view/base';
import Template from '../templates/table.hbs';

export default BaseView.extend({
    template: Template,
    childViewContainer: "#quote-rows"
});
