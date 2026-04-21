import { uploadFile } from '@/services/fileUpload';
import { useCallback, useState } from 'react';

type UploadState = 'idle' | 'uploading' | 'done' | 'error';

interface UseFileUploadReturn {
    state: UploadState;
    progress: number;
    fileId: string | null;
    encryptionKey: string | null;
    error: string | null;
    upload: (
        file: File,
    ) => Promise<{ fileId: string; encryptionKey: string } | null>;
    reset: () => void;
}

export function useFileUpload(): UseFileUploadReturn {
    const [state, setState] = useState<UploadState>('idle');
    const [progress, setProgress] = useState(0);
    const [fileId, setFileId] = useState<string | null>(null);
    const [encryptionKey, setEncryptionKey] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);

    const upload = useCallback(
        async (
            file: File,
        ): Promise<{ fileId: string; encryptionKey: string } | null> => {
            setState('uploading');
            setProgress(0);
            setError(null);
            try {
                const result = await uploadFile(file, (percent) =>
                    setProgress(percent),
                );
                setFileId(result.fileId);
                setEncryptionKey(result.encryptionKey);
                setState('done');
                return result;
            } catch (err) {
                setError(err instanceof Error ? err.message : 'Upload failed');
                setState('error');
                return null;
            }
        },
        [],
    );

    const reset = useCallback(() => {
        setState('idle');
        setProgress(0);
        setFileId(null);
        setEncryptionKey(null);
        setError(null);
    }, []);

    return { state, progress, fileId, encryptionKey, error, upload, reset };
}
