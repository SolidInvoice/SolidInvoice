import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class BillingIdController extends Controller<HTMLDivElement> {

    static targets: string[] = ['value', 'form', 'info'];

    declare valueTarget: HTMLSpanElement;
    declare formTarget: HTMLDivElement;
    declare infoTarget: HTMLSpanElement;

    private originalValue: string = '';

    connect() {
        this.originalValue = this.valueTarget.innerText;
    }

    edit(e: Event) {
        e.preventDefault();
        this.infoTarget.classList.add('d-none');
        this.formTarget.classList.remove('d-none');
    }

    save(e: Event) {
        e.preventDefault();

        const value: string = this.formTarget.querySelector('input')?.value ?? '';

        if (value.length === 0) {

            if (this.formTarget.querySelector('.invalid-feedback')) {
                return;
            }

            const errorHtml = `<span class="invalid-feedback d-block"><span class="d-block">
                    <span class="form-error-icon badge badge-danger text-uppercase">Error</span> <span class="form-error-message">This value should not be blank.</span>
                </span></span>`;

            this.formTarget.querySelector('.input-group')?.insertAdjacentHTML('afterend', errorHtml);
        } else {
            this.formTarget.querySelector('.invalid-feedback')?.remove();

            this.valueTarget.innerText = value;

            this.infoTarget.classList.remove('d-none');
            this.formTarget.classList.add('d-none');
        }
    }

    cancel(e: Event) {
        e.preventDefault();
        this.infoTarget.classList.remove('d-none');
        this.formTarget.classList.add('d-none');

        this.formTarget.querySelector('.invalid-feedback')?.remove();
        const input = this.formTarget.querySelector('input');

        if (input) {
            input.value = this.originalValue;
        }
    }
}
