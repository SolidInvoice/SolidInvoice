import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLElement> {
    static targets = ['modal', 'companyNameInput', 'confirmButton'];
    static values = { companyName: String };

    declare readonly modalTarget: HTMLElement;
    declare readonly companyNameInputTarget: HTMLInputElement;
    declare readonly confirmButtonTarget: HTMLButtonElement;
    declare readonly companyNameValue: string;

    private modalInstance: Modal | null = null;

    connect() {
        if (this.modalTarget) {
            this.modalInstance = Modal.getOrCreateInstance(this.modalTarget);
        }
    }

    disconnect() {
        if (this.modalInstance) {
            this.modalInstance.dispose();
        }
    }

    showModal() {
        if (this.modalInstance) {
            this.modalInstance.show();
        }
        this.companyNameInputTarget.value = '';
        this.updateConfirmButton();
    }

    hideModal() {
        if (this.modalInstance) {
            this.modalInstance.hide();
        }
    }

    validateInput() {
        this.updateConfirmButton();
    }

    private updateConfirmButton() {
        const isValid = this.companyNameInputTarget.value.trim() === this.companyNameValue.trim();
        this.confirmButtonTarget.disabled = !isValid;
    }
}
