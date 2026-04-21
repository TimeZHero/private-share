import { ApiError, apiGet, apiPost } from '@/lib/api';
import { decryptContent } from '@/lib/crypto';
import type { SecretCheckResponse, SecretRetrieveResponse } from '@/types';

export async function checkRequirements(
    secretId: string,
): Promise<SecretCheckResponse> {
    return apiGet<SecretCheckResponse>(`/api/secrets/${secretId}/check`);
}

export async function retrieveSecret(
    secretId: string,
    password?: string,
): Promise<SecretRetrieveResponse> {
    const payload: Record<string, unknown> = {};
    if (password) payload.password = password;
    return apiPost<SecretRetrieveResponse>(
        `/api/secrets/${secretId}/retrieve`,
        payload,
    );
}

export async function decrypt(
    encryptedBase64: string,
    key: string,
): Promise<string> {
    return decryptContent(encryptedBase64, key);
}

export function getEncryptionKeyFromHash():
    | { valid: true; key: string }
    | { valid: false; error: string } {
    const hash = window.location.hash;
    if (!hash || hash.length < 2) {
        return {
            valid: false,
            error: 'No encryption key found in the URL. The link should end with #xxxxxxxx where xxxxxxxx is the 8-character key.',
        };
    }
    const encryptionKey = hash.substring(1);
    if (encryptionKey.length !== 8) {
        return {
            valid: false,
            error: `Invalid encryption key length. Expected 8 characters, got ${encryptionKey.length}. Make sure you copied the complete URL.`,
        };
    }
    return { valid: true, key: encryptionKey };
}

export function extractKeyFromInput(
    input: string,
): { valid: true; key: string } | { valid: false; error: string } {
    let trimmed = input.trim();
    if (trimmed.includes('#')) {
        const hashIndex = trimmed.indexOf('#');
        trimmed = trimmed.substring(hashIndex + 1);
    }
    if (trimmed.length !== 8) {
        return {
            valid: false,
            error: `Invalid key length. Expected 8 characters, got ${trimmed.length}.`,
        };
    }
    return { valid: true, key: trimmed };
}

export function isPasswordError(error: unknown): boolean {
    return (
        error instanceof ApiError &&
        error.status === 403 &&
        error.data?.error === 'invalid_password'
    );
}

export function isNotFoundError(error: unknown): boolean {
    return error instanceof ApiError && error.status === 404;
}
