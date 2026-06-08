import { cn } from '@/lib/utils';
import { type ReactNode, useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';

interface ModalProps {
    open: boolean;
    onClose: () => void;
    title: string;
    children: ReactNode;
    maxWidth?: string;
}

export function Modal({
    open,
    onClose,
    title,
    children,
    maxWidth = 'max-w-lg',
}: ModalProps) {
    const panelRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        function handleKeydown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                onClose();
            }
        }

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', handleKeydown);
        panelRef.current?.focus();

        return () => {
            document.body.style.overflow = previousOverflow;
            document.removeEventListener('keydown', handleKeydown);
        };
    }, [open, onClose]);

    if (!open) {
        return null;
    }

    const titleId = 'modal-title';

    return createPortal(
        <div
            className="fixed inset-0 z-[100] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby={titleId}
        >
            <div
                className="animate-backdropIn absolute inset-0 bg-black/70 backdrop-blur-sm"
                onClick={onClose}
            />

            <div
                ref={panelRef}
                tabIndex={-1}
                className={cn(
                    'animate-modalIn relative flex max-h-[85vh] w-full flex-col overflow-hidden rounded-2xl border border-white/10 bg-[var(--color-surface)]/95 shadow-2xl shadow-black/50 backdrop-blur-md focus:outline-none',
                    maxWidth,
                )}
            >
                <div className="flex items-center justify-between gap-4 border-b border-white/10 px-6 py-4">
                    <h2
                        id={titleId}
                        className="text-lg font-semibold tracking-tight text-[var(--color-text)]"
                    >
                        {title}
                    </h2>
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Close"
                        className="flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg text-[var(--color-text)]/50 transition-colors hover:bg-white/5 hover:text-[var(--color-text)] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/30"
                    >
                        <svg
                            className="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <div className="themed-scroll overflow-y-auto px-6 py-5">
                    {children}
                </div>
            </div>
        </div>,
        document.body,
    );
}
