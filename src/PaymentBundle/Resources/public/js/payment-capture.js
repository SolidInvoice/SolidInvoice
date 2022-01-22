import $ from 'jquery';
import Module from 'SolidInvoiceCore/js/module';
import { includes } from 'lodash';

export default Module.extend({
    initialize (internal) {
        const $captureOnline = $('#payment_capture_online').parents('div.form-group');
        $captureOnline.hide();

        $('#payment_payment_method').on('change', function () {
            const val = $('option:selected', this).data('gateway');

            if (includes(internal, val)) {
                $captureOnline.hide();
            } else {
                $captureOnline.show();
            }
        });
    }
});
