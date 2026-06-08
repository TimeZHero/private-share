import { apiPostRaw } from '@/lib/api';
import {
    createFileDecryptionContext,
    FILE_CHUNK_SIZE,
    GCM_TAG_LENGTH,
} from '@/lib/crypto';
import type { FileInfo } from '@/types';

class ChunkedStreamReader {
    private reader: ReadableStreamDefaultReader<Uint8Array>;
    private buffer: Uint8Array = new Uint8Array(0);
    private done = false;

    constructor(reader: ReadableStreamDefaultReader<Uint8Array>) {
        this.reader = reader;
    }

    async pull(needed: number): Promise<Uint8Array> {
        while (this.buffer.length < needed && !this.done) {
            const { value, done } = await this.reader.read();
            if (done || !value) {
                this.done = true;
                break;
            }
            const merged = new Uint8Array(this.buffer.length + value.length);
            merged.set(this.buffer);
            merged.set(value, this.buffer.length);
            this.buffer = merged;
        }
        const take = Math.min(needed, this.buffer.length);
        const out = this.buffer.slice(0, take);
        this.buffer = this.buffer.slice(take);
        return out;
    }
}

function triggerBlobDownload(blob: Blob, filename: string): void {
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = filename;
    anchor.rel = 'noopener';
    anchor.dispatchEvent(
        new MouseEvent('click', { bubbles: false, cancelable: false }),
    );
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

interface SignedUrlResponse {
    download_url: string;
    client_encrypted: boolean;
    encryption_salt: string;
    client_iv: string;
    original_mime_type: string;
    plaintext_size: number;
    original_name: string;
}

async function fetchFileResponse(fileId: string): Promise<{
    response: Response;
    isClientEncrypted: boolean;
    encryptionSalt: string | null;
    clientIv: string | null;
    originalMimeType: string;
    plaintextSize: number;
    filename: string;
}> {
    const initialResponse = await apiPostRaw(`/api/files/${fileId}/download`);
    const contentType = initialResponse.headers.get('content-type') ?? '';

    if (contentType.includes('application/json')) {
        const data: SignedUrlResponse = await initialResponse.json();
        const fileResponse = await fetch(data.download_url);
        if (!fileResponse.ok) {
            throw new Error(`Download failed (${fileResponse.status})`);
        }
        return {
            response: fileResponse,
            isClientEncrypted: data.client_encrypted,
            encryptionSalt: data.encryption_salt || null,
            clientIv: data.client_iv || null,
            originalMimeType: data.original_mime_type,
            plaintextSize: data.plaintext_size,
            filename: data.original_name,
        };
    }

    return {
        response: initialResponse,
        isClientEncrypted:
            initialResponse.headers.get('X-Client-Encrypted') === '1',
        encryptionSalt: initialResponse.headers.get('X-Encryption-Salt'),
        clientIv: initialResponse.headers.get('X-Client-Iv'),
        originalMimeType:
            initialResponse.headers.get('X-Original-Mime-Type') ??
            'application/octet-stream',
        plaintextSize: parseInt(
            initialResponse.headers.get('X-Plaintext-Size') ?? '0',
            10,
        ),
        filename:
            initialResponse.headers
                .get('Content-Disposition')
                ?.match(/filename="(.+)"/)?.[1] ?? 'download',
    };
}

export async function downloadAndDecrypt(
    fileId: string,
    encryptionKey: string,
    fileInfo: FileInfo | null,
    onProgress?: (percent: number) => void,
): Promise<void> {
    const {
        response,
        isClientEncrypted,
        encryptionSalt,
        clientIv: clientIvHeader,
        originalMimeType,
        plaintextSize: resolvedPlaintextSize,
        filename: resolvedFilename,
    } = await fetchFileResponse(fileId);

    const filename = fileInfo?.original_name ?? resolvedFilename;

    if (!isClientEncrypted || !encryptionSalt || !clientIvHeader) {
        const blob = await response.blob();
        triggerBlobDownload(blob, filename);
        return;
    }

    const context = await createFileDecryptionContext(
        encryptionKey,
        encryptionSalt,
        clientIvHeader,
    );

    const plaintextSize = resolvedPlaintextSize || (fileInfo?.size ?? 0);
    const fullChunks = Math.floor(plaintextSize / FILE_CHUNK_SIZE);
    const remainder = plaintextSize % FILE_CHUNK_SIZE;
    const totalChunks = fullChunks + (remainder > 0 ? 1 : 0);

    const reader = new ChunkedStreamReader(response.body!.getReader());
    const decryptedParts: Uint8Array[] = [];

    for (let idx = 0; idx < totalChunks; idx++) {
        const isLast = idx === totalChunks - 1 && remainder > 0;
        const needed = isLast
            ? remainder + GCM_TAG_LENGTH
            : FILE_CHUNK_SIZE + GCM_TAG_LENGTH;
        const chunk = await reader.pull(needed);
        if (chunk.length === 0) break;

        const plain = await context.processChunk(
            chunk.buffer as ArrayBuffer,
            idx,
        );
        decryptedParts.push(new Uint8Array(plain));

        if (onProgress && totalChunks > 1) {
            onProgress(Math.round(((idx + 1) / totalChunks) * 100));
        }
    }

    const finalBlob = new Blob(decryptedParts as BlobPart[], {
        type: originalMimeType,
    });
    triggerBlobDownload(finalBlob, filename);
}
