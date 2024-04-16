import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class MailsettingsController extends Controller<HTMLDivElement> {

    static targets: string[] = ['provider'];

    declare providerTarget: HTMLSelectElement;

    connect (): void {
        this.providerTarget.addEventListener('change', (e: Event): void => {
            this.element.querySelectorAll('[data-provider]').forEach((el: Element): void => {
                el.classList.add('d-none');
            });

            const target: string = (e.target as HTMLInputElement).value.replace(' ', '-');

            this.element.querySelector(`[data-provider="${target}"]`)?.classList.remove('d-none')
        });
    }
}
