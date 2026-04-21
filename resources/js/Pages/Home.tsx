import { Logo } from '@/components/atoms/Logo';
import { AppLayout } from '@/components/layouts/AppLayout';
import { InfoStrip } from '@/components/molecules/InfoStrip';
import { SecretEditor } from '@/components/organisms/SecretEditor';
import type { HomePageProps, SharedPageProps } from '@/types';
import { usePage } from '@inertiajs/react';

export default function Home({ fileUploadsEnabled, maxSizeGb }: HomePageProps) {
    const { appName } = usePage<SharedPageProps>().props;

    return (
        <AppLayout>
            <div className="mb-8 text-center">
                <Logo />
                <h1 className="mb-1 text-3xl font-semibold tracking-tight">
                    {appName}
                </h1>
                <p className="text-sm text-[var(--color-text)]/60">
                    Share secrets securely with end-to-end encryption
                </p>
                <InfoStrip
                    items={[
                        {
                            icon: 'eye',
                            text: 'Secrets are deleted after being viewed once',
                        },
                        {
                            icon: 'clock',
                            text: 'Unretrieved secrets auto-expire after 30 days',
                        },
                    ]}
                    className="mt-4"
                />
            </div>

            <SecretEditor
                fileUploadsEnabled={fileUploadsEnabled}
                maxSizeGb={maxSizeGb}
            />
        </AppLayout>
    );
}
