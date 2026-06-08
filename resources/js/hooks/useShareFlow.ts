import { uploadFile } from '@/services/fileUpload';
import type { ShareResult } from '@/services/secretSharing';
import { shareSecret, validateShareInputs } from '@/services/secretSharing';
import { useCallback, useState } from 'react';

export type ShareFlowState =
    | 'idle'
    | 'uploading'
    | 'encrypting'
    | 'saving'
    | 'done'
    | 'error';

interface ShareFlowParams {
    content: string;
    file: File | null;
    password: string;
    enablePassword: boolean;
    markdownEnabled: boolean;
}

interface UseShareFlowReturn {
    state: ShareFlowState;
    uploadProgress: number;
    result: ShareResult | null;
    error: string | null;
    clearError: () => void;
    share: (params: ShareFlowParams) => Promise<void>;
    reset: () => void;
}

export function useShareFlow(): UseShareFlowReturn {
    const [state, setState] = useState<ShareFlowState>('idle');
    const [uploadProgress, setUploadProgress] = useState(0);
    const [result, setResult] = useState<ShareResult | null>(null);
    const [error, setError] = useState<string | null>(null);

    const share = useCallback(async (params: ShareFlowParams) => {
        const { content, file, password, enablePassword, markdownEnabled } =
            params;

        const validationError = validateShareInputs(
            content,
            !!file,
            password,
            enablePassword,
            false,
        );

        if (validationError) {
            setError(validationError);
            setState('error');
            return;
        }

        setError(null);

        try {
            let uploadedFileId: string | null = null;
            let fileEncryptionKey: string | null = null;

            if (file) {
                setState('uploading');
                setUploadProgress(0);
                const uploadResult = await uploadFile(file, (percent) =>
                    setUploadProgress(percent),
                );
                uploadedFileId = uploadResult.fileId;
                fileEncryptionKey = uploadResult.encryptionKey;
            }

            setState('encrypting');

            const shareResult = await shareSecret({
                content,
                uploadedFileId,
                fileEncryptionKey,
                password,
                markdownEnabled,
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
    }, []);

    const clearError = useCallback(() => {
        setError(null);
        if (state === 'error') setState('idle');
    }, [state]);

    const reset = useCallback(() => {
        setState('idle');
        setUploadProgress(0);
        setResult(null);
        setError(null);
    }, []);

    return { state, uploadProgress, result, error, clearError, share, reset };
}
