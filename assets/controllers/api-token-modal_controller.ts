import { Context, Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';
import { getComponent } from '@symfony/ux-live-component';

/**
 * Coordinates the modal hide/show sequence for API token creation
 * to ensure Bootstrap modal cleanup completes before LiveComponent re-renders
 */
export default class extends Controller<HTMLElement> {
    private shouldClearOnHide: boolean = false;
    private modalElement: HTMLElement | null = null;
    private boundHandleModalHidden: () => Promise<void>;

    constructor(context: Context) {
        super(context);
        this.boundHandleModalHidden = this.handleModalHidden.bind(this);
    }

    connect(): void {
        // Find the actual modal element within our wrapper
        this.modalElement = this.element.querySelector('.modal');

        if (this.modalElement) {
            // Listen for when modal is fully hidden on the modal element itself
            this.modalElement.addEventListener('hidden.bs.modal', this.boundHandleModalHidden);
        }
    }

    disconnect(): void {
        if (this.modalElement) {
            this.modalElement.removeEventListener('hidden.bs.modal', this.boundHandleModalHidden);
        }
    }

    confirmAndClose(event: Event): void {
        event.preventDefault();

        if (!this.modalElement) {
            return;
        }

        // Set flag that we should clear state after modal hides
        this.shouldClearOnHide = true;

        // Close the modal - Bootstrap will handle animation and cleanup
        const modalInstance = Modal.getInstance(this.modalElement);
        if (modalInstance) {
            modalInstance.hide();
        }
    }

    private async handleModalHidden(): Promise<void> {
        // Only clear state if we initiated the close via the confirm button
        if (this.shouldClearOnHide) {
            this.shouldClearOnHide = false;

            // Modal is now fully hidden and cleaned up
            // Find the LiveComponent and trigger clearToken action
            const liveComponent = this.element.closest('[data-controller*="live"]');
            if (liveComponent instanceof HTMLElement) {
                // Use official Symfony UX LiveComponent API
                const component = await getComponent(liveComponent);
                await component.action('clearToken');
            }
        }
    }
}
