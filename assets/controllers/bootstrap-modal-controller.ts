import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLDivElement> {
    private modal: JQuery|null = null;

    connect() {
        this.modal = $(this.element);
        document.addEventListener('modal:close', () => this.modal?.modal('hide'));
    }
}
