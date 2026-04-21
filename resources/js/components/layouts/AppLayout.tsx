import { BackgroundOrbs } from '@/components/atoms/BackgroundOrbs';
import { ProfileMenu } from '@/components/molecules/ProfileMenu';
import { applyBrandingColors } from '@/lib/colors';
import type { SharedPageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { type ReactNode, useEffect } from 'react';

interface AppLayoutProps {
    title?: string;
    children: ReactNode;
    maxWidth?: string;
    showFooter?: boolean;
    showTrustBadges?: boolean;
    footer?: ReactNode;
}

export function AppLayout({
    title,
    children,
    maxWidth = 'max-w-5xl',
    showFooter = true,
    showTrustBadges = true,
    footer,
}: AppLayoutProps) {
    const { branding, appName, auth, features } =
        usePage<SharedPageProps>().props;
    const showHeader = !!(auth?.user || auth?.guest) && !!features?.auth;

    useEffect(() => {
        if (!branding) return;
        applyBrandingColors(
            branding.primary,
            branding.secondary,
            branding.accent,
            branding.background,
            branding.foreground,
            branding.action,
        );
    }, [branding]);

    const pageTitle = title ? `${title}` : appName;

    return (
        <>
            <Head title={pageTitle} />
            <div className="min-h-screen bg-gradient-to-br from-[var(--color-surface)] via-[var(--color-primary-950)] to-[var(--color-surface)] text-[var(--color-text)] antialiased">
                <BackgroundOrbs />

                {showHeader && (
                    <header className="sticky top-0 z-50 border-b border-white/5 bg-[var(--color-surface)]/70 backdrop-blur-md">
                        <div
                            className={`mx-auto ${maxWidth} flex items-center justify-end px-6 py-3`}
                        >
                            <ProfileMenu />
                        </div>
                    </header>
                )}

                <div className="relative flex flex-col items-center px-6 pt-6 pb-6">
                    <div className={`w-full ${maxWidth}`}>
                        {children}

                        {showFooter && (showTrustBadges || footer) && (
                            <div className="mt-10 border-t border-white/10 pt-6">
                                {showTrustBadges && (
                                    <div className="flex flex-col flex-wrap items-center justify-center gap-x-10 gap-y-3 text-sm text-[var(--color-text)]/40 sm:flex-row">
                                        <div className="flex items-center gap-2">
                                            <svg
                                                className="h-4 w-4 shrink-0"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                                />
                                            </svg>
                                            <span>End-to-End Encrypted</span>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <svg
                                                className="h-4 w-4 shrink-0"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                                                />
                                            </svg>
                                            <span>
                                                Key Never Leaves Browser
                                            </span>
                                        </div>
                                    </div>
                                )}
                                {footer}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
