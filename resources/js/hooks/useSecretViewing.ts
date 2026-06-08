import {
    checkRequirements,
    decrypt,
    extractKeyFromInput,
    getEncryptionKeyFromHash,
    isNotFoundError,
    isPasswordError,
    retrieveSecret,
} from '@/services/secretViewing';
import {
    INITIAL_STATE,
    SecretViewMachine,
    type ViewMachineState,
} from '@/services/secretViewMachine';
import { useCallback, useRef, useState } from 'react';

export type ViewState = ViewMachineState['phase'];

function createMachine(
    onStateChange: (state: ViewMachineState) => void,
): SecretViewMachine {
    return new SecretViewMachine(
        {
            checkRequirements,
            retrieveSecret,
            decryptContent: decrypt,
            getEncryptionKeyFromHash,
            extractKeyFromInput,
            isPasswordError,
            isNotFoundError,
        },
        onStateChange,
    );
}

export function useSecretViewing() {
    const [state, setState] = useState<ViewMachineState>({ ...INITIAL_STATE });

    const machineRef = useRef<SecretViewMachine | null>(null);
    if (!machineRef.current) {
        machineRef.current = createMachine(setState);
    }

    const check = useCallback(async (secretId: string) => {
        await machineRef.current!.check(secretId);
    }, []);

    const confirmAndRetrieve = useCallback(
        async (secretId: string, password?: string) => {
            await machineRef.current!.confirmAndRetrieve(secretId, password);
        },
        [],
    );

    const retryDecryption = useCallback(async (keyInput: string) => {
        await machineRef.current!.retryDecryption(keyInput);
    }, []);

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
        viewState: state.phase,
        data: {
            content: state.content,
            createdAt: state.createdAt,
            markdownEnabled: state.markdownEnabled,
            hasFile: state.hasFile,
            fileId: state.fileId,
            fileInfo: state.fileInfo,
            encryptionKey: state.encryptionKey,
        },
        loadingTitle: state.loadingTitle,
        loadingText: state.loadingText,
        errorDetail: state.errorDetail,
        requiresPassword: state.requiresPassword,
        passwordError: state.passwordError,
        check,
        confirmAndRetrieve,
        retryDecryption,
        isAlreadyViewed,
        markAsViewed,
    };
}
