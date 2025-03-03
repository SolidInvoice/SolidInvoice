import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

/* stimulusFetch: 'lazy' */
export default class CapturePaymentController extends Controller<HTMLDivElement> {

    static targets: string[] = ['captureOnline', 'paymentMethod'];

    declare captureOnlineTarget: HTMLSelectElement;
    declare paymentMethodTarget: HTMLSelectElement;
    declare hasCaptureOnlineTarget: boolean;

    connect (): void {
        if (!this.hasCaptureOnlineTarget) {
            return;
        }

        this.captureOnlineTarget.classList.add('d-none');

        $(this.paymentMethodTarget).on('change', (e: Event): void => {

            const selectedOption: HTMLOptionElement|null = (e.target as HTMLSelectElement).querySelector('option:checked');

            if (selectedOption?.dataset.offline === undefined) {
                this.captureOnlineTarget.classList.remove('d-none');
            } else {
                this.captureOnlineTarget.classList.add('d-none');
            }
        });
    }
}
