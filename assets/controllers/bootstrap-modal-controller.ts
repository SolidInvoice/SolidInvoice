import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLDivElement> {
    static values = {
        show: String
    }

    declare showValue: string;
    private modal: JQuery|null = null;

    connect() {
        this.modal = $(this.element);
        document.addEventListener('modal:close', () => this.modal?.modal('hide'));

        // Automatically show modal if show value is true
        if (this.showValue === 'true') {
            this.modal.modal('show');
        }
    }

    disconnect() {
        // Ensure modal and backdrop are properly removed when component is disconnected
        if (this.modal) {
            this.modal.modal('hide');
            // Remove backdrop if it still exists
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
        }
    }
}
