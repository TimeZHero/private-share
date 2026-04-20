import { useCallback, type ReactElement } from 'react';
import { TOOLBAR_GROUPS, computeMarkdownInsertion, type MarkdownAction } from '@/services/markdown';

interface MarkdownToolbarProps {
    textareaRef: React.RefObject<HTMLTextAreaElement | null>;
    onContentChange: (newText: string) => void;
}

const iconMap: Record<string, ReactElement> = {
    heading: <><svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 8h10M7 12h10m-7 4h4" /></svg><span className="text-xs">H</span></>,
    bold: <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z" /></svg>,
    italic: <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}><line x1={14} y1={4} x2={10} y2={20} /><line x1={10} y1={4} x2={16} y2={4} /><line x1={8} y1={20} x2={14} y2={20} /></svg>,
    strikethrough: <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}><line x1={4} y1={12} x2={20} y2={12} /><path d="M16 4H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H7" /></svg>,
    link: <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>,
    code: <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>,
    codeblock: <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>,
    quote: <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>,
    bullet: <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}><line x1={9} y1={6} x2={20} y2={6} /><line x1={9} y1={12} x2={20} y2={12} /><line x1={9} y1={18} x2={20} y2={18} /><circle cx={5} cy={6} r={1.5} fill="currentColor" /><circle cx={5} cy={12} r={1.5} fill="currentColor" /><circle cx={5} cy={18} r={1.5} fill="currentColor" /></svg>,
    numbered: <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}><line x1={10} y1={6} x2={20} y2={6} /><line x1={10} y1={12} x2={20} y2={12} /><line x1={10} y1={18} x2={20} y2={18} /><text x={4} y={8} fontSize={7} fontWeight="bold" fill="currentColor" stroke="none">1</text><text x={4} y={14} fontSize={7} fontWeight="bold" fill="currentColor" stroke="none">2</text><text x={4} y={20} fontSize={7} fontWeight="bold" fill="currentColor" stroke="none">3</text></svg>,
    checkbox: <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>,
    table: <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>,
    hr: <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 12H4" /></svg>,
    image: <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>,
};

export function MarkdownToolbar({ textareaRef, onContentChange }: MarkdownToolbarProps) {
    const handleAction = useCallback(
        (type: MarkdownAction) => {
            const textarea = textareaRef.current;
            if (!textarea) return;

            const { newText, selectRange, cursorPosition } = computeMarkdownInsertion(
                textarea.value,
                textarea.selectionStart,
                textarea.selectionEnd,
                type,
            );

            onContentChange(newText);

            requestAnimationFrame(() => {
                textarea.focus();
                if (selectRange) {
                    textarea.setSelectionRange(selectRange[0], selectRange[1]);
                } else if (cursorPosition !== null) {
                    textarea.setSelectionRange(cursorPosition, cursorPosition);
                }
            });
        },
        [textareaRef, onContentChange],
    );

    return (
        <div className="flex flex-wrap items-center gap-1 mb-2 p-2 bg-[var(--color-surface-light)]/60 backdrop-blur-sm border border-white/10 rounded-xl animate-fadeIn">
            {TOOLBAR_GROUPS.map((group, groupIndex) => (
                <div key={groupIndex} className="contents">
                    {groupIndex > 0 && <div className="w-px h-5 bg-white/10 mx-1" />}
                    <div className="flex items-center gap-1">
                        {group.map((action) => (
                            <button
                                key={action.type}
                                type="button"
                                onClick={() => handleAction(action.type)}
                                className="toolbar-btn"
                                title={action.shortcut ? `${action.label} (${action.shortcut})` : action.label}
                            >
                                {iconMap[action.icon]}
                            </button>
                        ))}
                    </div>
                </div>
            ))}
            <div className="hidden sm:block w-px h-5 bg-white/10 mx-1" />
            <div className="hidden sm:flex items-center gap-1 ml-auto">
                <span className="text-xs text-[var(--color-text)]/40">Markdown enabled</span>
            </div>
        </div>
    );
}
