import { Button } from '@/components/atoms/Button';
import { ErrorAlert } from '@/components/atoms/ErrorAlert';
import { Textarea } from '@/components/atoms/Textarea';
import { Toggle } from '@/components/atoms/Toggle';
import { CopyField } from '@/components/molecules/CopyField';
import { GlowCard } from '@/components/molecules/GlowCard';
import { PasswordInput } from '@/components/molecules/PasswordInput';
import { FileUploader } from '@/components/organisms/FileUploader';
import { MarkdownToolbar } from '@/components/organisms/MarkdownToolbar';
import { useSecretSharing } from '@/hooks/useSecretSharing';
import type { MarkdownAction } from '@/services/markdown';
import { computeMarkdownInsertion, renderMarkdown } from '@/services/markdown';
import { useCallback, useEffect, useRef, useState } from 'react';

interface SecretEditorProps {
    fileUploadsEnabled: boolean;
    maxSizeGb: number;
}

export function SecretEditor({
    fileUploadsEnabled,
    maxSizeGb,
}: SecretEditorProps) {
    const [content, setContent] = useState('');
    const [markdownEnabled, setMarkdownEnabled] = useState(false);
    const [enablePassword, setEnablePassword] = useState(false);
    const [password, setPassword] = useState('');
    const [uploadedFileId, setUploadedFileId] = useState<string | null>(null);
    const [fileEncryptionKey, setFileEncryptionKey] = useState<string | null>(
        null,
    );
    const [fileUploadPending, setFileUploadPending] = useState(false);

    const plainTextareaRef = useRef<HTMLTextAreaElement>(null);
    const markdownTextareaRef = useRef<HTMLTextAreaElement>(null);
    const passwordRef = useRef<HTMLInputElement>(null);

    const {
        state,
        result,
        error,
        clearError,
        share,
        reset: resetSharing,
    } = useSecretSharing();

    const handleMarkdownToggle = useCallback(() => {
        setMarkdownEnabled((prev) => !prev);
    }, []);

    const handlePasswordToggle = useCallback(() => {
        setEnablePassword((prev) => {
            if (!prev) {
                requestAnimationFrame(() => passwordRef.current?.focus());
            } else {
                setPassword('');
            }
            return !prev;
        });
    }, []);

    const handleShare = useCallback(async () => {
        await share({
            content,
            uploadedFileId,
            fileEncryptionKey,
            password,
            enablePassword,
            markdownEnabled,
            fileUploadPending,
        });
    }, [
        content,
        uploadedFileId,
        fileEncryptionKey,
        password,
        enablePassword,
        markdownEnabled,
        fileUploadPending,
        share,
    ]);

    const handleReset = useCallback(() => {
        setContent('');
        setMarkdownEnabled(false);
        setEnablePassword(false);
        setPassword('');
        setUploadedFileId(null);
        setFileEncryptionKey(null);
        setFileUploadPending(false);
        resetSharing();
    }, [resetSharing]);

    const handleFileUploaded = useCallback((fileId: string, key: string) => {
        setUploadedFileId(fileId);
        setFileEncryptionKey(key);
        setFileUploadPending(false);
    }, []);

    const handleFileAdded = useCallback(() => {
        setFileUploadPending(true);
        setEnablePassword(true);
    }, []);

    const handleFileRemoved = useCallback(() => {
        setUploadedFileId(null);
        setFileEncryptionKey(null);
        setFileUploadPending(false);
    }, []);

    const handleToolbarContentChange = useCallback((newText: string) => {
        setContent(newText);
    }, []);

    useEffect(() => {
        function handleKeyDown(event: KeyboardEvent) {
            if (!markdownEnabled) return;
            const textarea = markdownTextareaRef.current;
            if (document.activeElement !== textarea || !textarea) return;
            if (event.ctrlKey || event.metaKey) {
                const shortcutMap: Record<string, MarkdownAction> = {
                    b: 'bold',
                    i: 'italic',
                    k: 'link',
                    h: 'heading',
                };
                const action = shortcutMap[event.key.toLowerCase()];
                if (action) {
                    event.preventDefault();
                    const { newText, selectRange, cursorPosition } =
                        computeMarkdownInsertion(
                            textarea.value,
                            textarea.selectionStart,
                            textarea.selectionEnd,
                            action,
                        );
                    setContent(newText);
                    requestAnimationFrame(() => {
                        if (selectRange)
                            textarea.setSelectionRange(
                                selectRange[0],
                                selectRange[1],
                            );
                        else if (cursorPosition !== null)
                            textarea.setSelectionRange(
                                cursorPosition,
                                cursorPosition,
                            );
                    });
                }
            }
        }
        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [markdownEnabled]);

    if (state === 'done' && result) {
        const successTitle =
            result.hasFile && result.hasText
                ? 'Secret Shared!'
                : result.hasFile
                  ? 'File Shared!'
                  : 'Secret Created!';
        const successDesc = result.hasFile
            ? result.hasText
                ? 'Share this link. The secret and attached file will be deleted after viewing.'
                : 'Share this link. The file will be deleted after the first download.'
            : "Share this link. The encryption key is in the URL—don't lose it!";

        return (
            <GlowCard padding="p-8" className="text-center">
                <div className="mb-6 inline-flex h-16 w-16 items-center justify-center rounded-full bg-[var(--color-button)] shadow-[var(--color-button)]/30 shadow-lg">
                    <svg
                        className="h-8 w-8 text-[var(--color-button-contrast)]"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                </div>
                <h2 className="mb-2 text-2xl font-semibold">{successTitle}</h2>
                <p className="mb-2 text-[var(--color-text)]/60">
                    {successDesc}
                </p>
                <p className="mb-4 flex items-center justify-center gap-1.5 text-sm text-[var(--color-text)]/40">
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
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    This secret will auto-expire in 30 days if not viewed
                </p>

                {(result.features.password || result.features.markdown) && (
                    <div className="mb-4 flex flex-wrap items-center justify-center gap-4 text-sm">
                        {result.features.markdown && (
                            <div className="flex items-center gap-2 text-[var(--color-text)]/80">
                                <svg
                                    className="h-4 w-4 text-[var(--color-button)]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                <span>Markdown enabled</span>
                            </div>
                        )}
                        {result.features.password && (
                            <div className="flex items-center gap-2 text-[var(--color-text)]/80">
                                <svg
                                    className="h-4 w-4 text-[var(--color-button)]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                <span>Password protected</span>
                            </div>
                        )}
                    </div>
                )}

                <CopyField value={result.link} className="mb-6" />

                <div className="mb-6 flex items-center justify-center gap-2 text-sm text-amber-400">
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
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                    <span>
                        The encryption key after # is required to decrypt. Save
                        this full URL!
                    </span>
                </div>

                <Button variant="secondary" onClick={handleReset}>
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
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    {result.hasFile ? 'Share Another' : 'Create Another Secret'}
                </Button>
            </GlowCard>
        );
    }

    const previewHtml =
        markdownEnabled && content.trim() ? renderMarkdown(content) : '';

    return (
        <>
            <MarkdownToolbar
                markdownEnabled={markdownEnabled}
                onToggle={handleMarkdownToggle}
                textareaRef={markdownTextareaRef}
                onContentChange={handleToolbarContentChange}
            />

            <div className="group relative mb-4">
                <div className="absolute -inset-1 rounded-2xl bg-gradient-to-r from-[var(--color-primary-600)] via-[var(--color-secondary-600)] to-[var(--color-primary-600)] opacity-40 blur-sm transition-opacity group-focus-within:opacity-60" />

                {!markdownEnabled ? (
                    <div className="relative overflow-hidden rounded-2xl border border-white/10 bg-[var(--color-surface)]/90 p-4 backdrop-blur-sm">
                        <Textarea
                            id="content"
                            ref={plainTextareaRef}
                            value={content}
                            onChange={(event) => setContent(event.target.value)}
                            placeholder="Write your secret here..."
                            rows={10}
                        />
                    </div>
                ) : (
                    <div className="relative grid grid-cols-1 gap-0 overflow-hidden rounded-2xl border border-white/10 bg-[var(--color-surface)]/90 backdrop-blur-sm lg:grid-cols-2">
                        <div className="relative">
                            <div className="absolute top-3 left-4 text-xs font-medium tracking-wider text-[var(--color-text)]/40 uppercase">
                                Editor
                            </div>
                            <Textarea
                                id="content"
                                ref={markdownTextareaRef}
                                value={content}
                                onChange={(event) =>
                                    setContent(event.target.value)
                                }
                                placeholder={
                                    'Write your secret here...\n\n**Bold**, *italic*, `code`\n- Lists\n> Quotes'
                                }
                                rows={10}
                                className="h-full border-b border-white/10 px-4 pt-10 pb-4 lg:border-r lg:border-b-0"
                            />
                        </div>
                        <div className="relative">
                            <div className="absolute top-3 left-4 text-xs font-medium tracking-wider text-[var(--color-text)]/40 uppercase">
                                Preview
                            </div>
                            <div className="prose h-full min-h-[250px] w-full overflow-y-auto px-4 pt-10 pb-4 text-sm">
                                {previewHtml ? (
                                    <div
                                        dangerouslySetInnerHTML={{
                                            __html: previewHtml,
                                        }}
                                    />
                                ) : (
                                    <p className="text-[var(--color-text)]/30 italic">
                                        Preview will appear here...
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {fileUploadsEnabled && (
                <FileUploader
                    maxSizeGb={maxSizeGb}
                    onFileUploaded={handleFileUploaded}
                    onFileRemoved={handleFileRemoved}
                    onFileAdded={handleFileAdded}
                />
            )}

            <div className="sticky bottom-0 z-30 flex flex-col items-stretch justify-between gap-4 rounded-xl border border-white/10 bg-[var(--color-surface-light)]/90 p-3 backdrop-blur-md sm:static sm:flex-row sm:items-center sm:backdrop-blur-sm">
                <div className="flex flex-wrap items-center gap-4 sm:gap-6">
                    <Toggle
                        id="enable-password"
                        label={
                            uploadedFileId
                                ? 'Password (required for files)'
                                : 'Require password'
                        }
                        title="Require a password to view"
                        checked={enablePassword}
                        onChange={handlePasswordToggle}
                        disabled={!!uploadedFileId}
                    />
                    {enablePassword && (
                        <div className="animate-fadeIn w-full sm:w-auto">
                            <PasswordInput
                                ref={passwordRef}
                                value={password}
                                onChange={(event) =>
                                    setPassword(event.target.value)
                                }
                                placeholder="Min 4 chars"
                                fullWidth={false}
                            />
                        </div>
                    )}
                </div>

                <Button
                    onClick={handleShare}
                    disabled={
                        state === 'encrypting' ||
                        state === 'saving' ||
                        fileUploadPending
                    }
                    className="whitespace-nowrap"
                >
                    {fileUploadPending ? (
                        <svg
                            className="h-5 w-5 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                className="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                strokeWidth="4"
                            />
                            <path
                                className="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                            />
                        </svg>
                    ) : (
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
                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"
                            />
                        </svg>
                    )}
                    {fileUploadPending
                        ? 'Uploading...'
                        : state === 'encrypting'
                          ? 'Encrypting...'
                          : state === 'saving'
                            ? 'Saving...'
                            : 'Share Secret'}
                </Button>
            </div>

            {error && (
                <ErrorAlert
                    message={error}
                    dismissible
                    onDismiss={clearError}
                    className="mt-3"
                />
            )}
        </>
    );
}
