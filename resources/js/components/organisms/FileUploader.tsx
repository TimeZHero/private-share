import { useFileUpload } from '@/hooks/useFileUpload';
import { useCallback, useRef, useState } from 'react';

interface FileUploaderProps {
    maxSizeGb: number;
    onFileUploaded: (fileId: string, encryptionKey: string) => void;
    onFileRemoved: () => void;
    onFileAdded: () => void;
}

export function FileUploader({
    maxSizeGb,
    onFileUploaded,
    onFileRemoved,
    onFileAdded,
}: FileUploaderProps) {
    const { state, progress, upload, reset } = useFileUpload();
    const inputRef = useRef<HTMLInputElement>(null);
    const [fileName, setFileName] = useState<string | null>(null);

    const handleFileChange = useCallback(
        async (event: React.ChangeEvent<HTMLInputElement>) => {
            const file = event.target.files?.[0];
            if (!file) return;

            const maxBytes = maxSizeGb * 1024 * 1024 * 1024;
            if (file.size > maxBytes) {
                alert(`File too large. Maximum size is ${maxSizeGb} GB.`);
                return;
            }

            setFileName(file.name);
            onFileAdded();

            try {
                const result = await upload(file);
                if (result) {
                    onFileUploaded(result.fileId, result.encryptionKey);
                }
            } catch {
                // error is handled by the hook state
            }
        },
        [maxSizeGb, upload, onFileAdded, onFileUploaded],
    );

    const handleRemove = useCallback(() => {
        reset();
        setFileName(null);
        onFileRemoved();
        if (inputRef.current) inputRef.current.value = '';
    }, [reset, onFileRemoved]);

    if (state === 'idle') {
        return (
            <div className="mb-4">
                <label className="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-white/15 bg-[var(--color-surface-light)]/60 p-6 transition-colors hover:border-white/30">
                    <svg
                        className="h-8 w-8 text-[var(--color-text)]/40"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                        />
                    </svg>
                    <span className="text-sm text-[var(--color-text)]/60">
                        Drag & drop a file or{' '}
                        <span className="text-[var(--color-primary-400)] underline">
                            Browse
                        </span>
                        <span className="ml-1 text-xs text-[var(--color-text)]/30">
                            (up to {maxSizeGb} GB)
                        </span>
                    </span>
                    <input
                        ref={inputRef}
                        type="file"
                        className="hidden"
                        onChange={handleFileChange}
                    />
                </label>
            </div>
        );
    }

    const statusText =
        state === 'uploading'
            ? `Encrypting & uploading... ${progress}%`
            : state === 'done'
              ? 'Encrypted & uploaded'
              : state === 'error'
                ? 'Upload failed'
                : '';

    const borderColor =
        state === 'done'
            ? 'border-emerald-500/40'
            : state === 'error'
              ? 'border-red-500/40'
              : 'border-white/10';

    const fillColor =
        state === 'done'
            ? 'bg-emerald-500/15'
            : state === 'error'
              ? 'bg-red-500/15'
              : 'bg-[var(--color-button)]/15';

    const fillWidth =
        state === 'uploading'
            ? `${progress}%`
            : state === 'done' || state === 'error'
              ? '100%'
              : '0%';

    const iconColor =
        state === 'done'
            ? 'text-emerald-400'
            : state === 'error'
              ? 'text-red-400'
              : 'text-[var(--color-text)]/80';

    return (
        <div className="mb-4">
            <div
                className={`relative flex items-center gap-3 bg-[var(--color-surface-light)] p-4 ${borderColor} overflow-hidden rounded-xl border`}
            >
                <div
                    className={`absolute inset-0 ${fillColor} transition-all duration-300 ease-out`}
                    style={{ width: fillWidth }}
                />
                <svg
                    className={`relative h-5 w-5 ${iconColor} shrink-0`}
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    {state === 'done' ? (
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M5 13l4 4L19 7"
                        />
                    ) : state === 'error' ? (
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    ) : (
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    )}
                </svg>
                <div className="relative min-w-0 flex-1">
                    <p className="truncate text-sm text-[var(--color-text)]">
                        {fileName}
                    </p>
                    <p className="text-xs text-[var(--color-text)]/60">
                        {statusText}
                    </p>
                </div>
                <button
                    type="button"
                    onClick={handleRemove}
                    className="relative text-[var(--color-text)]/60 transition-colors hover:text-[var(--color-text)]"
                >
                    <svg
                        className="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>
    );
}
