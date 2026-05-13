import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class InvoiceTax extends Controller<HTMLElement> {
    static targets: string[] = ['row', 'direction', 'tax', 'note', 'sequence', 'emptyState'];

    declare rowTargets: HTMLElement[];
    declare hasEmptyStateTarget: boolean;
    declare emptyStateTarget: HTMLElement;

    connect(): void {
        super.connect();
        this.refreshEmptyState();
        this.refreshRowDecorations();
    }

    rowTargetConnected(): void {
        this.refreshEmptyState();
        this.refreshRowDecorations();
    }

    rowTargetDisconnected(): void {
        this.refreshEmptyState();
        this.renumberSequences();
    }

    addRow(event: Event): void {
        event.preventDefault();
        const button = event.currentTarget as HTMLElement;
        button.dispatchEvent(new CustomEvent('invoice-tax:add', { bubbles: true }));
    }

    removeRow(event: Event): void {
        event.preventDefault();
        const button = event.currentTarget as HTMLElement;
        const row = button.closest<HTMLElement>('[data-invoice-tax-target="row"]');
        if (row === null) {
            return;
        }

        row.dispatchEvent(new CustomEvent('invoice-tax:remove', { bubbles: true, detail: { row } }));
        row.remove();
        this.refreshEmptyState();
        this.renumberSequences();
    }

    directionChanged(event: Event): void {
        const select = event.currentTarget as HTMLSelectElement;
        const row = select.closest<HTMLElement>('[data-invoice-tax-target="row"]');
        if (row !== null) {
            this.applyDirectionStyling(row, select.value);
        }
    }

    private refreshEmptyState(): void {
        if (! this.hasEmptyStateTarget) {
            return;
        }
        this.emptyStateTarget.hidden = this.rowTargets.length > 0;
    }

    private refreshRowDecorations(): void {
        for (const row of this.rowTargets) {
            const direction = row.querySelector<HTMLSelectElement>('[data-invoice-tax-target="direction"]');
            if (direction !== null) {
                this.applyDirectionStyling(row, direction.value);
            }
        }
    }

    private applyDirectionStyling(row: HTMLElement, direction: string): void {
        row.dataset.direction = direction;

        const note = row.querySelector<HTMLElement>('[data-invoice-tax-target="note"]');
        const noteWrapper = note?.closest<HTMLElement>('.invoice-tax-note') ?? note;
        if (noteWrapper !== null && noteWrapper !== undefined) {
            // Note field is most useful for Informational rows (reverse-charge VAT
            // disclosures); still visible for other directions.
            noteWrapper.classList.toggle('invoice-tax-note--prominent', direction === 'Informational');
        }
    }

    private renumberSequences(): void {
        for (let i = 0; i < this.rowTargets.length; i++) {
            const seq = this.rowTargets[i].querySelector<HTMLInputElement>('[data-invoice-tax-target="sequence"]');
            if (seq !== null) {
                seq.value = String(i);
            }
        }
    }
}
