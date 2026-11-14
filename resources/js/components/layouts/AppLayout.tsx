import { BackgroundOrbs } from '@/components/atoms/BackgroundOrbs';
import { Logo } from '@/components/atoms/Logo';
import { InfoButton } from '@/components/molecules/InfoButton';
import { ProfileMenu } from '@/components/molecules/ProfileMenu';
import { applyBrandingColors } from '@/lib/colors';
import type { SharedPageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { type ReactNode, useEffect } from 'react';

const footerNotes = [
    {
        text: 'Encrypted end-to-end in your browser',
        iconPaths: [
            'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
        ],
    },
    {
        text: 'Secrets are deleted after being viewed once',
        iconPaths: [
            'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
            'M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
        ],
    },
    {
        text: 'Unretrieved secrets auto-expire after 30 days',
        iconPaths: ['M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
    },
];

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

                <header className="sticky top-0 z-50 border-b border-white/5 bg-[var(--color-surface)]/70 backdrop-blur-md">
                    <div className="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
                        <Logo />
                        {authEnabled ? (
                            isAuthenticated && <ProfileMenu />
                        ) : (
                            <InfoButton />
                        )}
                    </div>
                </header>

                <div className="relative flex flex-col items-center px-4 pt-6 pb-6 sm:px-6">
                    <div className={`w-full ${maxWidth}`}>
                        {children}

                        {showFooter && (
                            <div className="mt-6 border-t border-white/10 pt-4">
                                <ul className="flex flex-col flex-wrap items-center justify-center gap-x-10 gap-y-3 text-sm text-[var(--color-text)]/40 sm:flex-row">
                                    {footerNotes.map((note) => (
                                        <li
                                            key={note.text}
                                            className="flex items-center gap-2"
                                        >
                                            <svg
                                                className="h-4 w-4 shrink-0"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                {note.iconPaths.map((path) => (
                                                    <path
                                                        key={path}
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        strokeWidth={2}
                                                        d={path}
                                                    />
                                                ))}
                                            </svg>
                                            <span>{note.text}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
