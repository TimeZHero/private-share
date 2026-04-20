import { type ReactNode } from 'react';
import { AppLayout } from './AppLayout';
import { Button } from '@/components/atoms/Button';

interface ErrorLayoutProps {
    code: number;
    title: string;
    description: string;
    extra?: ReactNode;
    actions?: ReactNode;
}

export function ErrorLayout({ code, title, description, extra, actions }: ErrorLayoutProps) {
    return (
        <AppLayout title={`${title} - Error`} maxWidth="max-w-2xl" showTrustBadges={false}>
            <div className="flex flex-col items-center justify-center min-h-[75vh]">
                <div className="text-center mb-10">
                    <div className="text-[10rem] leading-none font-extrabold tracking-tighter text-[var(--color-button)] select-none">
                        {code}
                    </div>

                    <h1 className="text-2xl font-semibold tracking-tight mt-4 mb-4">{title}</h1>
                    <p className="text-[var(--color-text)]/60 text-base leading-relaxed max-w-md mx-auto">{description}</p>
                </div>

                {extra && <div className="mb-8">{extra}</div>}

                <div className="flex items-center justify-center gap-4">
                    {actions ?? (
                        <Button as="a" href="/">
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Home
                        </Button>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
