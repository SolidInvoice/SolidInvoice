/*
 * This file is part of SolidInvoice package.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import { Controller } from '@hotwired/stimulus';

/**
 * Controller to handle downloading backup codes as a file
 * Listens for 'download:file' events from the TwoFactorSettings LiveComponent
 */
export default class extends Controller {
    connect(): void {
        // Listen for download event from LiveComponent
        this.element.addEventListener('download:file', this.handleDownload.bind(this) as EventListener);
    }

    disconnect(): void {
        // Clean up event listener
        this.element.removeEventListener('download:file', this.handleDownload.bind(this) as EventListener);
    }

    /**
     * Handle download:file event
     */
    private handleDownload(event: CustomEvent): void {
        const { content, filename, type } = event.detail;

        if (!content || !filename) {
            console.error('Download failed: Missing required parameters');
            return;
        }

        this.downloadFile(content, filename, type || 'text/plain');
    }

    /**
     * Download a file using the Blob API
     */
    private downloadFile(content: string, filename: string, type: string): void {
        try {
            // Create a Blob from the content
            const blob = new Blob([content], { type });
            const url = URL.createObjectURL(blob);

            // Create a temporary anchor element
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            link.style.display = 'none';

            // Append to body, click, and clean up
            document.body.appendChild(link);
            link.click();

            // Clean up
            setTimeout(() => {
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            }, 100);
        } catch (error) {
            console.error('Download failed:', error);
        }
    }
}
