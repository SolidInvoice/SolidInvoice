/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import { Context, Controller } from '@hotwired/stimulus';

interface SearchResult {
    type: string;
    id: string;
    title: string;
    subtitle: string;
    url: string;
    status?: string;
    meta?: string;
}

interface Qualifier {
    label: string;
    values?: string[];
    hint: string;
}

const QUALIFIERS: Record<string, Qualifier> = {
    'in': {
        label: 'Scope',
        values: ['invoices', 'recurring_invoices', 'quotes', 'payments', 'clients', 'contacts'],
        hint: 'in:invoices',
    },
    'status': {
        label: 'Status',
        values: ['draft', 'pending', 'paid', 'overdue', 'cancelled', 'active', 'paused', 'captured', 'authorized', 'refunded', 'failed'],
        hint: 'status:paid',
    },
    'amount': {
        label: 'Amount',
        hint: 'amount:>100 or amount:100..500',
    },
    'client': {
        label: 'Client',
        hint: 'client:"Acme Corp"',
    },
    'created': {
        label: 'Created',
        hint: 'created:>2024-01-01',
    },
    'sort': {
        label: 'Sort',
        values: ['amount', 'amount_desc', 'date', 'date_desc'],
        hint: 'sort:amount_desc',
    },
};

const LABELS: Record<string, string> = {
    clients: 'Clients',
    contacts: 'Contacts',
    invoices: 'Invoices',
    recurring_invoices: 'Recurring Invoices',
    quotes: 'Quotes',
    payments: 'Payments',
};

const STATUS_COLORS: Record<string, string> = {
    draft: 'secondary',
    pending: 'warning',
    sent: 'info',
    viewed: 'info',
    paid: 'success',
    overdue: 'danger',
    cancelled: 'danger',
    archived: 'secondary',
    captured: 'success',
    authorized: 'info',
    refunded: 'primary',
    failed: 'danger',
    unknown: 'secondary',
    active: 'success',
    paused: 'warning',
};

// Hardcoded trusted SVG icon strings — not user content
const ICONS: Record<string, string> = {
    client: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
    contact: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="18" rx="2"/><path d="M9 12a2 2 0 1 0 4 0 2 2 0 0 0-4 0"/><path d="M7 20v-1a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1"/></svg>`,
    invoice: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>`,
    recurring_invoice: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>`,
    quote: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="12" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>`,
    payment: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>`,
};

const ICON_COLORS: Record<string, string> = {
    clients: '#2e963a',
    contacts: '#3b82f6',
    invoices: '#f0a015',
    recurring_invoices: '#8b5cf6',
    quotes: '#06b6d4',
    payments: '#ef4444',
};

/* stimulusFetch: 'lazy' */
export default class GlobalSearchController extends Controller<HTMLElement> {
    static values = { url: String, suggestionsUrl: String };
    static targets = ['input', 'dropdown', 'results', 'spinner', 'chips', 'suggestions'];

    declare urlValue: string;
    declare suggestionsUrlValue: string;
    declare inputTarget: HTMLInputElement;
    declare dropdownTarget: HTMLElement;
    declare resultsTarget: HTMLElement;
    declare spinnerTarget: HTMLElement;
    declare chipsTarget: HTMLElement;
    declare suggestionsTarget: HTMLElement;
    declare hasChipsTarget: boolean;
    declare hasSuggestionsTarget: boolean;

    private debounceTimer: ReturnType<typeof setTimeout> | null = null;
    private suggestionsDebounceTimer: ReturnType<typeof setTimeout> | null = null;
    private abortController: AbortController | null = null;
    private suggestionsAbortController: AbortController | null = null;
    private selectedIndex = -1;
    private resultItems: HTMLElement[] = [];
    private chips: string[] = [];
    private suggestionsVisible = false;
    private readonly handleOutsideClick: (event: MouseEvent) => void;
    private readonly handleGlobalKeydown: (event: KeyboardEvent) => void;

    constructor(context: Context) {
        super(context);
        this.handleOutsideClick = this._handleOutsideClick.bind(this);
        this.handleGlobalKeydown = this._handleGlobalKeydown.bind(this);
    }

    connect() {
        document.addEventListener('click', this.handleOutsideClick);
        document.addEventListener('keydown', this.handleGlobalKeydown);
    }

    disconnect() {
        document.removeEventListener('click', this.handleOutsideClick);
        document.removeEventListener('keydown', this.handleGlobalKeydown);
        if (this.debounceTimer) clearTimeout(this.debounceTimer);
        if (this.suggestionsDebounceTimer) clearTimeout(this.suggestionsDebounceTimer);
        if (this.abortController) this.abortController.abort();
        if (this.suggestionsAbortController) this.suggestionsAbortController.abort();
    }

    onInput(): void {
        this.checkForSuggestions();
        this.checkForChipCreation();

        const query = this.buildQuery();

        if (this.debounceTimer) clearTimeout(this.debounceTimer);

        if (query.length < 2) {
            if (this.abortController) this.abortController.abort();
            this.hideDropdown();
            return;
        }

        this.debounceTimer = setTimeout(() => void this.search(query), 300);
    }

    onKeydown(event: KeyboardEvent): void {
        switch (event.key) {
            case 'Escape':
                if (this.suggestionsVisible) {
                    this.hideSuggestions();
                } else {
                    this.hideDropdown();
                    this.inputTarget.blur();
                }
                break;
            case 'ArrowDown':
                event.preventDefault();
                if (this.suggestionsVisible) {
                    this.moveSuggestionSelection(1);
                } else {
                    this.moveSelection(1);
                }
                break;
            case 'ArrowUp':
                event.preventDefault();
                if (this.suggestionsVisible) {
                    this.moveSuggestionSelection(-1);
                } else {
                    this.moveSelection(-1);
                }
                break;
            case 'Enter':
                event.preventDefault();
                if (this.suggestionsVisible) {
                    const activeItem = this.suggestionsTarget.querySelector<HTMLElement>('.search-suggestions-item.is-active');
                    if (activeItem) {
                        const value = activeItem.dataset['value'] ?? '';
                        const qualifier = activeItem.dataset['qualifier'];
                        if (qualifier !== undefined) {
                            this.selectDynamicSuggestion(qualifier, value);
                        } else {
                            this.selectSuggestion(value);
                        }
                    }
                } else if (this.selectedIndex >= 0 && this.resultItems[this.selectedIndex]) {
                    const link = this.resultItems[this.selectedIndex].querySelector('a') as HTMLAnchorElement | null;
                    if (link) window.location.href = link.href;
                }
                break;
            case 'Backspace':
                if (this.inputTarget.value === '' && this.chips.length > 0) {
                    this.removeChip(this.chips.length - 1);
                }
                break;
        }
    }

    private buildQuery(): string {
        const chipPart = this.chips.join(' ');
        const inputPart = this.inputTarget.value.trim();
        return chipPart ? `${chipPart} ${inputPart}`.trim() : inputPart;
    }

    private checkForSuggestions(): void {
        const value = this.inputTarget.value;
        const lastWord = value.split(/\s+/).pop() ?? '';

        // Match qualifier: with optional partial value (e.g. "status:", "status:p", "status:pa")
        const match = /^(\w+):(.*)$/.exec(lastWord);
        if (match) {
            const qualifierName = match[1] ?? '';
            const partial = match[2] ?? '';
            const qualifier = QUALIFIERS[qualifierName];

            if (qualifier && qualifier.values && qualifier.values.length > 0) {
                this.showSuggestions(qualifierName, partial);
                return;
            }

            // Handle dynamic suggestions (e.g. client:partial)
            if (qualifierName === 'client' && this.suggestionsUrlValue) {
                if (this.suggestionsDebounceTimer) clearTimeout(this.suggestionsDebounceTimer);
                this.suggestionsDebounceTimer = setTimeout(
                    () => void this.fetchDynamicSuggestions(qualifierName, partial),
                    200,
                );
                return;
            }
        }

        this.hideSuggestions();
    }

    private showSuggestions(qualifierName: string, partial: string = ''): void {
        if (!this.hasSuggestionsTarget) return;

        const qualifier = QUALIFIERS[qualifierName];
        if (!qualifier || !qualifier.values) return;

        const filtered = partial === ''
            ? qualifier.values
            : qualifier.values.filter(v => v.toLowerCase().startsWith(partial.toLowerCase()));

        if (filtered.length === 0) {
            this.hideSuggestions();
            return;
        }

        const container = this.suggestionsTarget;
        container.textContent = '';
        container.classList.add('is-open');

        const header = document.createElement('div');
        header.className = 'search-suggestions-header';
        header.textContent = qualifier.label;
        container.appendChild(header);

        for (const value of filtered) {
            const item = document.createElement('div');
            item.className = 'search-suggestions-item';
            item.dataset['value'] = value;

            const valueText = document.createTextNode(value + ' ');
            item.appendChild(valueText);

            const code = document.createElement('code');
            code.textContent = `${qualifierName}:${value}`;
            item.appendChild(code);

            item.addEventListener('click', () => {
                this.selectSuggestion(value);
            });

            container.appendChild(item);
        }

        this.suggestionsVisible = true;
    }

    private hideSuggestions(): void {
        if (!this.hasSuggestionsTarget) return;
        this.suggestionsTarget.textContent = '';
        this.suggestionsTarget.classList.remove('is-open');
        this.suggestionsVisible = false;
    }

    private async fetchDynamicSuggestions(qualifierName: string, partial: string): Promise<void> {
        if (!this.hasSuggestionsTarget) return;

        if (this.suggestionsAbortController) this.suggestionsAbortController.abort();
        this.suggestionsAbortController = new AbortController();

        try {
            const url = new URL(this.suggestionsUrlValue, window.location.origin);
            url.searchParams.set('qualifier', qualifierName);
            url.searchParams.set('q', partial);

            const response = await fetch(url.toString(), {
                signal: this.suggestionsAbortController.signal,
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) return;

            const names = await response.json() as string[];
            if (names.length > 0) {
                this.showDynamicSuggestions(qualifierName, names);
            } else {
                this.hideSuggestions();
            }
        } catch (err) {
            if ((err as Error).name !== 'AbortError') {
                this.hideSuggestions();
            }
        }
    }

    private showDynamicSuggestions(qualifierName: string, names: string[]): void {
        if (!this.hasSuggestionsTarget) return;

        const qualifier = QUALIFIERS[qualifierName];
        const label = qualifier?.label ?? qualifierName;

        const container = this.suggestionsTarget;
        container.textContent = '';
        container.classList.add('is-open');

        const header = document.createElement('div');
        header.className = 'search-suggestions-header';
        header.textContent = label;
        container.appendChild(header);

        for (const name of names) {
            const item = document.createElement('div');
            item.className = 'search-suggestions-item';
            item.dataset['value'] = name;
            item.dataset['qualifier'] = qualifierName;

            const valueText = document.createTextNode(name + ' ');
            item.appendChild(valueText);

            const code = document.createElement('code');
            code.textContent = `${qualifierName}:"${name}"`;
            item.appendChild(code);

            item.addEventListener('click', () => {
                this.selectDynamicSuggestion(qualifierName, name);
            });

            container.appendChild(item);
        }

        this.suggestionsVisible = true;
    }

    private selectDynamicSuggestion(qualifierName: string, value: string): void {
        const inputValue = this.inputTarget.value;
        const words = inputValue.split(/\s+/);
        words.pop(); // remove the partial "client:xxx" word

        const escapedValue = value.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
        const token = `${qualifierName}:"${escapedValue}"`;
        words.push('');
        this.inputTarget.value = words.join(' ').trimStart();
        this.addChip(token);
        this.hideSuggestions();
    }

    private moveSuggestionSelection(direction: number): void {
        if (!this.hasSuggestionsTarget) return;
        const items = Array.from(this.suggestionsTarget.querySelectorAll<HTMLElement>('.search-suggestions-item'));
        if (items.length === 0) return;

        const currentIndex = items.findIndex(item => item.classList.contains('is-active'));
        items[currentIndex]?.classList.remove('is-active');

        const nextIndex = currentIndex < 0
            ? (direction > 0 ? 0 : items.length - 1)
            : Math.max(0, Math.min(items.length - 1, currentIndex + direction));

        items[nextIndex]?.classList.add('is-active');
        items[nextIndex]?.scrollIntoView({ block: 'nearest' });
    }

    private selectSuggestion(value: string): void {
        const inputValue = this.inputTarget.value;
        const words = inputValue.split(/\s+/);
        const lastWord = words.pop() ?? '';

        // Match either "qualifier:" or "qualifier:partial"
        const match = /^(\w+):/.exec(lastWord);
        if (match) {
            const qualifierName = match[1];
            const token = `${qualifierName}:${value}`;
            words.push('');
            this.inputTarget.value = words.join(' ').trimStart();
            this.addChip(token);
        }

        this.hideSuggestions();
    }

    private checkForChipCreation(): void {
        const value = this.inputTarget.value;

        // Only trigger on space as the last character
        if (!value.endsWith(' ')) return;

        const tokenRegex = /^(\w+):("[^"]*"|\S+)$/;
        const words = value.trimEnd().split(/\s+/);
        const lastToken = words[words.length - 1] ?? '';

        const match = tokenRegex.exec(lastToken);
        if (match) {
            const qualifierName = match[1];
            if (qualifierName && QUALIFIERS[qualifierName] !== undefined) {
                // Remove the token from the input
                words.pop();
                this.inputTarget.value = words.length > 0 ? words.join(' ') + ' ' : '';
                this.addChip(lastToken);
            }
        }
    }

    private addChip(token: string): void {
        this.chips.push(token);
        this.renderChips();

        const query = this.buildQuery();
        if (query.length >= 2) {
            if (this.debounceTimer) clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => void this.search(query), 300);
        }
    }

    private removeChip(index: number): void {
        this.chips.splice(index, 1);
        this.renderChips();

        const query = this.buildQuery();
        if (query.length >= 2) {
            if (this.debounceTimer) clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => void this.search(query), 300);
        } else {
            this.hideDropdown();
        }
    }

    private renderChips(): void {
        if (!this.hasChipsTarget) return;

        const container = this.chipsTarget;
        container.textContent = '';

        for (let i = 0; i < this.chips.length; i++) {
            const chip = this.chips[i];
            if (chip === undefined) continue;

            const chipEl = document.createElement('span');
            chipEl.className = 'search-chip';

            const label = document.createElement('span');
            label.className = 'search-chip-label';
            label.textContent = chip.replace(':', ': ');
            chipEl.appendChild(label);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'search-chip-remove';
            removeBtn.setAttribute('aria-label', 'Remove');
            removeBtn.dataset['qualifier'] = chip;
            removeBtn.textContent = '×';

            const chipIndex = i;
            removeBtn.addEventListener('click', () => {
                this.removeChip(chipIndex);
            });

            chipEl.appendChild(removeBtn);
            container.appendChild(chipEl);
        }
    }

    private async search(query: string): Promise<void> {
        this.showSpinner();

        if (this.abortController) this.abortController.abort();
        this.abortController = new AbortController();

        try {
            const url = new URL(this.urlValue, window.location.origin);
            url.searchParams.set('q', query);

            const response = await fetch(url.toString(), {
                signal: this.abortController.signal,
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) throw new Error('Search request failed');

            const data = await response.json() as Record<string, SearchResult[]>;
            this.renderResults(data);
        } catch (err) {
            if ((err as Error).name !== 'AbortError') {
                this.renderError();
            }
        }
    }

    private renderResults(data: Record<string, SearchResult[]>): void {
        const total = Object.values(data).reduce((sum, arr) => sum + arr.length, 0);
        const container = this.resultsTarget;
        container.textContent = '';

        if (total === 0) {
            container.appendChild(this.buildEmptyState());
        } else {
            for (const [group, results] of Object.entries(data)) {
                container.appendChild(this.buildGroup(group, results));
            }
        }

        this.hideSpinner();
        this.showDropdown();
        this.selectedIndex = -1;
        this.resultItems = Array.from(container.querySelectorAll<HTMLElement>('.search-result-item'));
    }

    private buildEmptyState(): HTMLElement {
        const wrapper = document.createElement('div');
        wrapper.className = 'search-empty';

        // Trusted hardcoded SVG for empty state icon
        const iconWrapper = document.createElement('span');
        iconWrapper.className = 'search-empty-icon';
        iconWrapper.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>`;

        const text = document.createElement('p');
        text.className = 'search-empty-text';
        text.textContent = 'No results found';

        wrapper.appendChild(iconWrapper);
        wrapper.appendChild(text);
        return wrapper;
    }

    private buildGroup(group: string, results: SearchResult[]): HTMLElement {
        const wrapper = document.createElement('div');
        wrapper.className = 'search-group';

        const label = document.createElement('div');
        label.className = 'search-group-label';
        label.textContent = LABELS[group] ?? group;

        wrapper.appendChild(label);

        const color = ICON_COLORS[group] ?? '#6c757d';
        for (const result of results) {
            wrapper.appendChild(this.buildResultItem(result, color));
        }

        return wrapper;
    }

    private buildResultItem(result: SearchResult, iconColor: string): HTMLElement {
        const item = document.createElement('div');
        item.className = 'search-result-item';

        const link = document.createElement('a');
        link.href = result.url;
        link.className = 'search-result-link';

        // Icon — trusted hardcoded SVG string
        const iconSpan = document.createElement('span');
        iconSpan.className = 'search-result-icon';
        iconSpan.style.color = iconColor;
        iconSpan.style.backgroundColor = iconColor + '18'; // ~10% opacity tint
        iconSpan.innerHTML = ICONS[result.type] ?? ICONS['invoice'];

        // Body
        const body = document.createElement('span');
        body.className = 'search-result-body';

        const title = document.createElement('span');
        title.className = 'search-result-title';
        title.textContent = result.title;

        body.appendChild(title);

        if (result.subtitle) {
            const subtitle = document.createElement('span');
            subtitle.className = 'search-result-subtitle';
            subtitle.textContent = result.subtitle;
            body.appendChild(subtitle);
        }

        link.appendChild(iconSpan);
        link.appendChild(body);

        // Aside: status badge + meta
        if (result.status || result.meta) {
            const aside = document.createElement('span');
            aside.className = 'search-result-aside';

            if (result.status) {
                const badge = document.createElement('span');
                const colorClass = STATUS_COLORS[result.status] ?? 'secondary';
                badge.className = `badge bg-${colorClass}-lt search-result-badge`;
                badge.textContent = result.status;
                aside.appendChild(badge);
            }

            if (result.meta) {
                const meta = document.createElement('span');
                meta.className = 'search-result-meta';
                meta.textContent = result.meta;
                aside.appendChild(meta);
            }

            link.appendChild(aside);
        }

        item.appendChild(link);
        return item;
    }

    private moveSelection(direction: number): void {
        if (this.resultItems.length === 0) return;

        if (this.selectedIndex >= 0) {
            this.resultItems[this.selectedIndex]?.classList.remove('is-active');
        }

        this.selectedIndex = Math.max(
            0,
            Math.min(this.resultItems.length - 1, this.selectedIndex + direction),
        );

        const item = this.resultItems[this.selectedIndex];
        item?.classList.add('is-active');
        item?.scrollIntoView({ block: 'nearest' });
    }

    private showDropdown(): void {
        this.dropdownTarget.classList.add('is-open');
    }

    private hideDropdown(): void {
        this.dropdownTarget.classList.remove('is-open');
        this.selectedIndex = -1;
    }

    private showSpinner(): void {
        this.spinnerTarget.classList.remove('d-none');
        this.resultsTarget.textContent = '';
        this.showDropdown();
    }

    private hideSpinner(): void {
        this.spinnerTarget.classList.add('d-none');
    }

    private renderError(): void {
        const err = document.createElement('div');
        err.className = 'search-error';
        err.textContent = 'Search failed. Please try again.';
        this.resultsTarget.textContent = '';
        this.resultsTarget.appendChild(err);
        this.hideSpinner();
        this.showDropdown();
    }

    private _handleOutsideClick(event: MouseEvent): void {
        if (!this.element.contains(event.target as Node)) {
            this.hideDropdown();
            this.hideSuggestions();
        }
    }

    private _handleGlobalKeydown(event: KeyboardEvent): void {
        if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
            const target = event.target as HTMLElement;
            if (
                target instanceof HTMLInputElement ||
                target instanceof HTMLTextAreaElement ||
                target.isContentEditable
            ) {
                return;
            }
            event.preventDefault();
            this.inputTarget.focus();
            this.inputTarget.select();
        }
    }
}
