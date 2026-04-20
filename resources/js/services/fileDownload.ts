import { deriveFileKey, deriveChunkIv, FILE_CHUNK_SIZE, GCM_TAG_LENGTH } from '@/lib/crypto';
import { apiPostRaw } from '@/lib/api';
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
    anchor.dispatchEvent(new MouseEvent('click', { bubbles: false, cancelable: false }));
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

export async function downloadAndDecrypt(
    fileId: string,
    encryptionKey: string,
    fileInfo: FileInfo | null,
    onProgress?: (percent: number) => void,
): Promise<void> {
    const response = await apiPostRaw(`/api/files/${fileId}/download`);

    const isClientEncrypted = response.headers.get('X-Client-Encrypted') === '1';
    const encryptionSalt = response.headers.get('X-Encryption-Salt');
    const clientIvHeader = response.headers.get('X-Client-Iv');
    const originalMimeType = response.headers.get('X-Original-Mime-Type') ?? 'application/octet-stream';
    const filename = fileInfo?.original_name ?? 'download';

    if (!isClientEncrypted || !encryptionSalt || !clientIvHeader) {
        const blob = await response.blob();
        triggerBlobDownload(blob, filename);
        return;
    }

    const cryptoKey = await deriveFileKey(encryptionKey, encryptionSalt);
    const baseIv = Uint8Array.from(atob(clientIvHeader), (char) => char.charCodeAt(0));

    const plaintextSizeHeader = response.headers.get('X-Plaintext-Size');
    const plaintextSize = plaintextSizeHeader
        ? parseInt(plaintextSizeHeader, 10)
        : fileInfo?.size ?? 0;
    const fullChunks = Math.floor(plaintextSize / FILE_CHUNK_SIZE);
    const remainder = plaintextSize % FILE_CHUNK_SIZE;
    const totalChunks = fullChunks + (remainder > 0 ? 1 : 0);

    const reader = new ChunkedStreamReader(response.body!.getReader());
    const decryptedParts: Uint8Array[] = [];

    for (let idx = 0; idx < totalChunks; idx++) {
        const isLast = idx === totalChunks - 1 && remainder > 0;
        const needed = isLast ? remainder + GCM_TAG_LENGTH : FILE_CHUNK_SIZE + GCM_TAG_LENGTH;
        const chunk = await reader.pull(needed);
        if (chunk.length === 0) break;

        const iv = deriveChunkIv(baseIv, idx);
        const plain = await crypto.subtle.decrypt(
            { name: 'AES-GCM', iv: iv as BufferSource, tagLength: 128 },
            cryptoKey,
            chunk as BufferSource,
        );
        decryptedParts.push(new Uint8Array(plain));

        if (onProgress && totalChunks > 1) {
            onProgress(Math.round(((idx + 1) / totalChunks) * 100));
        }
    }

    const finalBlob = new Blob(decryptedParts as BlobPart[], { type: originalMimeType });
    triggerBlobDownload(finalBlob, filename);
}
