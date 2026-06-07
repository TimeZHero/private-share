import { useFlashToast } from '@/hooks/useFlashToast';
import { Toaster as SonnerToaster } from 'sonner';

export function Toaster() {
    useFlashToast();

    return (
        <SonnerToaster
            theme="dark"
            position="bottom-right"
            closeButton
            toastOptions={{
                style: {
                    background: 'var(--color-surface-light)',
                    color: 'var(--color-text)',
                    border: '1px solid rgba(255, 255, 255, 0.1)',
                },
            }}
        />
    );
}
