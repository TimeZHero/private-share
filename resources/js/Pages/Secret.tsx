import { AppLayout } from '@/components/layouts/AppLayout';
import { Logo } from '@/components/atoms/Logo';
import { SecretViewer } from '@/components/organisms/SecretViewer';
import type { SecretPageProps } from '@/types';

export default function Secret({ secretId, createdAt }: SecretPageProps) {
    return (
        <AppLayout title="View Secret">
            <div className="text-center mb-2">
                <Logo />
            </div>

            <SecretViewer secretId={secretId} createdAt={createdAt} />
        </AppLayout>
    );
}
