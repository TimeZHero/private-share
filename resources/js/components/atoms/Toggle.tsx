import { cn } from '@/lib/utils';
import { type InputHTMLAttributes } from 'react';

interface ToggleProps extends Omit<
    InputHTMLAttributes<HTMLInputElement>,
    'type'
> {
    label: string;
    labelId?: string;
}

export function Toggle({
    label,
    labelId,
    id,
    className,
    ...props
}: ToggleProps) {
    return (
        <label
            className={cn(
                'group flex cursor-pointer items-center gap-2',
                className,
            )}
            htmlFor={id}
        >
            <input
                type="checkbox"
                id={id}
                className="toggle-input sr-only"
                {...props}
            />
            <div className="toggle-switch-sm" />
            <span
                id={labelId ?? (id ? `${id}-label` : undefined)}
                className="text-sm text-[var(--color-text)]/60 transition-colors group-hover:text-[var(--color-text)]"
            >
                {label}
            </span>
        </label>
    );
}
