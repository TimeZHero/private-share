import { type TextareaHTMLAttributes, forwardRef } from 'react';
import { cn } from '@/lib/utils';

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaHTMLAttributes<HTMLTextAreaElement>>(
    ({ className, ...props }, ref) => (
        <textarea
            ref={ref}
            className={cn(
                'w-full min-h-[250px] px-2 py-2 bg-transparent text-[var(--color-text)] placeholder-[var(--color-text)]/30 text-sm leading-relaxed resize-none focus:outline-none',
                className,
            )}
            {...props}
        />
    ),
);

Textarea.displayName = 'Textarea';
