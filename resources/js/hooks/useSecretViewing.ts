import { ApiError } from '@/lib/api';
import {
    checkRequirements,
    decrypt,
    extractKeyFromInput,
    getEncryptionKeyFromHash,
    isNotFoundError,
    isPasswordError,
    retrieveSecret,
} from '@/services/secretViewing';
import type { FileInfo, SecretCheckResponse } from '@/types';
import { useCallback, useRef, useState } from 'react';

export type ViewState = 'loading' | 'confirmation' | 'success' | 'error';

interface ViewingData {
    content: string | null;
    createdAt: string | null;
    markdownEnabled: boolean;
    hasFile: boolean;
    fileId: string | null;
    fileInfo: FileInfo | null;
    encryptionKey: string | null;
}

interface UseSecretViewingReturn {
    viewState: ViewState;
    data: ViewingData;
    loadingTitle: string;
    loadingText: string;
    errorDetail: string;
    requiresPassword: boolean;
    passwordError: string | null;
    check: (secretId: string) => Promise<void>;
    confirmAndRetrieve: (secretId: string, password?: string) => Promise<void>;
    retryDecryption: (keyInput: string) => Promise<void>;
    isAlreadyViewed: (secretId: string) => boolean;
    markAsViewed: (secretId: string) => void;
}

export function useSecretViewing(): UseSecretViewingReturn {
    const [viewState, setViewState] = useState<ViewState>('loading');
    const [data, setData] = useState<ViewingData>({
        content: null,
        createdAt: null,
        markdownEnabled: false,
        hasFile: false,
        fileId: null,
        fileInfo: null,
        encryptionKey: null,
    });
    const [loadingTitle, setLoadingTitle] = useState('Loading...');
    const [loadingText, setLoadingText] = useState('Please wait');
    const [errorDetail, setErrorDetail] = useState('');
    const [requiresPassword, setRequiresPassword] = useState(false);
    const [passwordError, setPasswordError] = useState<string | null>(null);

    const encryptedContentRef = useRef<string | null>(null);
    const secretCreatedAtRef = useRef<string | null>(null);
    const savedKeyRef = useRef<string | null>(null);
    const checkDataRef = useRef<SecretCheckResponse | null>(null);
    const secretIdRef = useRef<string | null>(null);
    const retrievePasswordRef = useRef<string | undefined>(undefined);

    const showLoading = useCallback((title: string, text: string) => {
        setLoadingTitle(title);
        setLoadingText(text);
        setViewState('loading');
    }, []);

    const showError = useCallback((detail: string) => {
        setErrorDetail(detail);
        setViewState('error');
    }, []);

    const showSuccess = useCallback(
        (
            content: string | null,
            createdAt: string | null,
            markdownEnabled: boolean,
            hasFile: boolean,
            fileId: string | null,
            fileInfo: FileInfo | null,
            encryptionKey: string | null,
        ) => {
            setData({
                content,
                createdAt,
                markdownEnabled,
                hasFile,
                fileId,
                fileInfo,
                encryptionKey,
            });
            setViewState('success');
        },
        [],
    );

    const check = useCallback(
        async (secretId: string) => {
            secretIdRef.current = secretId;
            showLoading('Checking...', 'Verifying secret requirements');
            try {
                const result = await checkRequirements(secretId);
                checkDataRef.current = result;

                if (result.requires_confirmation || result.requires_password) {
                    setRequiresPassword(result.requires_password);
                    setPasswordError(null);
                    setData((prev) => ({
                        ...prev,
                        markdownEnabled: result.markdown_enabled,
                        hasFile: result.has_file,
                        fileInfo: result.file ?? null,
                    }));
                    setViewState('confirmation');
                } else {
                    const keyResult = getEncryptionKeyFromHash();
                    if (!keyResult.valid) {
                        showError(keyResult.error);
                        return;
                    }
                    savedKeyRef.current = keyResult.key;
                    await doRetrieveAndDecrypt(secretId);
                }
            } catch (error) {
                if (isNotFoundError(error)) {
                    showError(
                        'This secret has already been viewed or does not exist.',
                    );
                } else {
                    showError('Failed to load secret. Please try again.');
                }
            }
        },
        [showLoading, showError],
    );

    const doRetrieveAndDecrypt = useCallback(
        async (secretId: string, password?: string) => {
            retrievePasswordRef.current = password;
            showLoading('Retrieving...', 'Fetching your secret');
            try {
                const result = await retrieveSecret(secretId, password);
                encryptedContentRef.current = result.content;
                secretCreatedAtRef.current = result.created_at;

                if (!savedKeyRef.current) {
                    const keyResult = getEncryptionKeyFromHash();
                    if (!keyResult.valid) {
                        showError(keyResult.error);
                        return;
                    }
                    savedKeyRef.current = keyResult.key;
                }

                if (result.content) {
                    showLoading(
                        'Decrypting...',
                        'Please wait while we decrypt your secret',
                    );
                    try {
                        const decrypted = await decrypt(
                            result.content,
                            savedKeyRef.current!,
                        );
                        showSuccess(
                            decrypted,
                            result.created_at,
                            result.markdown_enabled,
                            result.has_file,
                            result.file_id ?? null,
                            checkDataRef.current?.file ?? null,
                            savedKeyRef.current,
                        );
                    } catch {
                        showError(
                            'Decryption failed. The encryption key is incorrect. Enter the correct key below and try again.',
                        );
                    }
                } else {
                    showSuccess(
                        null,
                        result.created_at,
                        result.markdown_enabled,
                        result.has_file,
                        result.file_id ?? null,
                        checkDataRef.current?.file ?? null,
                        savedKeyRef.current,
                    );
                }
            } catch (error) {
                if (isNotFoundError(error)) {
                    showError(
                        'This secret has already been viewed or does not exist.',
                    );
                } else if (isPasswordError(error)) {
                    setPasswordError(
                        ((error as ApiError).data?.message as string) ??
                            'Incorrect password',
                    );
                    setViewState('confirmation');
                } else {
                    showError(
                        error instanceof Error
                            ? error.message
                            : 'Failed to retrieve secret. Please try again.',
                    );
                }
            }
        },
        [showLoading, showError, showSuccess],
    );

    const confirmAndRetrieve = useCallback(
        async (secretId: string, password?: string) => {
            setPasswordError(null);
            if (requiresPassword && !password) {
                setPasswordError('Please enter the password');
                return;
            }
            await doRetrieveAndDecrypt(secretId, password);
        },
        [requiresPassword, doRetrieveAndDecrypt],
    );

    const retryDecryption = useCallback(
        async (keyInput: string) => {
            if (!keyInput.trim()) {
                showError(
                    'Please enter an encryption key or paste the full link',
                );
                return;
            }
            const keyResult = extractKeyFromInput(keyInput);
            if (!keyResult.valid) {
                showError(keyResult.error);
                return;
            }
            savedKeyRef.current = keyResult.key;

            if (encryptedContentRef.current) {
                showLoading(
                    'Decrypting...',
                    'Please wait while we decrypt your secret',
                );
                try {
                    const content = await decrypt(
                        encryptedContentRef.current,
                        savedKeyRef.current,
                    );
                    showSuccess(
                        content,
                        secretCreatedAtRef.current,
                        checkDataRef.current?.markdown_enabled ?? false,
                        checkDataRef.current?.has_file ?? false,
                        null,
                        checkDataRef.current?.file ?? null,
                        savedKeyRef.current,
                    );
                } catch {
                    showError(
                        'Decryption failed. The encryption key is incorrect. Please check and try again.',
                    );
                }
            } else if (secretIdRef.current) {
                await doRetrieveAndDecrypt(
                    secretIdRef.current,
                    retrievePasswordRef.current,
                );
            } else {
                showError(
                    'Unable to retry. Please reload the page and try again.',
                );
            }
        },
        [showLoading, showError, showSuccess, doRetrieveAndDecrypt],
    );

    const isAlreadyViewed = useCallback((secretId: string): boolean => {
        return (
            (history.state && history.state.secretViewed) ||
            sessionStorage.getItem(`viewed:${secretId}`) === '1'
        );
    }, []);

    const markAsViewed = useCallback((secretId: string) => {
        history.replaceState({ ...history.state, secretViewed: true }, '');
        sessionStorage.setItem(`viewed:${secretId}`, '1');
    }, []);

    return {
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
    };
}
