import { AppLayout } from '@/components/layouts/AppLayout';
import { Logo } from '@/components/atoms/Logo';
import { InfoStrip } from '@/components/molecules/InfoStrip';
import { SecretEditor } from '@/components/organisms/SecretEditor';
import { usePage } from '@inertiajs/react';
import type { HomePageProps, SharedPageProps } from '@/types';

export default function Home({ fileUploadsEnabled, maxSizeGb }: HomePageProps) {
    const { appName } = usePage<SharedPageProps>().props;

    return (
        <AppLayout>
            <div className="text-center mb-8">
                <Logo />
                <h1 className="text-3xl font-semibold tracking-tight mb-1">
                    {appName}
                </h1>
                <p className="text-[var(--color-text)]/60 text-sm">Share secrets securely with end-to-end encryption</p>
                <InfoStrip
                    items={[
                        { icon: 'eye', text: 'Secrets are deleted after being viewed once' },
                        { icon: 'clock', text: 'Unretrieved secrets auto-expire after 30 days' },
                    ]}
                    className="mt-4"
                />
            </div>

            <SecretEditor fileUploadsEnabled={fileUploadsEnabled} maxSizeGb={maxSizeGb} />
        </AppLayout>
    );
}
