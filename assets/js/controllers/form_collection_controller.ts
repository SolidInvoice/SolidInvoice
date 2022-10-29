import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        prototypeName: String,
        itemSelector: String,
    }

    // @ts-ignore
    declare readonly element: HTMLElement;
    declare readonly prototypeNameValue: string;
    declare readonly itemSelectorValue: string;

    controllerName = 'collection';

    connect() {
        this.controllerName = this.context.scope.identifier;
        this._dispatchEvent('collection:connect');
    }

    add(event: Event) {
        event.preventDefault();

        const prototypeHTML = this.element.dataset.prototype;

        if (!prototypeHTML) {
            throw new Error(
                'A "data-prototype" attribute was expected on data-controller="' + this.controllerName + '" element.'
            );
        }

        const entries: Element[] = [];
        this.element.querySelectorAll(
            this.itemSelectorValue
                ? this.itemSelectorValue.replace('%controllerName%', this.controllerName)
                : ':scope > [data-' + this.controllerName + '-target="entry"]:not([data-controller] > *)'
        ).forEach(entry => {
            entries.push(entry);
        });

        // replace only first appearance of prototypeNameValue to support nested blocks with same prototypeName
        const collectionNamePattern = this.element.id.replace(/_/g, '(?:_|\\[|]\\[)');
        const newEntry = this._textToNode(
            prototypeHTML
                .replace(this.prototypeNameValue  + 'label__', entries.length.toString())
                .replace(
                    new RegExp(`${collectionNamePattern}${this.prototypeNameValue}`, 'g'),
                    entries.length.toString()
                )
        );

        this._dispatchEvent('collection:pre-add', {
            previousEntry: this.element,
            entry: newEntry,
            index: entries.length,
        });

        if (0 < entries.length) {
            entries[entries.length - 1].after(newEntry);
        } else {
            this.element.prepend(newEntry);
        }

        this._dispatchEvent('collection:add', {
            previousEntry: this.element,
            entry: newEntry,
            index: entries.length,
        });
    }

    delete(event: MouseEvent) {
        event.preventDefault();

        const clickTarget = event.target as HTMLButtonElement;

        const entry = clickTarget.closest('[data-' + this.controllerName + '-target="entry"]') as HTMLElement;

        this._dispatchEvent('collection:pre-delete', {
            entry: entry,
        });

        entry.remove();

        this._dispatchEvent('collection:delete', {
            entry: entry,
        });
    }

    _textToNode(text: string): HTMLElement {
        const template = document.createElement('template');
        text = text.trim();

        template.innerHTML = text;

        return template.content.firstChild as HTMLElement;
    }

    _dispatchEvent(name: string, payload: {} = {}) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload, bubbles: true }));
    }
}
