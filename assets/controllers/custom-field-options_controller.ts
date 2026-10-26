import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLElement> {
    static targets = ['list', 'row'];
    static values = {
        prototype: String,
        index: Number,
        selectTypes: Array,
        typeTargetId: String,
    };

    declare listTarget: HTMLElement;
    declare rowTargets: HTMLElement[];
    declare prototypeValue: string;
    declare indexValue: number;
    declare selectTypesValue: string[];
    declare typeTargetIdValue: string;

    private typeSelect: HTMLSelectElement | null = null;
    private boundTypeListener: ((e: Event) => void) | null = null;

    connect() {
        this.typeSelect = document.getElementById(this.typeTargetIdValue) as HTMLSelectElement | null;
        if (this.typeSelect !== null) {
            this.boundTypeListener = () => this.refreshVisibility();
            this.typeSelect.addEventListener('change', this.boundTypeListener);
        }
        this.refreshVisibility();
    }

    disconnect() {
        if (this.typeSelect !== null && this.boundTypeListener !== null) {
            this.typeSelect.removeEventListener('change', this.boundTypeListener);
        }
    }

    add(event: Event) {
        event.preventDefault();
        const placeholder = '__option_name__';
        const html = this.prototypeValue.replace(new RegExp(placeholder, 'g'), String(this.indexValue));
        this.indexValue += 1;

        const template = document.createElement('template');
        template.innerHTML = html.trim();
        const protoNode = template.content.firstElementChild;

        const row = document.createElement('div');
        row.className = 'd-flex align-items-center mb-2';
        row.setAttribute('data-custom-field-options-target', 'row');

        const inputWrapper = document.createElement('div');
        inputWrapper.className = 'flex-fill';
        if (protoNode !== null) {
            inputWrapper.appendChild(protoNode);
        }

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-link text-danger ms-2';
        removeBtn.setAttribute('data-action', 'custom-field-options#remove');
        removeBtn.setAttribute('title', 'Remove');
        removeBtn.textContent = '×';

        row.appendChild(inputWrapper);
        row.appendChild(removeBtn);
        this.listTarget.appendChild(row);

        const input = row.querySelector<HTMLInputElement>('input');
        input?.focus();
    }

    remove(event: Event) {
        event.preventDefault();
        const button = event.currentTarget as HTMLElement;
        const row = button.closest<HTMLElement>('[data-custom-field-options-target="row"]');
        row?.remove();
    }

    private refreshVisibility() {
        if (this.typeSelect === null) {
            return;
        }
        const showsOptions = this.selectTypesValue.includes(this.typeSelect.value);
        this.element.hidden = ! showsOptions;
    }
}
