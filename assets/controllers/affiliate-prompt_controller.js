/* global document, fetch, URLSearchParams */
import { Controller } from '@hotwired/stimulus';
import { Toast } from '@tabler/core';

/*
 * The one-off affiliate prompt in the corner of the dashboard.
 *
 * However it closes — the close button, "Not now", Escape or the join button — the dismissal is
 * saved in the background, so it never comes back and the page never reloads.
 */
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        dismissUrl: String,
        token: String,
    };

    #toast = null;
    #dismissed = false;

    connect() {
        // Never compete with a modal that is already open; the prompt waits for the next visit.
        if (document.querySelector('.modal.show')) {
            return;
        }

        this.#toast = Toast.getOrCreateInstance(this.element, { autohide: false });
        this.element.addEventListener('hidden.bs.toast', this.#dismiss);
        document.addEventListener('keydown', this.#closeOnEscape);
        this.#toast.show();
    }

    disconnect() {
        this.element.removeEventListener('hidden.bs.toast', this.#dismiss);
        document.removeEventListener('keydown', this.#closeOnEscape);
        this.#toast?.dispose();
        this.#toast = null;
    }

    close() {
        this.#toast?.hide();
    }

    #closeOnEscape = (event) => {
        // An Escape meant for a modal opened on top must not dismiss the prompt for good.
        if (event.key === 'Escape' && !document.querySelector('.modal.show')) {
            this.close();
        }
    };

    #dismiss = () => {
        if (this.#dismissed) {
            return;
        }

        this.#dismissed = true;

        // keepalive, because the join button can take the user away from the page mid-request.
        fetch(this.dismissUrlValue, {
            method: 'POST',
            body: new URLSearchParams({ _token: this.tokenValue }),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            keepalive: true,
        });
    };
}
