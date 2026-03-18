import { Controller } from '@hotwired/stimulus';

/**
 * Duration input controller.
 *
 * Enforces HH:MM format on a plain text input that stores a duration.
 * - Only digits and ':' are accepted while typing.
 * - The colon is auto-inserted after two hours digits are entered.
 * - On blur the value is normalised: hours padded to 2 digits, minutes clamped
 *   to 59 and padded to 2 digits.
 * - Selecting all text on focus makes it easy to retype the value.
 *
 * Usage:
 *   <input type="text"
 *          data-controller="duration-input"
 *          data-duration-input-placeholder-value="00:00" />
 */

/* stimulusFetch: 'lazy' */
export default class DurationInputController extends Controller<HTMLInputElement> {
    static values = {
        placeholder: { type: String, default: '00:00' },
    };

    declare placeholderValue: string;

    private previousLength: number = 0;

    connect(): void {
        this.previousLength = this.element.value.length;
        this.element.setAttribute('autocomplete', 'off');
        this.element.setAttribute('inputmode', 'numeric');
        this.element.addEventListener('keydown', this.onKeydown);
        this.element.addEventListener('input', this.onInput);
        this.element.addEventListener('blur', this.onBlur);
        this.element.addEventListener('focus', this.onFocus);
    }

    disconnect(): void {
        this.element.removeEventListener('keydown', this.onKeydown);
        this.element.removeEventListener('input', this.onInput);
        this.element.removeEventListener('blur', this.onBlur);
        this.element.removeEventListener('focus', this.onFocus);
    }

    // -------------------------------------------------------------------------
    // Event handlers
    // -------------------------------------------------------------------------

    private onFocus = (): void => {
        // Select all so the user can immediately retype the value.
        requestAnimationFrame(() => this.element.select());
    };

    private onKeydown = (e: KeyboardEvent): void => {
        const navigationKeys = [
            'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
            'Home', 'End', 'Enter', 'Backspace', 'Delete',
        ];
        const isDigit = /^\d$/.test(e.key);
        const isColon = e.key === ':';
        const isModified = e.ctrlKey || e.metaKey; // allow copy/paste shortcuts

        if (!isDigit && !isColon && !navigationKeys.includes(e.key) && !isModified) {
            e.preventDefault();
        }
    };

    private onInput = (): void => {
        const raw = this.element.value;
        const isAdding = raw.length > this.previousLength;

        // Strip anything that isn't a digit or colon; allow at most one colon.
        let cleaned = raw.replace(/[^\d:]/g, '');
        const firstColon = cleaned.indexOf(':');
        if (firstColon !== -1) {
            cleaned = cleaned.slice(0, firstColon + 1) + cleaned.slice(firstColon + 1).replace(/:/g, '');
        }

        // Auto-insert colon: when the user adds a digit and there are now more
        // than 2 digits before any colon, insert it between hours and minutes.
        if (isAdding && !cleaned.includes(':') && cleaned.length > 2) {
            cleaned = cleaned.slice(0, -2) + ':' + cleaned.slice(-2);
        }

        // Clamp the first digit of minutes to 5 (minutes can't exceed 59).
        const colonIdx = cleaned.indexOf(':');
        if (colonIdx !== -1 && cleaned.length > colonIdx + 1) {
            const firstMinuteDigit = parseInt(cleaned[colonIdx + 1], 10);
            if (firstMinuteDigit > 5) {
                cleaned = cleaned.slice(0, colonIdx + 1) + '5' + cleaned.slice(colonIdx + 2);
            }
        }

        if (cleaned !== raw) {
            this.element.value = cleaned;
        }

        this.previousLength = this.element.value.length;
    };

    private onBlur = (): void => {
        this.normalize();
        this.previousLength = this.element.value.length;
    };

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private normalize(): void {
        const val = this.element.value.trim();

        if (val === '' || val === ':') {
            this.element.value = this.placeholderValue;
            return;
        }

        let hours: number;
        let minutes: number;

        if (val.includes(':')) {
            const [h, m] = val.split(':');
            hours = parseInt(h || '0', 10) || 0;
            minutes = parseInt(m || '0', 10) || 0;
        } else if (val.length <= 2) {
            // Treat a bare 1–2 digit entry as minutes (e.g. "30" → "00:30").
            hours = 0;
            minutes = parseInt(val, 10) || 0;
        } else {
            // Treat last 2 digits as minutes, the rest as hours (e.g. "130" → "01:30").
            hours = parseInt(val.slice(0, -2), 10) || 0;
            minutes = parseInt(val.slice(-2), 10) || 0;
        }

        minutes = Math.min(minutes, 59);

        this.element.value = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    }
}
