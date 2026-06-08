import { Button } from '@/components/atoms/Button';
import { ErrorAlert } from '@/components/atoms/ErrorAlert';
import { Textarea } from '@/components/atoms/Textarea';
import { Toggle } from '@/components/atoms/Toggle';
import { CopyField } from '@/components/molecules/CopyField';
import { GlowCard } from '@/components/molecules/GlowCard';
import { PasswordInput } from '@/components/molecules/PasswordInput';
import { MarkdownToolbar } from '@/components/organisms/MarkdownToolbar';
import { useShareFlow } from '@/hooks/useShareFlow';
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
    const [selectedFile, setSelectedFile] = useState<File | null>(null);

    const plainTextareaRef = useRef<HTMLTextAreaElement>(null);
    const markdownTextareaRef = useRef<HTMLTextAreaElement>(null);
    const passwordRef = useRef<HTMLInputElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const {
        state,
        uploadProgress,
        result,
        error,
        clearError,
        share,
        reset: resetSharing,
    } = useShareFlow();

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
            file: selectedFile,
            password,
            enablePassword,
            markdownEnabled,
        });
    }, [
        content,
        selectedFile,
        password,
        enablePassword,
        markdownEnabled,
        share,
    ]);

    const handleReset = useCallback(() => {
        setContent('');
        setMarkdownEnabled(false);
        setEnablePassword(false);
        setPassword('');
        setSelectedFile(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
        resetSharing();
    }, [resetSharing]);

    const handleFileChange = useCallback(
        (event: React.ChangeEvent<HTMLInputElement>) => {
            const file = event.target.files?.[0];
            if (!file) return;

            const maxBytes = maxSizeGb * 1024 * 1024 * 1024;
            if (file.size > maxBytes) {
                alert(`File too large. Maximum size is ${maxSizeGb} GB.`);
                return;
            }

            setSelectedFile(file);
            setEnablePassword(true);
        },
        [maxSizeGb],
    );

    const handleFileRemove = useCallback(() => {
        setSelectedFile(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
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

    const isProcessing =
        state === 'uploading' || state === 'encrypting' || state === 'saving';

    const buttonLabel =
        state === 'uploading'
            ? `Uploading ${uploadProgress}%...`
            : state === 'encrypting'
              ? 'Encrypting...'
              : state === 'saving'
                ? 'Saving...'
                : 'Share Secret';

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
                <FileAttachment
                    file={selectedFile}
                    state={state}
                    uploadProgress={uploadProgress}
                    maxSizeGb={maxSizeGb}
                    fileInputRef={fileInputRef}
                    onFileChange={handleFileChange}
                    onFileRemove={handleFileRemove}
                />
            )}

            <div className="sticky bottom-0 z-30 flex flex-col items-stretch justify-between gap-4 rounded-xl border border-white/10 bg-[var(--color-surface-light)]/90 p-3 backdrop-blur-md sm:static sm:flex-row sm:items-center sm:backdrop-blur-sm">
                <div className="flex flex-wrap items-center gap-4 sm:gap-6">
                    <Toggle
                        id="enable-password"
                        label={
                            selectedFile
                                ? 'Password (required for files)'
                                : 'Require password'
                        }
                        title="Require a password to view"
                        checked={enablePassword}
                        onChange={handlePasswordToggle}
                        disabled={!!selectedFile}
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
                    disabled={isProcessing}
                    className="whitespace-nowrap"
                >
                    {isProcessing ? (
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
                    {buttonLabel}
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

function FileAttachment({
    file,
    state,
    uploadProgress,
    maxSizeGb,
    fileInputRef,
    onFileChange,
    onFileRemove,
}: {
    file: File | null;
    state: string;
    uploadProgress: number;
    maxSizeGb: number;
    fileInputRef: React.RefObject<HTMLInputElement | null>;
    onFileChange: (event: React.ChangeEvent<HTMLInputElement>) => void;
    onFileRemove: () => void;
}) {
    if (!file) {
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
                        ref={fileInputRef}
                        type="file"
                        className="hidden"
                        onChange={onFileChange}
                    />
                </label>
            </div>
        );
    }

    const isUploading = state === 'uploading';
    const isDone = state !== 'uploading' && state !== 'idle';

    const statusText = isUploading
        ? `Encrypting & uploading... ${uploadProgress}%`
        : 'Ready to encrypt & upload';

    const borderColor = isDone ? 'border-emerald-500/40' : 'border-white/10';

    const fillColor = isDone
        ? 'bg-emerald-500/15'
        : 'bg-[var(--color-button)]/15';

    const fillWidth = isUploading
        ? `${uploadProgress}%`
        : isDone
          ? '100%'
          : '0%';

    const iconColor = isDone
        ? 'text-emerald-400'
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
                    {isDone ? (
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M5 13l4 4L19 7"
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
                        {file.name}
                    </p>
                    <p className="text-xs text-[var(--color-text)]/60">
                        {statusText}
                    </p>
                </div>
                {!isUploading && (
                    <button
                        type="button"
                        onClick={onFileRemove}
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
                )}
            </div>
        </div>
    );
}
