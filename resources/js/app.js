import './searchable-select.js';
import './file-preview.js';

let letterTemplateEditorPromise;

window.loadSimopramLetterEditor = () => {
    letterTemplateEditorPromise ??= import('./letter-template-editor.js');

    return letterTemplateEditorPromise;
};

let letterTemplateMorphHookRegistered = false;

function registerLetterTemplateMorphHook() {
    if (!window.Livewire || letterTemplateMorphHookRegistered) {
        return;
    }

    letterTemplateMorphHookRegistered = true;
    Livewire.hook('morph.removing', ({ el }) => {
        const editorContainers = [];

        if (el.matches?.('[data-ckeditor-editor]')) {
            editorContainers.push(el);
        }

        el.querySelectorAll?.('[data-ckeditor-editor]').forEach((container) => editorContainers.push(container));

        editorContainers.forEach((container) => {
            window.loadSimopramLetterEditor().then(({ destroy }) => destroy(container));
        });
    });
}

if (window.Livewire) {
    registerLetterTemplateMorphHook();
} else {
    document.addEventListener('livewire:init', registerLetterTemplateMorphHook, { once: true });
}
