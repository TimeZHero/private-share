import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

interface ErrorAlertProps {
    message: ReactNode;
    visible?: boolean;
    dismissible?: boolean;
    onDismiss?: () => void;
    className?: string;
}

export function ErrorAlert({
    message,
    visible = true,
    dismissible = false,
    onDismiss,
    className,
}: ErrorAlertProps) {
    if (!visible) return null;

    return (
        <div
            className={cn(
                'flex items-center justify-center gap-3 rounded-xl border border-red-500/80 bg-red-950/50 px-4 py-3 text-sm text-red-200',
                className,
            )}
            role="alert"
        >
            <svg
                className="h-5 w-5 shrink-0 text-red-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
            </svg>
            <span className="leading-relaxed font-medium">{message}</span>
            {dismissible && onDismiss && (
                <button
                    type="button"
                    onClick={onDismiss}
                    className="ml-auto shrink-0 cursor-pointer text-red-400/70 transition-colors hover:text-red-300"
                >
                    <svg
                        className="h-4 w-4"
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
            )}
        </div>
    );
}
