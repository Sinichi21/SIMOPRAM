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
    Alpine.data('outgoingLetterEditor', (initialBody) => ({
        editor: null,
        async init() {
            await this.$nextTick();
            const element = this.$refs.editor;
            if (!element) return;
            try {
                this.editor = await ClassicEditor.create(element, {
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
                this.editor.model.document.on('change:data', () => {
                    this.$wire.set('body', this.editor.getData(), false);
                });
            } catch (error) {
                console.error('CKEditor 5 failed to initialize', error);
                element.hidden = true;
                this.$refs.fallback.hidden = false;
            }
        },
        destroy() {
            if (this.editor) this.editor.destroy();
        },
    }));
});
