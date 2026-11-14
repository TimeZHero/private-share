import { AppLayout } from '@/components/layouts/AppLayout';
import { SecretViewer } from '@/components/organisms/SecretViewer';
import type { SecretPageProps } from '@/types';

export default function Secret({ secretId, createdAt }: SecretPageProps) {
    return (
        <AppLayout title="View Secret">
            <SecretViewer secretId={secretId} createdAt={createdAt} />
        </AppLayout>
    );
}
