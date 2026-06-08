import type {
    FileInfo,
    SecretCheckResponse,
    SecretRetrieveResponse,
} from '@/types';

export type ViewPhase = 'loading' | 'confirmation' | 'success' | 'error';

export interface ViewMachineState {
    phase: ViewPhase;
    loadingTitle: string;
    loadingText: string;
    errorDetail: string;
    requiresPassword: boolean;
    passwordError: string | null;
    content: string | null;
    createdAt: string | null;
    markdownEnabled: boolean;
    hasFile: boolean;
    fileId: string | null;
    fileInfo: FileInfo | null;
    encryptionKey: string | null;
}

export interface SecretViewApi {
    checkRequirements: (secretId: string) => Promise<SecretCheckResponse>;
    retrieveSecret: (
        secretId: string,
        password?: string,
    ) => Promise<SecretRetrieveResponse>;
    decryptContent: (encrypted: string, key: string) => Promise<string>;
    getEncryptionKeyFromHash: () =>
        | { valid: true; key: string }
        | { valid: false; error: string };
    extractKeyFromInput: (
        input: string,
    ) => { valid: true; key: string } | { valid: false; error: string };
    isPasswordError: (error: unknown) => boolean;
    isNotFoundError: (error: unknown) => boolean;
}

export const INITIAL_STATE: ViewMachineState = {
    phase: 'loading',
    loadingTitle: 'Loading...',
    loadingText: 'Please wait',
    errorDetail: '',
    requiresPassword: false,
    passwordError: null,
    content: null,
    createdAt: null,
    markdownEnabled: false,
    hasFile: false,
    fileId: null,
    fileInfo: null,
    encryptionKey: null,
};

export class SecretViewMachine {
    private state: ViewMachineState;
    private api: SecretViewApi;
    private onStateChange: (state: ViewMachineState) => void;

    private encryptedContent: string | null = null;
    private savedKey: string | null = null;
    private checkData: SecretCheckResponse | null = null;
    private lastPassword: string | undefined = undefined;
    private secretId: string | null = null;

    constructor(
        api: SecretViewApi,
        onStateChange: (state: ViewMachineState) => void,
    ) {
        this.api = api;
        this.onStateChange = onStateChange;
        this.state = { ...INITIAL_STATE };
    }

    private update(patch: Partial<ViewMachineState>): void {
        this.state = { ...this.state, ...patch };
        this.onStateChange(this.state);
    }

    async check(secretId: string): Promise<void> {
        this.secretId = secretId;
        this.update({
            phase: 'loading',
            loadingTitle: 'Checking...',
            loadingText: 'Verifying secret requirements',
        });

        try {
            const result = await this.api.checkRequirements(secretId);
            this.checkData = result;

            this.update({
                phase: 'confirmation',
                requiresPassword: result.requires_password,
                passwordError: null,
                markdownEnabled: result.markdown_enabled,
                hasFile: result.has_file,
                fileInfo: result.file ?? null,
            });
        } catch (error) {
            if (this.api.isNotFoundError(error)) {
                this.update({
                    phase: 'error',
                    errorDetail:
                        'This secret has already been viewed or does not exist.',
                });
            } else {
                this.update({
                    phase: 'error',
                    errorDetail: 'Failed to load secret. Please try again.',
                });
            }
        }
    }

    async confirmAndRetrieve(
        secretId: string,
        password?: string,
    ): Promise<void> {
        if (this.state.requiresPassword && !password) {
            this.update({ passwordError: 'Please enter the password' });
            return;
        }

        this.update({ passwordError: null });
        await this.retrieveAndDecrypt(secretId, password);
    }

    async retryDecryption(keyInput: string): Promise<void> {
        if (!keyInput.trim()) {
            this.update({
                phase: 'error',
                errorDetail:
                    'Please enter an encryption key or paste the full link',
            });
            return;
        }

        const keyResult = this.api.extractKeyFromInput(keyInput);
        if (!keyResult.valid) {
            this.update({ phase: 'error', errorDetail: keyResult.error });
            return;
        }

        this.savedKey = keyResult.key;

        if (this.encryptedContent) {
            this.update({
                phase: 'loading',
                loadingTitle: 'Decrypting...',
                loadingText: 'Please wait while we decrypt your secret',
            });

            try {
                const content = await this.api.decryptContent(
                    this.encryptedContent,
                    this.savedKey,
                );
                this.update({
                    phase: 'success',
                    content,
                    createdAt: this.state.createdAt,
                    markdownEnabled: this.checkData?.markdown_enabled ?? false,
                    hasFile: this.checkData?.has_file ?? false,
                    fileInfo: this.checkData?.file ?? null,
                    encryptionKey: this.savedKey,
                });
            } catch {
                this.update({
                    phase: 'error',
                    errorDetail:
                        'Decryption failed. The encryption key is incorrect. Please check and try again.',
                });
            }
        } else if (this.secretId) {
            await this.retrieveAndDecrypt(this.secretId, this.lastPassword);
        } else {
            this.update({
                phase: 'error',
                errorDetail:
                    'Unable to retry. Please reload the page and try again.',
            });
        }
    }

    private async retrieveAndDecrypt(
        secretId: string,
        password?: string,
    ): Promise<void> {
        this.lastPassword = password;
        this.update({
            phase: 'loading',
            loadingTitle: 'Retrieving...',
            loadingText: 'Fetching your secret',
        });

        try {
            const result = await this.api.retrieveSecret(secretId, password);
            this.encryptedContent = result.content;

            if (!this.savedKey) {
                const keyResult = this.api.getEncryptionKeyFromHash();
                if (!keyResult.valid) {
                    this.update({
                        phase: 'error',
                        errorDetail: keyResult.error,
                    });
                    return;
                }
                this.savedKey = keyResult.key;
            }

            if (result.content) {
                this.update({
                    loadingTitle: 'Decrypting...',
                    loadingText: 'Please wait while we decrypt your secret',
                });

                try {
                    const decrypted = await this.api.decryptContent(
                        result.content,
                        this.savedKey!,
                    );
                    this.update({
                        phase: 'success',
                        content: decrypted,
                        createdAt: result.created_at,
                        markdownEnabled: result.markdown_enabled,
                        hasFile: result.has_file,
                        fileId: result.file_id ?? null,
                        fileInfo: this.checkData?.file ?? null,
                        encryptionKey: this.savedKey,
                    });
                } catch {
                    this.update({
                        phase: 'error',
                        errorDetail:
                            'Decryption failed. The encryption key is incorrect. Enter the correct key below and try again.',
                    });
                }
            } else {
                this.update({
                    phase: 'success',
                    content: null,
                    createdAt: result.created_at,
                    markdownEnabled: result.markdown_enabled,
                    hasFile: result.has_file,
                    fileId: result.file_id ?? null,
                    fileInfo: this.checkData?.file ?? null,
                    encryptionKey: this.savedKey,
                });
            }
        } catch (error) {
            if (this.api.isNotFoundError(error)) {
                this.update({
                    phase: 'error',
                    errorDetail:
                        'This secret has already been viewed or does not exist.',
                });
            } else if (this.api.isPasswordError(error)) {
                const apiError = error as { data?: { message?: string } };
                this.update({
                    phase: 'confirmation',
                    passwordError:
                        (apiError.data?.message as string) ??
                        'Incorrect password',
                });
            } else {
                this.update({
                    phase: 'error',
                    errorDetail:
                        error instanceof Error
                            ? error.message
                            : 'Failed to retrieve secret. Please try again.',
                });
            }
        }
    }
}
