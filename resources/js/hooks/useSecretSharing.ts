import {
    shareSecret,
    validateShareInputs,
    type ShareResult,
} from '@/services/secretSharing';
import { useCallback, useState } from 'react';

type SharingState = 'idle' | 'encrypting' | 'saving' | 'done' | 'error';

interface UseSecretSharingReturn {
    state: SharingState;
    result: ShareResult | null;
    error: string | null;
    clearError: () => void;
    share: (params: {
        content: string;
        uploadedFileId: string | null;
        fileEncryptionKey: string | null;
        password: string;
        enablePassword: boolean;
        markdownEnabled: boolean;
        fileUploadPending: boolean;
    }) => Promise<void>;
    reset: () => void;
}

export function useSecretSharing(): UseSecretSharingReturn {
    const [state, setState] = useState<SharingState>('idle');
    const [result, setResult] = useState<ShareResult | null>(null);
    const [error, setError] = useState<string | null>(null);

    const share = useCallback(
        async (params: {
            content: string;
            uploadedFileId: string | null;
            fileEncryptionKey: string | null;
            password: string;
            enablePassword: boolean;
            markdownEnabled: boolean;
            fileUploadPending: boolean;
        }) => {
            const validationError = validateShareInputs(
                params.content,
                !!params.uploadedFileId,
                params.password,
                params.enablePassword,
                params.fileUploadPending,
            );

            if (validationError) {
                setError(validationError);
                setState('error');
                return;
            }

            setError(null);
            setState('encrypting');

            try {
                setState('saving');
                const shareResult = await shareSecret({
                    content: params.content,
                    uploadedFileId: params.uploadedFileId,
                    fileEncryptionKey: params.fileEncryptionKey,
                    password: params.password,
                    markdownEnabled: params.markdownEnabled,
                });
                setResult(shareResult);
                setState('done');
            } catch (err) {
                setError(
                    err instanceof Error
                        ? err.message
                        : 'Failed to share. Please try again.',
                );
                setState('error');
            }
        },
        [],
    );

    const clearError = useCallback(() => {
        setError(null);
        if (state === 'error') setState('idle');
    }, [state]);

    const reset = useCallback(() => {
        setState('idle');
        setResult(null);
        setError(null);
    }, []);

    return { state, result, error, clearError, share, reset };
}
