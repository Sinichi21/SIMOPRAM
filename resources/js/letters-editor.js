import {
    ClassicEditor, Essentials, Paragraph, Heading, Bold, Italic, Underline, Strikethrough,
    Link, List, Indent, IndentBlock, BlockQuote, Alignment, Table, TableToolbar,
    TableProperties, TableCellProperties, TableColumnResize, Undo, FindAndReplace,
    PasteFromOffice, RemoveFormat, HorizontalLine, SourceEditing, GeneralHtmlSupport,
} from 'ckeditor5';
import 'ckeditor5/ckeditor5.css';

const plugins = [
    Essentials, Paragraph, Heading, Bold, Italic, Underline, Strikethrough, Link,
    List, Indent, IndentBlock, BlockQuote, Alignment, Table, TableToolbar,
    TableProperties, TableCellProperties, TableColumnResize, Undo, FindAndReplace,
    PasteFromOffice, RemoveFormat, HorizontalLine, SourceEditing, GeneralHtmlSupport,
];

document.addEventListener('alpine:init', () => {
    Alpine.data('outgoingLetterEditor', (initialBody, property = 'body') => {
        let editor = null;
        let applyingContent = false;
        let disposed = false;
        return {
            async init() {
                await this.$nextTick();
                const element = this.$refs.editor;
                if (!element) return;
                try {
                    editor = await ClassicEditor.create(element, {
                        licenseKey: 'GPL',
                        plugins,
                        toolbar: {
                            items: ['undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'underline',
                                'strikethrough', 'removeFormat', '|', 'alignment', '|',
                                'bulletedList', 'numberedList', 'outdent', 'indent', '|',
                                'link', 'insertTable', 'blockQuote', 'horizontalLine', '|',
                                'findAndReplace', 'sourceEditing'],
                            shouldNotGroupWhenFull: true,
                        },
                        table: {
                            contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells',
                                'tableProperties', 'tableCellProperties'],
                        },
                        htmlSupport: {
                            allow: [{ name: /^(table|thead|tbody|tfoot|tr|td|th|p|div|span)$/,
                                styles: true, attributes: ['class', 'colspan', 'rowspan'] }],
                        },
                        initialData: initialBody || '',
                    });
                    if (disposed) {
                        await editor.destroy();
                        editor = null;
                        return;
                    }
                    this._onServerContent = (event) => {
                        if ((event.detail?.property || 'body') !== property) return;
                        if (!editor || typeof event.detail?.html !== 'string') return;
                        if (editor.getData() !== event.detail.html) {
                            applyingContent = true;
                            editor.setData(event.detail.html);
                            applyingContent = false;
                        }
                };
                window.addEventListener('letter-editor-content', this._onServerContent);
                editor.model.document.on('change:data', () => {
                    if (applyingContent) return;
                    this.$wire.$set(property, editor.getData(), false);
                    if (property === 'body') this.$wire.$set('useCustomBody', true, false);
                });
            } catch (error) {
                console.error('CKEditor 5 failed to initialize', error);
                element.hidden = true;
                this.$refs.fallback.hidden = false;
            }
        },
        insertPlaceholder(key) {
            if (!editor) return;
            editor.editing.view.focus();
            editor.model.change((writer) => {
                editor.model.insertContent(writer.createText('{{ ' + key + ' }}'));
            });
        },
        destroy() {
            disposed = true;
            if (this._onServerContent) window.removeEventListener('letter-editor-content', this._onServerContent);
            if (editor) editor.destroy();
        },
        };
    });
});
