import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class LineTax extends Controller<HTMLElement> {
    static targets: string[] = ['row', 'compoundRow'];

    declare rowTargets: HTMLElement[];

    connect(): void {
        super.connect();
        this.refreshCompoundVisibility();
        this.refreshSequenceButtons();
    }

    rowTargetConnected(): void {
        this.refreshCompoundVisibility();
        this.refreshSequenceButtons();
    }

    rowTargetDisconnected(): void {
        this.refreshCompoundVisibility();
        this.refreshSequenceButtons();
        this.renumberSequences();
    }

    addRow(event: Event): void {
        event.preventDefault();
        const button = event.currentTarget as HTMLElement;
        button.dispatchEvent(new CustomEvent('line-tax:add', { bubbles: true }));
    }

    removeRow(event: Event): void {
        event.preventDefault();
        const button = event.currentTarget as HTMLElement;
        const row = button.closest<HTMLElement>('[data-line-tax-target="row"]');
        if (row === null) {
            return;
        }

        row.dispatchEvent(new CustomEvent('line-tax:remove', { bubbles: true, detail: { row } }));
        row.remove();
        this.refreshCompoundVisibility();
        this.refreshSequenceButtons();
        this.renumberSequences();
    }

    moveUp(event: Event): void {
        event.preventDefault();
        const button = event.currentTarget as HTMLElement;
        const row = button.closest<HTMLElement>('[data-line-tax-target="row"]');
        if (row === null || row.previousElementSibling === null) {
            return;
        }
        row.parentElement?.insertBefore(row, row.previousElementSibling);
        this.renumberSequences();
        this.refreshSequenceButtons();
    }

    moveDown(event: Event): void {
        event.preventDefault();
        const button = event.currentTarget as HTMLElement;
        const row = button.closest<HTMLElement>('[data-line-tax-target="row"]');
        if (row === null || row.nextElementSibling === null) {
            return;
        }
        row.parentElement?.insertBefore(row.nextElementSibling, row);
        this.renumberSequences();
        this.refreshSequenceButtons();
    }

    private refreshCompoundVisibility(): void {
        const showCompound = this.rowTargets.length >= 2;
        for (const row of this.rowTargets) {
            const compound = row.querySelector<HTMLElement>('[data-line-tax-target="compound"]');
            const wrapper = compound?.closest<HTMLElement>('.line-tax-compound-wrapper') ?? compound;
            if (wrapper !== null && wrapper !== undefined) {
                wrapper.hidden = ! showCompound;
            }
        }
    }

    private refreshSequenceButtons(): void {
        for (let i = 0; i < this.rowTargets.length; i++) {
            const row = this.rowTargets[i];
            const upBtn = row.querySelector<HTMLButtonElement>('[data-line-tax-up]');
            const downBtn = row.querySelector<HTMLButtonElement>('[data-line-tax-down]');
            if (upBtn !== null) {
                upBtn.disabled = i === 0;
            }
            if (downBtn !== null) {
                downBtn.disabled = i === this.rowTargets.length - 1;
            }
        }
    }

    private renumberSequences(): void {
        for (let i = 0; i < this.rowTargets.length; i++) {
            const seq = this.rowTargets[i].querySelector<HTMLInputElement>('[data-line-tax-target="sequence"]');
            if (seq !== null) {
                seq.value = String(i);
            }
        }
    }
}
