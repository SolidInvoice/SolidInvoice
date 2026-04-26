import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLElement> {
    static values = { url: String };
    declare urlValue: string;

    private sortable: Sortable | null = null;

    connect() {
        this.sortable = Sortable.create(this.element, {
            handle: '[data-handle]',
            animation: 150,
            onEnd: () => this.persist(),
        });
    }

    disconnect() {
        this.sortable?.destroy();
        this.sortable = null;
    }

    private async persist() {
        const items = Array.from(this.element.querySelectorAll<HTMLElement>('[data-id]'));
        const payload = items.map((el, i) => ({ id: el.dataset.id, position: i }));

        const res = await fetch(this.urlValue, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
            credentials: 'same-origin',
        });

        if (!res.ok) {
            console.error('Reorder failed:', await res.text());
        }
    }
}
