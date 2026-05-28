import { Controller } from '@hotwired/stimulus';
import { basicSetup } from 'codemirror';
import { EditorState, RangeSetBuilder } from '@codemirror/state';
import { EditorView, Decoration, DecorationSet, ViewPlugin, ViewUpdate } from '@codemirror/view';
import { html } from '@codemirror/lang-html';

const twigHighlight = ViewPlugin.fromClass(
    class {
        decorations: DecorationSet;

        constructor(view: EditorView) {
            this.decorations = buildTwigDecorations(view.state);
        }

        update(update: ViewUpdate): void {
            if (update.docChanged) {
                this.decorations = buildTwigDecorations(update.state);
            }
        }
    },
    {
        decorations: (value): DecorationSet => value.decorations,
    }
);

function buildTwigDecorations(state: EditorState): DecorationSet {
    const builder: RangeSetBuilder<Decoration> = new RangeSetBuilder();
    const text: string = state.doc.toString();
    const regex: RegExp = /(\{\{[\s\S]*?\}\}|\{%[\s\S]*?%\}|\{#[\s\S]*?#\})/g;
    let match: RegExpExecArray | null;

    while ((match = regex.exec(text)) !== null) {
        const from = match.index;
        const to = match.index + match[0].length;
        builder.add(from, to, Decoration.mark({ class: 'cm-si-twig' }));
    }

    return builder.finish();
}

/* stimulusFetch: 'lazy' */
export default class CodeEditorController extends Controller<HTMLTextAreaElement> {
    static values = { language: String };

    declare readonly languageValue: string;
    declare readonly hasLanguageValue: boolean;
    private view?: EditorView;
    private submitHandler?: () => void;
    private wrapper?: HTMLDivElement;

    connect(): void {
        const textarea = this.element;
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('code-editor');

        if (!textarea.dataset.model && textarea.name) {
            const modelName = textarea.name.replace(/\[]$/, '').split('[').map((part) => part.replace(']', '')).join('.');
            textarea.dataset.model = `norender|${modelName}`;
        }

        textarea.insertAdjacentElement('afterend', this.wrapper);
        textarea.style.display = 'none';

        const updateListener = EditorView.updateListener.of((update: ViewUpdate): void => {
            if (update.docChanged) {
                textarea.value = update.state.doc.toString();
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
                textarea.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        const extensions = [
            basicSetup,
            twigHighlight,
            EditorView.lineWrapping,
            updateListener,
        ];

        if (!this.hasLanguageValue || this.languageValue === 'html') {
            extensions.splice(1, 0, html());
        }

        this.view = new EditorView({
            state: EditorState.create({
                doc: textarea.value,
                extensions,
            }),
            parent: this.wrapper,
        });

        if (textarea.form) {
            this.submitHandler = (): void => {
                if (this.view) {
                    textarea.value = this.view.state.doc.toString();
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                    textarea.dispatchEvent(new Event('change', { bubbles: true }));
                }
            };

            textarea.form.addEventListener('submit', this.submitHandler);
        }
    }

    disconnect(): void {
        if (this.submitHandler && this.element.form) {
            this.element.form.removeEventListener('submit', this.submitHandler);
        }

        this.view?.destroy();
        this.view = undefined;
        this.element.style.display = '';

        if (this.wrapper) {
            this.wrapper.remove();
            this.wrapper = undefined;
        }
    }
}
