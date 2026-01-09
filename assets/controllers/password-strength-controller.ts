/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import { Controller } from '@hotwired/stimulus';

/**
 * Password Strength Controller
 *
 * Provides real-time password strength feedback with visual indicators
 */
export default class extends Controller {
    static targets = ['input', 'bar', 'label', 'meter'];

    declare readonly inputTarget: HTMLInputElement;
    declare readonly barTarget: HTMLElement;
    declare readonly labelTarget: HTMLElement;
    declare readonly meterTarget: HTMLElement;

    /**
     * Check password strength when user types
     */
    checkStrength(): void {
        const password = this.inputTarget.value;
        const strength = this.calculateStrength(password);

        this.updateMeter(strength);
    }

    /**
     * Calculate password strength score
     *
     * Scoring algorithm:
     * - Length: 8+ chars (25 pts), 12+ chars (+15 pts)
     * - Character variety: lowercase, uppercase, numbers, special chars (15 pts each)
     * - Total: 0-100 score
     */
    private calculateStrength(password: string): { score: number; label: string; strength: string } {
        let score = 0;

        if (!password) {
            return { score: 0, label: 'At least 8 characters', strength: '' };
        }

        // Length check
        if (password.length >= 8) score += 25;
        if (password.length >= 12) score += 15;

        // Character variety checks
        if (/[a-z]/.test(password)) score += 15; // lowercase
        if (/[A-Z]/.test(password)) score += 15; // uppercase
        if (/[0-9]/.test(password)) score += 15; // numbers
        if (/[^a-zA-Z0-9]/.test(password)) score += 15; // special chars

        let label = '';
        let strength = '';

        if (score < 40) {
            label = 'Weak password';
            strength = 'weak';
        } else if (score < 70) {
            label = 'Medium strength';
            strength = 'medium';
        } else {
            label = 'Strong password';
            strength = 'strong';
        }

        return { score, label, strength };
    }

    /**
     * Update visual meter with strength feedback
     */
    private updateMeter(result: { score: number; label: string; strength: string }): void {
        this.barTarget.style.width = `${result.score}%`;
        this.barTarget.setAttribute('data-strength', result.strength);
        this.labelTarget.textContent = result.label;

        // Show meter when typing
        if (result.score > 0) {
            this.meterTarget.style.opacity = '1';
        }
    }
}
