import { Controller } from '@hotwired/stimulus';
import { Modal } from '@tabler/core';

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLDivElement> {
    static values = {
        show: String
    }

    declare showValue: string;
    private modal: Modal|null = null;

    connect() {
        // Tabler's bundled Bootstrap types return BaseComponent from the static lookups,
        // so the instance has to be narrowed back to Modal at the call site.
        this.modal = Modal.getOrCreateInstance(this.element) as Modal;
        document.addEventListener('modal:close', this.close);

        // Automatically show modal if show value is true
        if (this.showValue === 'true') {
            this.modal.show();
        }
    }

    disconnect() {
        document.removeEventListener('modal:close', this.close);

        // `dispose()` takes the backdrop and the `modal-open` class on <body> with it.
        this.modal?.hide();
        this.modal?.dispose();
        this.modal = null;
    }

    private close = (): void => {
        this.modal?.hide();
    };
}
