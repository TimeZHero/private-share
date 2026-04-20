import { type InputHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

interface ToggleProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
    label: string;
    labelId?: string;
}

export function Toggle({ label, labelId, id, className, ...props }: ToggleProps) {
    return (
        <label className={cn('flex items-center gap-2 cursor-pointer group', className)} htmlFor={id}>
            <input type="checkbox" id={id} className="toggle-input sr-only" {...props} />
            <div className="toggle-switch-sm" />
            <span
                id={labelId ?? (id ? `${id}-label` : undefined)}
                className="text-sm text-[var(--color-text)]/60 group-hover:text-[var(--color-text)] transition-colors"
            >
                {label}
            </span>
        </label>
    );
}
