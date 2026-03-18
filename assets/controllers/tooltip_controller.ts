/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import { Controller } from '@hotwired/stimulus';
import { Tooltip } from 'bootstrap';

export default class extends Controller {
    private tooltips: Tooltip[] = [];

    connect(): void {
        this.element.querySelectorAll<HTMLElement>('[data-bs-toggle="tooltip"]').forEach((el) => {
            // Avoid double-initializing if Tabler already did it on page load
            const existing = Tooltip.getInstance(el);
            this.tooltips.push(existing ?? new Tooltip(el));
        });
    }

    disconnect(): void {
        this.tooltips.forEach((t) => t.dispose());
        this.tooltips = [];
    }
}
