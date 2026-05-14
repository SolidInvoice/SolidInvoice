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
    declare readonly hasButtonTarget: boolean

    connect () {
        super.connect();

        this.inputTarget.addEventListener('change', () => {
            this.inputTarget.classList.remove('is-valid')
            this.inputTarget.classList.remove('is-invalid')
        })

        this.refreshButtonVisibility()
    }

    async validate(e: Event) {
        e.preventDefault();

        if (! this.isVatLabel()) {
            return
        }

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

    private isVatLabel(): boolean {
        const label = this.element.dataset.taxIdentifierLabel
            ?? this.inputTarget.dataset.taxIdentifierLabel

        if (label === undefined) {
            return true
        }

        return label.trim().toUpperCase() === 'VAT'
    }

    private refreshButtonVisibility(): void {
        if (! this.hasButtonTarget) {
            return
        }

        this.buttonTarget.hidden = ! this.isVatLabel()
    }
}
