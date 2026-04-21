import { cn } from '@/lib/utils';
import type { Branding, SharedPageProps } from '@/types';
import { usePage } from '@inertiajs/react';

interface LogoProps {
    linked?: boolean;
    size?: 'large' | 'small';
}

function LogoImage({
    branding,
    className,
}: {
    branding: Branding;
    className?: string;
}) {
    if (branding.logo.image) {
        return (
            <img src={branding.logo.image} alt="Logo" className={className} />
        );
    }
    return (
        <div
            className={className}
            dangerouslySetInnerHTML={{ __html: branding.logo.svg }}
        />
    );
}

export function Logo({ linked = true, size = 'large' }: LogoProps) {
    const { branding } = usePage<SharedPageProps>().props;
    if (!branding) return null;
    const sizeClass = branding.logoSize[size];
    const Tag = linked ? 'a' : 'div';
    const linkProps = linked ? { href: '/' } : {};

    if (branding.logo.show_container) {
        return (
            <Tag
                {...linkProps}
                className={cn(
                    'mb-5 inline-flex items-center justify-center rounded-2xl bg-gradient-to-br from-[var(--color-primary-500)] to-[var(--color-secondary-600)] p-3.5 shadow-[var(--color-primary-500)]/30 shadow-lg',
                    linked &&
                        'transition-transform duration-200 hover:scale-105',
                    sizeClass,
                )}
            >
                <LogoImage
                    branding={branding}
                    className="h-full w-full object-contain"
                />
            </Tag>
        );
    }

    return (
        <Tag
            {...linkProps}
            className={cn(
                'mb-5 inline-block',
                linked && 'transition-transform duration-200 hover:scale-105',
            )}
        >
            <LogoImage
                branding={branding}
                className={branding.logo.image ? 'h-14 w-auto' : sizeClass}
            />
        </Tag>
    );
}
