/*
 * This file is part of SolidInvoice package.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';
import { getComponent } from '@symfony/ux-live-component';

/**
 * Handles showing/hiding modals when LiveComponent updates
 * Watches for changes to modal visibility and triggers Bootstrap modal API
 */
export default class extends Controller<HTMLElement> {
    private modalInstances: Map<string, Modal> = new Map();
    private observer: MutationObserver | null = null;
    private isShowingModal: Set<string> = new Set();

    connect(): void {
        // Initialize Bootstrap Modal instances for all modals in this component
        const modals = this.element.querySelectorAll<HTMLElement>('.modal');
        modals.forEach((modalElement) => {
            const modalId = modalElement.id;
            if (modalId) {
                const modalInstance = new Modal(modalElement);
                this.modalInstances.set(modalId, modalInstance);

                // Show modal if it has the 'show' class on connect
                if (modalElement.classList.contains('show')) {
                    modalInstance.show();
                    this.isShowingModal.add(modalId);
                }

                // Listen for when modal is hidden by Bootstrap (via dismiss button, escape key, backdrop click)
                modalElement.addEventListener('hidden.bs.modal', () => this.handleModalHidden(modalElement));
            }
        });

        // Watch for class changes on modal elements
        this.observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const target = mutation.target as HTMLElement;
                    if (target.classList.contains('modal')) {
                        this.handleModalClassChange(target);
                    }
                }
            });
        });

        // Observe all modals for class changes
        modals.forEach((modal) => {
            this.observer?.observe(modal, {
                attributes: true,
                attributeFilter: ['class'],
            });
        });
    }

    disconnect(): void {
        // Clean up modal instances
        this.modalInstances.forEach((modal) => {
            modal.dispose();
        });
        this.modalInstances.clear();
        this.isShowingModal.clear();

        // Disconnect observer
        this.observer?.disconnect();
        this.observer = null;
    }

    /**
     * Handle modal class changes
     * Show modal when 'show' class is added
     */
    private handleModalClassChange(modalElement: HTMLElement): void {
        const modalId = modalElement.id;
        const modalInstance = this.modalInstances.get(modalId);

        if (!modalInstance) {
            return;
        }

        const hasShowClass = modalElement.classList.contains('show');
        const isCurrentlyShowing = this.isShowingModal.has(modalId);

        // Only trigger show if the modal has the show class and is not already showing
        if (hasShowClass && !isCurrentlyShowing) {
            const isVisible = modalElement.classList.contains('d-block') ||
                             window.getComputedStyle(modalElement).display === 'block';

            if (!isVisible) {
                modalInstance.show();
                this.isShowingModal.add(modalId);
            }
        }
    }

    /**
     * Handle when modal is hidden by Bootstrap
     * Update LiveComponent state to reflect the modal is closed
     */
    private async handleModalHidden(modalElement: HTMLElement): Promise<void> {
        const modalId = modalElement.id;
        this.isShowingModal.delete(modalId);

        // Find which LiveProp controls this modal and set it to false
        const liveComponent = this.element;
        if (liveComponent instanceof HTMLElement && liveComponent.hasAttribute('data-live-name-value')) {
            try {
                const component = await getComponent(liveComponent);

                // Determine which modal was closed and update the corresponding LiveProp
                if (modalId === 'totp-setup-modal') {
                    await component.set('showQrModal', false);
                } else if (modalId === 'backup-codes-modal') {
                    await component.set('showBackupCodes', false);
                }
            } catch (error) {
                console.error('Failed to update LiveComponent state:', error);
            }
        }
    }
}
