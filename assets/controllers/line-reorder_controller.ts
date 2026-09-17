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
    static values = { announcement: String };
    declare announcementValue: string;
    declare readonly hasAnnouncementValue: boolean;

    private sortable: Sortable | null = null;
    private component: Component | null = null;
    private liveRegion: HTMLElement | null = null;

    async connect() {
        // getComponent() only resolves the component's own root element, and this controller
        // lives on the list of rows inside it.
        const root = this.element.closest<HTMLElement>('[data-live-name-value]');

        if (root === null) {
            return;
        }

        this.component = await getComponent(root);
        this.liveRegion = this.createLiveRegion();

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
        this.liveRegion?.remove();
        this.liveRegion = null;
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

        // Before the bounds check, not after: an arrow key pressed on the first or last
        // handle has to do nothing, and scrolling the page out from under a control that
        // just refused to move is not nothing.
        event.preventDefault();

        const rows = this.rows();
        const from = rows.indexOf(handle.closest<HTMLElement>(ROW) as HTMLElement);
        const to = event.key === 'ArrowUp' ? from - 1 : from + 1;

        if (from === -1 || to < 0 || to >= rows.length) {
            return;
        }

        void this.move(from, to);
    }

    private async move(from: number, to: number) {
        await this.component?.action('moveLine', { from, to });

        // The re-render replaces the row the key press came from, and focus would land back on
        // the document — so put it on the handle of the line the user just moved, leaving them
        // able to press the key again.
        this.rows()[to]?.querySelector<HTMLElement>(HANDLE)?.focus();

        this.announce(to);
    }

    /*
     * Focus returns to a handle whose accessible name is the same one it had before the move,
     * so a screen reader is told nothing about what changed. Where the line landed is the
     * whole outcome of the action, so it is said out loud.
     */
    private announce(index: number) {
        if (this.liveRegion === null || !this.hasAnnouncementValue) {
            return;
        }

        this.liveRegion.textContent = this.announcementValue
            .replace('%position%', String(index + 1))
            .replace('%total%', String(this.rows().length));
    }

    /*
     * Outside the component rather than inside it: the live component re-renders the row list
     * on every move, and a region morphed away mid-announcement is not read.
     */
    private createLiveRegion(): HTMLElement {
        const region = document.createElement('div');

        region.className = 'visually-hidden';
        region.setAttribute('role', 'status');
        region.setAttribute('aria-live', 'polite');
        document.body.append(region);

        return region;
    }

    private rows(): HTMLElement[] {
        return Array.from(this.element.querySelectorAll<HTMLElement>(ROW));
    }
}
