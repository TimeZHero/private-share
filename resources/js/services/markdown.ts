import DOMPurify from 'dompurify';
import { marked } from 'marked';

export function renderMarkdown(source: string): string {
    return DOMPurify.sanitize(marked.parse(source) as string);
}

export type MarkdownAction =
    | 'heading'
    | 'bold'
    | 'italic'
    | 'strikethrough'
    | 'link'
    | 'image'
    | 'code'
    | 'codeblock'
    | 'quote'
    | 'bullet'
    | 'numbered'
    | 'checkbox'
    | 'table'
    | 'hr';

interface InsertionResult {
    newText: string;
    selectRange: [number, number] | null;
    cursorPosition: number | null;
}

export function computeMarkdownInsertion(
    text: string,
    selectionStart: number,
    selectionEnd: number,
    type: MarkdownAction,
): InsertionResult {
    const selectedText = text.substring(selectionStart, selectionEnd);
    let insertion: string;
    let selectRange: [number, number] | null = null;
    let cursorOffset = 0;

    switch (type) {
        case 'bold':
            insertion = `**${selectedText || 'bold text'}**`;
            if (!selectedText)
                selectRange = [selectionStart + 2, selectionStart + 11];
            break;
        case 'italic':
            insertion = `*${selectedText || 'italic text'}*`;
            if (!selectedText)
                selectRange = [selectionStart + 1, selectionStart + 12];
            break;
        case 'strikethrough':
            insertion = `~~${selectedText || 'strikethrough'}~~`;
            if (!selectedText)
                selectRange = [selectionStart + 2, selectionStart + 15];
            break;
        case 'heading': {
            const lineStart = text.lastIndexOf('\n', selectionStart - 1) + 1;
            const prefix = selectionStart === lineStart ? '' : '\n';
            insertion = `${prefix}## ${selectedText || 'Heading'}`;
            if (!selectedText)
                selectRange = [
                    selectionStart + prefix.length + 3,
                    selectionStart + prefix.length + 10,
                ];
            break;
        }
        case 'link':
            if (selectedText) {
                insertion = `[${selectedText}](url)`;
                selectRange = [
                    selectionStart + selectedText.length + 3,
                    selectionStart + selectedText.length + 6,
                ];
            } else {
                insertion = '[link text](url)';
                selectRange = [selectionStart + 1, selectionStart + 10];
            }
            break;
        case 'image':
            insertion = `![${selectedText || 'alt text'}](image-url)`;
            if (!selectedText)
                selectRange = [selectionStart + 2, selectionStart + 10];
            break;
        case 'code':
            insertion = `\`${selectedText || 'code'}\``;
            if (!selectedText)
                selectRange = [selectionStart + 1, selectionStart + 5];
            break;
        case 'codeblock': {
            const lang = 'language';
            insertion = `\n\`\`\`${lang}\n${selectedText || 'code here'}\n\`\`\`\n`;
            if (!selectedText)
                selectRange = [
                    selectionStart + 5 + lang.length,
                    selectionStart + 5 + lang.length + 9,
                ];
            break;
        }
        case 'quote':
            insertion = `\n> ${selectedText || 'Quote text here'}\n`;
            if (!selectedText)
                selectRange = [selectionStart + 3, selectionStart + 18];
            break;
        case 'bullet':
            insertion = `\n- ${selectedText || 'List item'}\n- \n- \n`;
            if (!selectedText)
                selectRange = [selectionStart + 3, selectionStart + 12];
            break;
        case 'numbered':
            insertion = `\n1. ${selectedText || 'First item'}\n2. \n3. \n`;
            if (!selectedText)
                selectRange = [selectionStart + 4, selectionStart + 14];
            break;
        case 'checkbox':
            insertion = `\n- [ ] ${selectedText || 'Task item'}\n- [ ] \n- [ ] \n`;
            if (!selectedText)
                selectRange = [selectionStart + 7, selectionStart + 16];
            break;
        case 'table':
            insertion = `\n| Header 1 | Header 2 | Header 3 |\n|----------|----------|----------|\n| Cell 1   | Cell 2   | Cell 3   |\n| Cell 4   | Cell 5   | Cell 6   |\n`;
            cursorOffset = 3;
            break;
        case 'hr':
            insertion = '\n\n---\n\n';
            cursorOffset = 0;
            break;
        default:
            return { newText: text, selectRange: null, cursorPosition: null };
    }

    const newText =
        text.substring(0, selectionStart) +
        insertion +
        text.substring(selectionEnd);
    const cursorPosition = selectRange
        ? null
        : selectionStart + insertion.length - cursorOffset;

    return { newText, selectRange, cursorPosition };
}

interface ToolbarAction {
    type: MarkdownAction;
    label: string;
    shortcut?: string;
    icon: string;
}

export const TOOLBAR_GROUPS: ToolbarAction[][] = [
    [
        {
            type: 'heading',
            label: 'Heading',
            shortcut: 'Ctrl+H',
            icon: 'heading',
        },
        { type: 'bold', label: 'Bold', shortcut: 'Ctrl+B', icon: 'bold' },
        { type: 'italic', label: 'Italic', shortcut: 'Ctrl+I', icon: 'italic' },
        {
            type: 'strikethrough',
            label: 'Strikethrough',
            icon: 'strikethrough',
        },
    ],
    [
        { type: 'link', label: 'Link', shortcut: 'Ctrl+K', icon: 'link' },
        { type: 'code', label: 'Inline Code', icon: 'code' },
        { type: 'codeblock', label: 'Code Block', icon: 'codeblock' },
    ],
    [
        { type: 'quote', label: 'Quote', icon: 'quote' },
        { type: 'bullet', label: 'Bullet List', icon: 'bullet' },
        { type: 'numbered', label: 'Numbered List', icon: 'numbered' },
        { type: 'checkbox', label: 'Checkbox List', icon: 'checkbox' },
    ],
    [
        { type: 'table', label: 'Insert Table', icon: 'table' },
        { type: 'hr', label: 'Horizontal Rule', icon: 'hr' },
        { type: 'image', label: 'Image', icon: 'image' },
    ],
];
