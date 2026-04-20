import { type InputHTMLAttributes, forwardRef } from 'react';
import { cn } from '@/lib/utils';

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
    fullWidth?: boolean;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(
    ({ className, fullWidth = true, ...props }, ref) => (
        <input
            ref={ref}
            className={cn(
                'px-4 py-3 bg-[var(--color-surface-light)]/80 border border-white/10 rounded-xl text-[var(--color-text)] text-sm placeholder-[var(--color-text)]/40 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-500)]/50 focus:border-[var(--color-primary-500)]',
                fullWidth && 'w-full',
                className,
            )}
            {...props}
        />
    ),
);

Input.displayName = 'Input';
