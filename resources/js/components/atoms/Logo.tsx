import type { Branding, SharedPageProps } from '@/types';
import { usePage } from '@inertiajs/react';

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

export function Logo() {
    const { branding, appName } = usePage<SharedPageProps>().props;
    if (!branding) return null;

    return (
        <a
            href="/"
            aria-label={appName}
            className="group/logo flex min-w-0 items-center gap-3"
        >
            {branding.logo.show_container ? (
                <span className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[var(--color-primary-500)] to-[var(--color-secondary-600)] p-2 shadow-[var(--color-primary-500)]/30 shadow-md transition-transform duration-200 group-hover/logo:scale-105">
                    <LogoImage
                        branding={branding}
                        className="h-full w-full object-contain"
                    />
                </span>
            ) : (
                <LogoImage
                    branding={branding}
                    className={
                        branding.logo.image
                            ? 'h-9 w-auto shrink-0'
                            : 'h-9 w-9 shrink-0'
                    }
                />
            )}
            <span className="truncate text-lg font-semibold tracking-tight">
                {appName}
            </span>
        </a>
    );
}
