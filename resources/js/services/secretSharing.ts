import { apiPost } from '@/lib/api';
import { encryptContent, generateKey } from '@/lib/crypto';

interface ShareSecretParams {
    content: string;
    uploadedFileId: string | null;
    fileEncryptionKey: string | null;
    password: string;
    markdownEnabled: boolean;
}

export interface ShareResult {
    link: string;
    hasFile: boolean;
    hasText: boolean;
    features: {
        password: boolean;
        markdown: boolean;
    };
}

export function validateShareInputs(
    content: string,
    hasFile: boolean,
    password: string,
    enablePassword: boolean,
    fileUploadPending: boolean,
): string | null {
    if (!content.trim() && !hasFile) {
        return 'Please enter some content or attach a file to share.';
    }
    if (fileUploadPending) {
        return 'Please wait for the file to finish uploading.';
    }
    if (hasFile && password.length < 4) {
        return 'A password is required when sharing a file (min 4 characters).';
    }
    if (enablePassword && password.length < 4) {
        return 'Password must be at least 4 characters long.';
    }
    return null;
}

export async function shareSecret(
    params: ShareSecretParams,
): Promise<ShareResult> {
    const {
        content,
        uploadedFileId,
        fileEncryptionKey,
        password,
        markdownEnabled,
    } = params;

    const hasFile = !!uploadedFileId;
    const hasText = content.trim().length > 0;
    const encryptionKey = hasFile
        ? fileEncryptionKey
        : hasText
          ? generateKey()
          : null;

    let encryptedContent: string | null = null;
    if (hasText && encryptionKey) {
        encryptedContent = await encryptContent(content, encryptionKey);
    }

    const payload: Record<string, unknown> = {
        content: encryptedContent,
        markdown_enabled: markdownEnabled,
    };
    if (uploadedFileId) payload.shared_file_id = uploadedFileId;
    if (password) payload.password = password;

    const data = await apiPost<{ id: string }>('/api/secrets', payload);

    const baseUrl = window.location.origin;
    const link = encryptionKey
        ? `${baseUrl}/${data.id}#${encryptionKey}`
        : `${baseUrl}/${data.id}`;

    return {
        link,
        hasFile,
        hasText,
        features: {
            password: !!password,
            markdown: hasText && markdownEnabled,
        },
    };
}
