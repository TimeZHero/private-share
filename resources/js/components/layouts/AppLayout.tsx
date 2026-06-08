import { BackgroundOrbs } from '@/components/atoms/BackgroundOrbs';
import { InfoButton } from '@/components/molecules/InfoButton';
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
}

export function AppLayout({
    title,
    children,
    maxWidth = 'max-w-5xl',
    showFooter = true,
}: AppLayoutProps) {
    const { branding, appName, auth, features } =
        usePage<SharedPageProps>().props;
    const authEnabled = !!features?.auth;
    const isAuthenticated = !!(auth?.user || auth?.guest);
    const showHeader = authEnabled ? isAuthenticated : true;

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
                            className={`mx-auto ${maxWidth} flex items-center justify-end gap-3 px-6 py-3`}
                        >
                            {authEnabled ? <ProfileMenu /> : <InfoButton />}
                        </div>
                    </header>
                )}

                <div className="relative flex flex-col items-center px-6 pt-6 pb-6">
                    <div className={`w-full ${maxWidth}`}>
                        {children}

                        {showFooter && (
                            <div className="mt-10 border-t border-white/10 pt-6">
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
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                        <span>
                                            Secrets are deleted after being
                                            viewed once
                                        </span>
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
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        <span>
                                            Unretrieved secrets auto-expire
                                            after 30 days
                                        </span>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
