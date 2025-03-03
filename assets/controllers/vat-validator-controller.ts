import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class VatValidator extends Controller<HTMLDivElement> {
    static values = {
        url: String,
    }

    static targets: string[] = ['input', 'button']

    declare urlValue: string

    declare inputTarget: HTMLInputElement
    declare buttonTarget: HTMLInputElement

    connect () {
        super.connect();

        this.inputTarget.addEventListener('change', () => {
            this.inputTarget.classList.remove('is-valid')
            this.inputTarget.classList.remove('is-invalid')
        })
    }

    async validate(e: Event) {
        e.preventDefault();

        const originalText = this.buttonTarget.innerHTML

        this.buttonTarget.innerHTML = '<i class="fas fa-spin fa-refresh"></i>'

        const response = await fetch(this.urlValue, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                vat_number: this.inputTarget.value,
            }),
        })

        const data = await response.json()

        this.inputTarget.classList.remove('is-valid')
        this.inputTarget.classList.remove('is-invalid')
        this.inputTarget.classList.add(data.valid ? 'is-valid' : 'is-invalid')

        this.buttonTarget.innerHTML = originalText
    }
}
