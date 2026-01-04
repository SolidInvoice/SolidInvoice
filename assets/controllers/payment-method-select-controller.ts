import { Controller } from '@hotwired/stimulus';

/**
 * Controller for payment method card selection
 * Handles visual selection and shows/hides capture online toggle based on payment method type
 *
 * @stimulusFetch lazy
 */
export default class PaymentMethodSelectController extends Controller<HTMLDivElement> {
    static targets: string[] = ['card', 'captureOnline'];

    declare cardTargets: HTMLLabelElement[];
    declare captureOnlineTarget: HTMLElement;
    declare hasCaptureOnlineTarget: boolean;

    connect(): void {
        // Initialize: check if any payment method is pre-selected
        this.updateCaptureOnlineVisibility();
    }

    /**
     * Called when a payment method card is selected
     */
    select(event: Event): void {
        const input = event.target as HTMLInputElement;
        const selectedCard = input.closest('.payment-method-card') as HTMLLabelElement;

        if (!selectedCard) {
            return;
        }

        this.updateCaptureOnlineVisibility();
    }

    /**
     * Shows or hides the capture online toggle based on the selected payment method
     */
    private updateCaptureOnlineVisibility(): void {
        if (!this.hasCaptureOnlineTarget) {
            return;
        }

        // Find the selected card
        const selectedCard = this.cardTargets.find(card => {
            const input = card.querySelector('input[type="radio"]') as HTMLInputElement;
            return input?.checked;
        });

        if (!selectedCard) {
            // No selection, hide capture online
            this.captureOnlineTarget.classList.add('d-none');
            return;
        }

        const isOffline = selectedCard.dataset.offline === 'true';

        if (isOffline) {
            // Offline payment method - hide capture online toggle
            this.captureOnlineTarget.classList.add('d-none');
        } else {
            // Online payment method - show capture online toggle
            this.captureOnlineTarget.classList.remove('d-none');
        }
    }
}
