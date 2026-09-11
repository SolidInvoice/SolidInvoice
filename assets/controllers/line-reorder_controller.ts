import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';
import { getComponent, type Component } from '@symfony/ux-live-component';

/*
 * Drag-to-reorder for the invoice, recurring invoice and quote line editors.
 *
 * The drop only tells the live component which row moved where; the component reorders the
 * submitted values and re-renders. Leaving the reordered DOM alone would look right until the
 * next re-render — editing a price, adding a tax — and then silently snap back.
 */
/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLElement> {
    private sortable: Sortable | null = null;
    private component: Component | null = null;

    async connect() {
        // getComponent() only resolves the component's own root element, and this controller
        // lives on the list of rows inside it.
        const root = this.element.closest<HTMLElement>('[data-live-name-value]');

        if (root === null) {
            return;
        }

        this.component = await getComponent(root);

        this.sortable = Sortable.create(this.element, {
            handle: '[data-line-reorder-handle]',
            draggable: '[data-line-reorder-row]',
            animation: 150,
            onEnd: ({ oldIndex, newIndex }) => {
                if (oldIndex === undefined || newIndex === undefined || oldIndex === newIndex) {
                    return;
                }

                this.component?.action('moveLine', { from: oldIndex, to: newIndex });
            },
        });
    }

    disconnect() {
        this.sortable?.destroy();
        this.sortable = null;
        this.component = null;
    }
}
