import { usePage } from '@inertiajs/react';
import type { SharedPageProps, Branding } from '@/types';
import { cn } from '@/lib/utils';

interface LogoProps {
    linked?: boolean;
    size?: 'large' | 'small';
}

function LogoImage({ branding, className }: { branding: Branding; className?: string }) {
    if (branding.logo.image) {
        return <img src={branding.logo.image} alt="Logo" className={className} />;
    }
    return <div className={className} dangerouslySetInnerHTML={{ __html: branding.logo.svg }} />;
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
                    'inline-flex items-center justify-center mb-5 p-3.5 rounded-2xl bg-gradient-to-br from-[var(--color-primary-500)] to-[var(--color-secondary-600)] shadow-lg shadow-[var(--color-primary-500)]/30',
                    linked && 'hover:scale-105 transition-transform duration-200',
                    sizeClass,
                )}
            >
                <LogoImage branding={branding} className="w-full h-full object-contain" />
            </Tag>
        );
    }

    return (
        <Tag
            {...linkProps}
            className={cn(
                'inline-block mb-5',
                linked && 'hover:scale-105 transition-transform duration-200',
            )}
        >
            <LogoImage branding={branding} className={branding.logo.image ? 'h-14 w-auto' : sizeClass} />
        </Tag>
    );
}
