import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

/**
 * Manages the API token history modal display and URL parameter cleanup
 */
export default class extends Controller<HTMLElement> {
    private modalElement: HTMLElement | null = null;
    private modalInstance: Modal | null = null;

    connect(): void {
        // Find the modal element within our wrapper
        this.modalElement = this.element.querySelector('.modal');

        if (this.modalElement) {
            // Only show modal if it has actual content (not just loading spinner)
            const hasContent = this.modalElement.querySelector('.modal-header');

            if (hasContent) {
                // Create Bootstrap modal instance and show it
                this.modalInstance = Modal.getOrCreateInstance(this.modalElement);
                this.modalInstance.show();

                // Remove view_history parameter when modal is hidden
                this.modalElement.addEventListener('hidden.bs.modal', () => {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('view_history');
                    window.location.href = url.toString();
                });
            }
        }
    }

    disconnect(): void {
        // Clean up modal instance
        if (this.modalInstance) {
            this.modalInstance.dispose();
        }
    }
}
