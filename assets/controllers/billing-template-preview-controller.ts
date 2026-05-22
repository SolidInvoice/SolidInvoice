import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class BillingTemplatePreviewController extends Controller<HTMLFormElement> {
    static targets = ['content', 'iframe', 'preview', 'error'];

    static values = {
        type: String,
        variant: String,
        token: String,
    };

    declare readonly contentTarget: HTMLTextAreaElement;
    declare readonly iframeTarget: HTMLIFrameElement;
    declare readonly previewTarget: HTMLElement;
    declare readonly errorTarget: HTMLElement;

    declare readonly typeValue: string;
    declare readonly variantValue: string;
    declare readonly tokenValue: string;

    async preview(event: Event): Promise<void> {
        event.preventDefault();

        await this.run('/settings/billing-templates/preview', this.variantValue, async (response) => {
            const data = (await response.json()) as { html?: string; error?: string };

            if (response.ok && typeof data.html === 'string') {
                this.renderHtml(data.html);
            } else {
                this.renderError(data.error ?? 'Unknown error');
            }
        });
    }

    async previewPdf(event: Event): Promise<void> {
        event.preventDefault();

        await this.run('/settings/billing-templates/preview.pdf', 'pdf', async (response) => {
            if (!response.ok) {
                const data = (await response.json().catch(() => ({}))) as { error?: string };
                this.renderError(data.error ?? `HTTP ${response.status}`);
                return;
            }

            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            this.iframeTarget.src = url;
            this.errorTarget.hidden = true;
            this.previewTarget.hidden = false;
        });
    }

    private async run(
        path: string,
        variant: string,
        handler: (response: Response) => Promise<void>
    ): Promise<void> {
        const content = this.contentTarget.value;

        try {
            const response = await fetch(path, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    _token: this.tokenValue,
                    type: this.typeValue,
                    variant,
                    content,
                }),
            });

            await handler(response);
        } catch (error) {
            this.renderError(error instanceof Error ? error.message : String(error));
        }
    }

    private renderHtml(html: string): void {
        this.errorTarget.hidden = true;
        this.previewTarget.hidden = false;
        const doc = this.iframeTarget.contentDocument;
        if (doc !== null) {
            doc.open();
            doc.write(html);
            doc.close();
        }
    }

    private renderError(message: string): void {
        this.errorTarget.textContent = message;
        this.errorTarget.hidden = false;
        this.previewTarget.hidden = false;
        this.iframeTarget.src = 'about:blank';
    }
}
