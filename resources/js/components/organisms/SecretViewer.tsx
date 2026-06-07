import { Button } from '@/components/atoms/Button';
import { ErrorAlert } from '@/components/atoms/ErrorAlert';
import { Input } from '@/components/atoms/Input';
import { GlowCard } from '@/components/molecules/GlowCard';
import { PasswordInput } from '@/components/molecules/PasswordInput';
import { useSecretViewing } from '@/hooks/useSecretViewing';
import { copyToClipboard } from '@/lib/clipboard';
import { downloadAndDecrypt } from '@/services/fileDownload';
import { renderMarkdown } from '@/services/markdown';
import { useCallback, useEffect, useRef, useState } from 'react';

interface SecretViewerProps {
    secretId: string;
    createdAt: string;
}

export function SecretViewer({ secretId, createdAt }: SecretViewerProps) {
    const {
        viewState,
        data,
        loadingTitle,
        loadingText,
        errorDetail,
        requiresPassword,
        passwordError,
        check,
        confirmAndRetrieve,
        retryDecryption,
        isAlreadyViewed,
        markAsViewed,
    } = useSecretViewing();

    const [accessPassword, setAccessPassword] = useState('');
    const [retryKeyInput, setRetryKeyInput] = useState('');
    const [copyText, setCopyText] = useState('Copy');
    const [copyMdText, setCopyMdText] = useState('Copy as Markdown');
    const [downloadText, setDownloadText] = useState('Download');
    const [downloadDisabled, setDownloadDisabled] = useState(false);
    const passwordInputRef = useRef<HTMLInputElement>(null);
    const retryInputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (isAlreadyViewed(secretId)) {
            return;
        }
        check(secretId);
    }, [secretId, check, isAlreadyViewed]);

    useEffect(() => {
        if (viewState === 'success') {
            markAsViewed(secretId);
        }
    }, [viewState, secretId, markAsViewed]);

    useEffect(() => {
        function handlePageShow(event: PageTransitionEvent) {
            if (event.persisted || isAlreadyViewed(secretId)) {
                // Already viewed guard — force error state by re-checking
            }
        }
        window.addEventListener('pageshow', handlePageShow);
        return () => window.removeEventListener('pageshow', handlePageShow);
    }, [secretId, isAlreadyViewed]);

    const handleConfirm = useCallback(async () => {
        await confirmAndRetrieve(secretId, accessPassword || undefined);
    }, [secretId, accessPassword, confirmAndRetrieve]);

    const handleRetry = useCallback(async () => {
        await retryDecryption(retryKeyInput);
    }, [retryKeyInput, retryDecryption]);

    const handleCopyContent = useCallback(async () => {
        if (!data.content) return;
        let textToCopy: string;
        if (data.markdownEnabled) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = renderMarkdown(data.content);
            textToCopy =
                tempDiv.innerText || tempDiv.textContent || data.content;
        } else {
            textToCopy = data.content;
        }
        await copyToClipboard(textToCopy);
        setCopyText('Copied!');
        setTimeout(() => setCopyText('Copy'), 2000);
    }, [data.content, data.markdownEnabled]);

    const handleCopyMarkdown = useCallback(async () => {
        if (!data.content) return;
        await copyToClipboard(data.content);
        setCopyMdText('Copied!');
        setTimeout(() => setCopyMdText('Copy as Markdown'), 2000);
    }, [data.content]);

    const handleDownload = useCallback(async () => {
        if (!data.fileId || !data.encryptionKey) return;
        setDownloadDisabled(true);
        setDownloadText('Downloading...');
        try {
            await downloadAndDecrypt(
                data.fileId,
                data.encryptionKey,
                data.fileInfo,
                (percent) => {
                    setDownloadText(`Decrypting ${percent}%`);
                },
            );
            setDownloadText('Downloaded');
        } catch (error) {
            console.error('File download/decrypt error:', error);
            setDownloadText('Download failed');
            setTimeout(() => {
                setDownloadText('Download');
                setDownloadDisabled(false);
            }, 3000);
        }
    }, [data.fileId, data.encryptionKey, data.fileInfo]);

    if (viewState === 'loading' && isAlreadyViewed(secretId)) {
        return (
            <ErrorView
                errorDetail="This secret has already been viewed and cannot be displayed again."
                showRetry={false}
                retryKeyInput=""
                onRetryKeyChange={() => {}}
                onRetry={() => {}}
                retryInputRef={retryInputRef}
            />
        );
    }

    if (viewState === 'loading') {
        return (
            <div className="text-center">
                <div className="mb-5 inline-flex h-14 w-14 animate-pulse items-center justify-center rounded-2xl bg-[var(--color-button)] shadow-[var(--color-button)]/30 shadow-lg">
                    <svg
                        className="h-7 w-7 animate-spin text-[var(--color-button-contrast)]"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            className="opacity-25"
                            cx={12}
                            cy={12}
                            r={10}
                            stroke="currentColor"
                            strokeWidth={4}
                        />
                        <path
                            className="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        />
                    </svg>
                </div>
                <h1 className="mb-2 text-2xl font-semibold tracking-tight">
                    {loadingTitle}
                </h1>
                <p className="text-[var(--color-text)]/60">{loadingText}</p>
            </div>
        );
    }

    if (viewState === 'confirmation') {
        return (
            <>
                <div className="mb-8 text-center">
                    <h1 className="mb-3 text-3xl font-semibold tracking-tight">
                        View Secret?
                    </h1>
                    <p className="mx-auto max-w-md text-[var(--color-text)]/60">
                        Once you view it, the secret will be permanently
                        deleted.
                    </p>
                </div>

                <GlowCard opacity="opacity-30" className="mb-8">
                    <div className="flex items-start gap-4">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[var(--color-button)]/20">
                            <svg
                                className="h-5 w-5 text-[var(--color-button)]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3 className="mb-1 font-medium text-[var(--color-text)]">
                                This action cannot be undone
                            </h3>
                            <p className="text-sm text-[var(--color-text)]/60">
                                For security, secrets are deleted immediately
                                after being viewed. Make sure you're ready to
                                view and save the content if needed.
                            </p>
                        </div>
                    </div>
                </GlowCard>

                {requiresPassword && (
                    <GlowCard opacity="opacity-30" className="mb-6">
                        <div className="mb-4 flex items-center gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[var(--color-button)]/20">
                                <svg
                                    className="h-5 w-5 text-[var(--color-button)]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h3 className="font-medium text-[var(--color-text)]">
                                    Password Required
                                </h3>
                                <p className="text-sm text-[var(--color-text)]/60">
                                    This secret is password protected
                                </p>
                            </div>
                        </div>
                        <PasswordInput
                            ref={passwordInputRef}
                            value={accessPassword}
                            onChange={(event) =>
                                setAccessPassword(event.target.value)
                            }
                            placeholder="Enter password"
                            onKeyDown={(event) =>
                                event.key === 'Enter' && handleConfirm()
                            }
                        />
                        {passwordError && (
                            <ErrorAlert
                                message={passwordError}
                                className="mt-3"
                            />
                        )}
                    </GlowCard>
                )}

                <div className="flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <Button onClick={handleConfirm}>
                        <svg
                            className="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                            />
                        </svg>
                        View Secret
                    </Button>
                    <Button variant="secondary" as="a" href="/">
                        <svg
                            className="h-5 w-5"
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
                        Cancel
                    </Button>
                </div>
            </>
        );
    }

    if (viewState === 'error') {
        return (
            <ErrorView
                errorDetail={errorDetail}
                showRetry={true}
                retryKeyInput={retryKeyInput}
                onRetryKeyChange={setRetryKeyInput}
                onRetry={handleRetry}
                retryInputRef={retryInputRef}
            />
        );
    }

    // Success view
    return (
        <>
            <div className="mb-8 text-center">
                <h1 className="mb-2 text-3xl font-semibold tracking-tight">
                    Secret retrieved successfully
                </h1>
                <p className="text-[var(--color-text)]/60">
                    Shared on {data.createdAt ?? createdAt}
                </p>
            </div>

            {data.content && (
                <GlowCard padding="p-8" className="min-h-[200px]">
                    {data.markdownEnabled ? (
                        <div
                            className="prose max-w-none"
                            dangerouslySetInnerHTML={{
                                __html: renderMarkdown(data.content),
                            }}
                        />
                    ) : (
                        <pre className="text-sm leading-relaxed break-words whitespace-pre-wrap text-[var(--color-text)]">
                            {data.content}
                        </pre>
                    )}
                </GlowCard>
            )}

            {data.hasFile && data.fileInfo && (
                <GlowCard opacity="opacity-30" className="mt-4">
                    <div className="flex items-center gap-4">
                        <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-[var(--color-button)]/20">
                            <svg
                                className="h-6 w-6 text-[var(--color-button)]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="truncate font-medium text-[var(--color-text)]">
                                {data.fileInfo.original_name}
                            </p>
                            <p className="text-sm text-[var(--color-text)]/40">
                                {data.fileInfo.formatted_size} ·{' '}
                                {data.fileInfo.mime_type}
                            </p>
                        </div>
                        <Button
                            onClick={handleDownload}
                            disabled={downloadDisabled}
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
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                            {downloadText}
                        </Button>
                    </div>
                </GlowCard>
            )}

            <div className="mt-8 flex flex-col items-center justify-end gap-4 sm:flex-row">
                <div className="flex items-center gap-2">
                    {data.markdownEnabled && data.content && (
                        <Button
                            variant="secondary"
                            onClick={handleCopyMarkdown}
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
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            {copyMdText}
                        </Button>
                    )}
                    {data.content && (
                        <Button onClick={handleCopyContent}>
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
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                />
                            </svg>
                            {copyText}
                        </Button>
                    )}
                </div>
            </div>
        </>
    );
}

function ErrorView({
    errorDetail,
    showRetry,
    retryKeyInput,
    onRetryKeyChange,
    onRetry,
    retryInputRef,
}: {
    errorDetail: string;
    showRetry: boolean;
    retryKeyInput: string;
    onRetryKeyChange: (value: string) => void;
    onRetry: () => void;
    retryInputRef: React.RefObject<HTMLInputElement | null>;
}) {
    return (
        <>
            <div className="mb-8 text-center">
                <h1 className="mb-3 text-3xl font-semibold tracking-tight">
                    Decryption Failed
                </h1>
                <p className="mx-auto mb-2 max-w-md text-[var(--color-text)]/60">
                    The secret could not be decrypted. This usually means the
                    encryption key in the URL is missing or corrupted.
                </p>
                <p className="text-sm text-[var(--color-text)]/40">
                    Make sure you copied the complete URL including the{' '}
                    <code className="rounded bg-[var(--color-surface-light)] px-1.5 py-0.5 text-[var(--color-primary-400)]">
                        #key
                    </code>{' '}
                    at the end.
                </p>
            </div>

            <GlowCard
                from="red-600"
                via="rose-600"
                to="red-600"
                opacity="opacity-30"
                className="mb-6"
            >
                <div className="flex items-start gap-4">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-500/20">
                        <svg
                            className="h-5 w-5 text-red-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                            />
                        </svg>
                    </div>
                    <div>
                        <h3 className="mb-1 font-medium text-[var(--color-text)]">
                            Missing Encryption Key
                        </h3>
                        <p className="text-sm text-[var(--color-text)]/60">
                            {errorDetail}
                        </p>
                    </div>
                </div>
            </GlowCard>

            {showRetry && (
                <>
                    <GlowCard opacity="opacity-30" className="mb-6">
                        <div className="mb-4 flex items-center gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[var(--color-button)]/20">
                                <svg
                                    className="h-5 w-5 text-[var(--color-button)]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h3 className="font-medium text-[var(--color-text)]">
                                    Enter Encryption Key
                                </h3>
                                <p className="text-sm text-[var(--color-text)]/60">
                                    Paste the correct key or full link to try
                                    again
                                </p>
                            </div>
                        </div>
                        <Input
                            ref={retryInputRef}
                            value={retryKeyInput}
                            onChange={(event) =>
                                onRetryKeyChange(event.target.value)
                            }
                            placeholder="Enter key (e.g., Ab3xK9mZ) or paste full link"
                            className="font-mono"
                            onKeyDown={(event) =>
                                event.key === 'Enter' && onRetry()
                            }
                        />
                    </GlowCard>

                    <div className="flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <Button onClick={onRetry}>
                            <svg
                                className="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                />
                            </svg>
                            Retry Decryption
                        </Button>
                    </div>
                </>
            )}
        </>
    );
}
