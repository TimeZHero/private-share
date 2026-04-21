import { Logo } from '@/components/atoms/Logo';
import { AppLayout } from '@/components/layouts/AppLayout';
import { SecretViewer } from '@/components/organisms/SecretViewer';
import type { SecretPageProps } from '@/types';

export default function Secret({ secretId, createdAt }: SecretPageProps) {
    return (
        <AppLayout title="View Secret">
            <div className="mb-2 text-center">
                <Logo />
            </div>

            <SecretViewer secretId={secretId} createdAt={createdAt} />
        </AppLayout>
    );
}
