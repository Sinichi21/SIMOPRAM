import {
    Alignment,
    BlockQuote,
    Bold,
    ClassicEditor,
    Essentials,
    Heading,
    Italic,
    List,
    Paragraph,
    Strikethrough,
    Table,
    TableCellProperties,
    TableColumnResize,
    TableProperties,
    TableToolbar,
    Underline,
} from 'ckeditor5';

import 'ckeditor5/ckeditor5.css';

const instances = new WeakMap();
const pending = new WeakMap();
const containers = new WeakMap();
const hostContainers = new WeakMap();
const cancelled = new WeakSet();

async function mount(element, initialData, onChange) {
    if (!element || instances.has(element) || pending.has(element)) {
        return;
    }

    const container = element.parentElement;
    containers.set(container, element);
    hostContainers.set(element, container);

    const creation = ClassicEditor.create(element, {
        licenseKey: import.meta.env.VITE_CKEDITOR_LICENSE_KEY || 'GPL',
        plugins: [
            Alignment,
            BlockQuote,
            Bold,
            Essentials,
            Heading,
            Italic,
            List,
            Paragraph,
            Strikethrough,
            Table,
            TableCellProperties,
            TableColumnResize,
            TableProperties,
            TableToolbar,
            Underline,
        ],
        toolbar: [
            'undo',
            'redo',
            '|',
            'heading',
            '|',
            'bold',
            'italic',
            'underline',
            'strikethrough',
            '|',
            'alignment',
            '|',
            'bulletedList',
            'numberedList',
            '|',
            'blockQuote',
            'insertTable',
        ],
        table: {
            contentToolbar: [
                'tableColumn',
                'tableRow',
                'mergeTableCells',
                'tableProperties',
                'tableCellProperties',
            ],
            showHiddenBorders: true,
        },
        initialData: initialData || '',
    });

    pending.set(element, creation);

    try {
        const editor = await creation;
        pending.delete(element);

        if (cancelled.has(element)) {
            cancelled.delete(element);
            await editor.destroy();
            containers.delete(container);
            hostContainers.delete(element);

            return;
        }

        editor.model.document.on('change:data', () => onChange(editor.getData()));
        instances.set(element, editor);
    } catch (error) {
        pending.delete(element);
        containers.delete(container);
        hostContainers.delete(element);
        console.error('CKEditor 5 gagal dimuat.', error);
    }
}

async function destroy(elementOrContainer) {
    const element = instances.has(elementOrContainer)
        ? elementOrContainer
        : containers.get(elementOrContainer);
    const editor = element ? instances.get(element) : null;

    if (! editor) {
        if (element && pending.has(element)) {
            cancelled.add(element);
        }

        return;
    }

    instances.delete(element);
    const container = hostContainers.get(element);
    containers.delete(container);
    hostContainers.delete(element);

    try {
        await editor.destroy();
    } catch (error) {
        console.error('CKEditor 5 gagal ditutup.', error);
    }
}

function insertPlaceholder(element, placeholder) {
    const editor = instances.get(element);

    if (! editor) {
        return;
    }

    editor.model.change((writer) => {
        writer.insertText(`{{ ${placeholder} }}`, editor.model.document.selection);
    });
    editor.editing.view.focus();
}

export { mount, destroy, insertPlaceholder };
