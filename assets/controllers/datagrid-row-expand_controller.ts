import { Controller } from '@hotwired/stimulus';

/**
 * Handles expandable row details in DataGrid tables
 */
export default class extends Controller<HTMLTableRowElement> {
    static targets = ['expandIcon'];

    declare readonly expandIconTarget: HTMLElement;
    private detailRow: HTMLTableRowElement | null = null;

    connect(): void {
        // Find the next sibling row (the detail row)
        this.detailRow = this.element.nextElementSibling as HTMLTableRowElement;
    }

    toggle(event: Event): void {
        event.preventDefault();

        if (!this.detailRow) {
            return;
        }

        const isExpanded = this.element.classList.contains('expanded');

        if (isExpanded) {
            this.collapse();
        } else {
            this.expand();
        }
    }

    expand(): void {
        if (!this.detailRow) {
            return;
        }

        this.element.classList.add('expanded');
        this.detailRow.classList.add('visible');
        this.expandIconTarget.style.transform = 'rotate(90deg)';
    }

    collapse(): void {
        if (!this.detailRow) {
            return;
        }

        this.element.classList.remove('expanded');
        this.detailRow.classList.remove('visible');
        this.expandIconTarget.style.transform = 'rotate(0deg)';
    }
}
