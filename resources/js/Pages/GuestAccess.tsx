import { Button } from '@/components/atoms/Button';
import { AppLayout } from '@/components/layouts/AppLayout';
import { GlowCard } from '@/components/molecules/GlowCard';
import { formatExpiry } from '@/services/guestLink';
import type { GuestAccessPageProps } from '@/types';
import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function GuestAccess({
    acceptUrl,
    expiresAt,
}: GuestAccessPageProps) {
    const [submitting, setSubmitting] = useState(false);
    const expiry = formatExpiry(expiresAt);

    const continueAsGuest = () => {
        setSubmitting(true);
        router.post(acceptUrl, {}, { onFinish: () => setSubmitting(false) });
    };

    return (
        <AppLayout title="Guest Access" maxWidth="max-w-sm" showFooter={false}>
            <div className="mt-6 mb-6 text-center">
                <h1 className="mb-1 text-3xl font-semibold tracking-tight">
                    You're invited
                </h1>
                <p className="text-sm text-[var(--color-text)]/60">
                    Guest access expires {expiry}
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
                    You can only use this link one time. Do not use if you are
                    on a shared computer
                </p>
            </GlowCard>
        </AppLayout>
    );
}
