import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

interface GlowCardProps {
    children: ReactNode;
    from?: string;
    via?: string;
    to?: string;
    opacity?: string;
    padding?: string;
    className?: string;
    focusable?: boolean;
}

export function GlowCard({
    children,
    from,
    via,
    to,
    opacity = 'opacity-40',
    padding = 'p-6',
    className,
    focusable = false,
}: GlowCardProps) {
    const gradientFrom = from ?? 'var(--color-primary-600)';
    const gradientVia = via ?? 'var(--color-secondary-600)';
    const gradientTo = to ?? 'var(--color-primary-600)';

    const isCustomColor = !from;
    const gradientStyle = isCustomColor
        ? {
              backgroundImage: `linear-gradient(to right, ${gradientFrom}, ${gradientVia}, ${gradientTo})`,
          }
        : undefined;

    const gradientClasses = isCustomColor
        ? ''
        : `from-${from} via-${via} to-${to}`;

    return (
        <div className={cn('group relative flex flex-col', className)}>
            <div
                className={cn(
                    'absolute -inset-1 rounded-2xl blur-sm',
                    opacity,
                    !isCustomColor && `bg-gradient-to-r ${gradientClasses}`,
                    focusable &&
                        'transition-opacity group-focus-within:opacity-60',
                )}
                style={isCustomColor ? gradientStyle : undefined}
            />
            <div
                className={cn(
                    'relative flex-1 rounded-2xl border border-white/10 bg-[var(--color-surface)]/90 backdrop-blur-sm',
                    padding,
                )}
            >
                {children}
            </div>
        </div>
    );
}
