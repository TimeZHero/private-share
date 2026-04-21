import { cn } from '@/lib/utils';
import { type InputHTMLAttributes, forwardRef } from 'react';

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
    fullWidth?: boolean;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(
    ({ className, fullWidth = true, ...props }, ref) => (
        <input
            ref={ref}
            className={cn(
                'rounded-xl border border-white/10 bg-[var(--color-surface-light)]/80 px-4 py-3 text-sm text-[var(--color-text)] placeholder-[var(--color-text)]/40 focus:border-[var(--color-primary-500)] focus:ring-2 focus:ring-[var(--color-primary-500)]/50 focus:outline-none',
                fullWidth && 'w-full',
                className,
            )}
            {...props}
        />
    ),
);

Input.displayName = 'Input';
