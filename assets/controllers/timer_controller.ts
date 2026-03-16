import { Controller } from '@hotwired/stimulus';
import { Dropdown } from 'bootstrap';

/* stimulusFetch: 'lazy' */
export default class TimerController extends Controller<HTMLDivElement> {
    static targets = ['display'];
    static values = {
        elapsed: Number,
        running: Boolean,
    };

    declare displayTarget: HTMLElement;
    declare hasDisplayTarget: boolean;
    declare elapsedValue: number;
    declare runningValue: boolean;

    private intervalId: number | null = null;
    private localElapsed: number = 0;

    connect(): void {
        this.localElapsed = this.elapsedValue;
        if (this.runningValue) {
            this.startTicking();
        }
        this.updateDisplay();
    }

    disconnect(): void {
        this.stopTicking();
    }

    elapsedValueChanged(): void {
        this.localElapsed = this.elapsedValue;
        this.updateDisplay();
    }

    runningValueChanged(): void {
        if (this.runningValue) {
            this.startTicking();
        } else {
            this.stopTicking();
        }
    }

    private startTicking(): void {
        if (this.intervalId !== null) {
            return;
        }
        this.intervalId = window.setInterval(() => {
            this.localElapsed++;
            this.updateDisplay();
        }, 1000);
    }

    private stopTicking(): void {
        if (this.intervalId !== null) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }

    closeDropdown(): void {
        const toggle = this.element.querySelector<HTMLElement>('[data-bs-toggle="dropdown"]');
        if (toggle) {
            Dropdown.getOrCreateInstance(toggle).hide();
        }
    }

    private updateDisplay(): void {
        if (!this.hasDisplayTarget) {
            return;
        }
        const hours = Math.floor(this.localElapsed / 3600);
        const minutes = Math.floor((this.localElapsed % 3600) / 60);
        const seconds = this.localElapsed % 60;
        this.displayTarget.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }
}
