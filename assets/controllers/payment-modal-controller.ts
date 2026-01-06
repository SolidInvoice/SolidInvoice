import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        // Add ESC key listener when modal is opened
        this.handleEscKey = this.handleEscKey.bind(this);
        document.addEventListener('keydown', this.handleEscKey);

        // Focus trap - focus the first focusable element
        this.focusFirstElement();
    }

    disconnect() {
        // Clean up ESC key listener when modal is closed
        document.removeEventListener('keydown', this.handleEscKey);
    }

    handleEscKey(event: KeyboardEvent) {
        if (event.key === 'Escape') {
            // Find the close link and click it to trigger LiveComponent navigation
            const closeLink = this.element.querySelector('.payment-modal-close') as HTMLAnchorElement;
            if (closeLink) {
                closeLink.click();
            }
        }
    }

    focusFirstElement() {
        // Find the first focusable element in the modal
        const focusableElements = this.element.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );

        if (focusableElements.length > 0) {
            (focusableElements[0] as HTMLElement).focus();
        }
    }
}
