import { AppLayout } from '@/components/layouts/AppLayout';
import { SecretEditor } from '@/components/organisms/SecretEditor';
import type { HomePageProps } from '@/types';

export default function Home({ fileUploadsEnabled, maxSizeGb }: HomePageProps) {
    return (
        <AppLayout>
            <SecretEditor
                fileUploadsEnabled={fileUploadsEnabled}
                maxSizeGb={maxSizeGb}
            />
        </AppLayout>
    );
}
