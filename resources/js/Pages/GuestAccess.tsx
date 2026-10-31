import { Button } from '@/components/atoms/Button';
import { Logo } from '@/components/atoms/Logo';
import { AppLayout } from '@/components/layouts/AppLayout';
import { GlowCard } from '@/components/molecules/GlowCard';
import type { GuestAccessPageProps } from '@/types';
import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function GuestAccess({
    acceptUrl,
    expiresAt,
}: GuestAccessPageProps) {
    const [submitting, setSubmitting] = useState(false);
    const expiry = new Date(expiresAt).toLocaleString();

    const continueAsGuest = () => {
        setSubmitting(true);
        router.post(acceptUrl, {}, { onFinish: () => setSubmitting(false) });
    };

    return (
        <AppLayout title="Guest Access" maxWidth="max-w-sm" showFooter={false}>
            <div className="mb-8 text-center">
                <Logo linked={false} />
                <h1 className="mb-1 text-3xl font-semibold tracking-tight">
                    You're invited
                </h1>
                <p className="text-sm text-[var(--color-text)]/60">
                    Share secrets as a guest until {expiry}
                </p>
            </div>

            <GlowCard>
                <Button
                    className="w-full"
                    onClick={continueAsGuest}
                    disabled={submitting}
                >
                    Continue as guest
                </Button>

                <p className="mt-4 text-center text-xs text-[var(--color-text)]/40">
                    This link works once, in this browser only
                </p>
            </GlowCard>
        </AppLayout>
    );
}
