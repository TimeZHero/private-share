import { cn } from '@/lib/utils';
import { type TextareaHTMLAttributes, forwardRef } from 'react';

export const Textarea = forwardRef<
    HTMLTextAreaElement,
    TextareaHTMLAttributes<HTMLTextAreaElement>
>(({ className, ...props }, ref) => (
    <textarea
        ref={ref}
        className={cn(
            'min-h-[250px] w-full resize-none bg-transparent px-2 py-2 text-sm leading-relaxed text-[var(--color-text)] placeholder-[var(--color-text)]/30 focus:outline-none',
            className,
        )}
        {...props}
    />
));

Textarea.displayName = 'Textarea';
