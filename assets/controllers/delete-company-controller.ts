import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLElement> {
    static targets = ['modal', 'companyNameInput', 'confirmButton'];
    static values = { companyName: String };

    declare readonly modalTarget: HTMLElement;
    declare readonly companyNameInputTarget: HTMLInputElement;
    declare readonly confirmButtonTarget: HTMLButtonElement;
    declare readonly companyNameValue: string;

    private modal: JQuery|null = null;

    connect() {
        this.modal = $(this.modalTarget);
    }

    showModal() {
        this.modal?.modal('show');
        this.companyNameInputTarget.value = '';
        this.updateConfirmButton();
    }

    hideModal() {
        this.modal?.modal('hide');
    }

    validateInput() {
        this.updateConfirmButton();
    }

    private updateConfirmButton() {
        const isValid = this.companyNameInputTarget.value.trim() === this.companyNameValue.trim();
        this.confirmButtonTarget.disabled = !isValid;
    }
}
