import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.element.addEventListener('collection:add', (event: Event): void => {
            const newItem = document.createElement('button');
            newItem.setAttribute('type', 'button');
            newItem.setAttribute('data-action', 'click->form-collection#delete');
            newItem.textContent = '-';
            newItem.classList.add('btn');
            newItem.classList.add('btn-danger');
            newItem.classList.add('btn-delete');

            ((event as CustomEvent).detail.previousEntry as HTMLElement)
                .querySelector('.btn-success.btn-add')
                ?.replaceWith(newItem)
        });
    }
}
