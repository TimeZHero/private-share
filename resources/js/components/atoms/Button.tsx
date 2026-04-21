import { cn } from '@/lib/utils';
import { type ButtonHTMLAttributes, type ReactNode } from 'react';

type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger';
type ButtonSize = 'default' | 'sm';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: ButtonVariant;
    size?: ButtonSize;
    children: ReactNode;
    as?: 'button' | 'a';
    href?: string;
}

const variantClasses: Record<ButtonVariant, string> = {
    primary:
        'bg-[var(--color-button)] hover:bg-[var(--color-button-hover)] text-[var(--color-button-contrast)] shadow-lg shadow-[var(--color-button)]/25 hover:shadow-[var(--color-button)]/40',
    secondary:
        'bg-[var(--color-surface-light)] hover:brightness-125 text-[var(--color-text)]/80 hover:text-[var(--color-text)] border border-white/10 hover:border-white/20',
    ghost: 'text-[var(--color-text)]/60 hover:text-[var(--color-text)] hover:bg-[var(--color-surface-light)]',
    danger: 'bg-red-600 hover:bg-red-500 text-white shadow-lg shadow-red-500/25',
};

const sizeClasses: Record<ButtonSize, string> = {
    default: 'px-6 py-2.5 text-base rounded-xl',
    sm: 'px-4 py-1.5 text-sm rounded-lg',
};

export function Button({
    variant = 'primary',
    size = 'default',
    children,
    className,
    as,
    href,
    ...props
}: ButtonProps) {
    const classes = cn(
        'inline-flex cursor-pointer items-center justify-center gap-2 font-bold transition-[color,background-color,border-color,box-shadow,opacity] duration-200 disabled:cursor-not-allowed disabled:opacity-50',
        sizeClasses[size],
        variantClasses[variant],
        className,
    );

    if (as === 'a' && href) {
        return (
            <a href={href} className={classes}>
                {children}
            </a>
        );
    }

    return (
        <button type="button" className={classes} {...props}>
            {children}
        </button>
    );
}
