import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';
import { getComponent, type Component } from '@symfony/ux-live-component';

const ROW = '[data-line-reorder-row]';
const HANDLE = '[data-line-reorder-handle]';

/*
 * Drag-to-reorder for the invoice, recurring invoice and quote line editors.
 *
 * The drop only tells the live component which row moved where; the component reorders the
 * submitted values and re-renders. Leaving the reordered DOM alone would look right until the
 * next re-render — editing a price, adding a tax — and then silently snap back.
 *
 * A pointer is not the only way in: the same move is on the arrow keys, so the handle is a
 * real control for someone who never picks up a mouse, and not just a grip that happens to
 * take focus.
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
            handle: HANDLE,
            draggable: ROW,
            animation: 150,
            // The header row is a child of this list too, so oldIndex/newIndex — which count
            // every child — are each one ahead of the line the server knows about. The
            // *Draggable* pair counts only the rows.
            onEnd: ({ oldDraggableIndex, newDraggableIndex }) => {
                if (oldDraggableIndex === undefined || newDraggableIndex === undefined || oldDraggableIndex === newDraggableIndex) {
                    return;
                }

                void this.move(oldDraggableIndex, newDraggableIndex);
            },
        });
    }

    disconnect() {
        this.sortable?.destroy();
        this.sortable = null;
        this.component = null;
    }

    /*
     * Arrow up and down move the focused line one place. Listened for on the list rather than
     * bound to each handle, so a row added after this controller connected is covered too.
     */
    keydown(event: KeyboardEvent) {
        if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') {
            return;
        }

        const target = event.target as HTMLElement | null;
        const handle = target?.closest<HTMLElement>(HANDLE);

        if (handle === null || handle === undefined) {
            return;
        }

        const rows = this.rows();
        const from = rows.indexOf(handle.closest<HTMLElement>(ROW) as HTMLElement);
        const to = event.key === 'ArrowUp' ? from - 1 : from + 1;

        if (from === -1 || to < 0 || to >= rows.length) {
            return;
        }

        // Otherwise the page scrolls under the line that just moved.
        event.preventDefault();

        void this.move(from, to);
    }

    private async move(from: number, to: number) {
        await this.component?.action('moveLine', { from, to });

        // The re-render replaces the row the key press came from, and focus would land back on
        // the document — so put it on the handle of the line the user just moved, leaving them
        // able to press the key again.
        this.rows()[to]?.querySelector<HTMLElement>(HANDLE)?.focus();
    }

    private rows(): HTMLElement[] {
        return Array.from(this.element.querySelectorAll<HTMLElement>(ROW));
    }
}
