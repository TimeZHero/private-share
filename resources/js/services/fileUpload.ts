import { apiPost, apiPostFormData } from '@/lib/api';
import {
    deriveFileKey,
    encryptChunk,
    FILE_CHUNK_SIZE,
    generateKey,
} from '@/lib/crypto';

interface UploadResult {
    fileId: string;
    encryptionKey: string;
}

export async function uploadFile(
    file: File,
    onProgress?: (percent: number) => void,
): Promise<UploadResult> {
    const encryptionKey = generateKey();
    const totalChunks = Math.ceil(file.size / FILE_CHUNK_SIZE);

    const saltBytes = crypto.getRandomValues(new Uint8Array(16));
    const encryptionSalt = btoa(String.fromCharCode(...saltBytes));
    const baseIv = crypto.getRandomValues(new Uint8Array(12));
    const cryptoKey = await deriveFileKey(encryptionKey, encryptionSalt);
    const clientIv = btoa(String.fromCharCode(...baseIv));

    const { upload_id: uploadId } = await apiPost<{ upload_id: string }>(
        '/api/files/initiate',
        {
            name: file.name,
            mime_type: file.type || 'application/octet-stream',
            size: file.size,
            total_chunks: totalChunks,
            encryption_salt: encryptionSalt,
            client_iv: clientIv,
        },
    );

    for (let chunkIdx = 0; chunkIdx < totalChunks; chunkIdx++) {
        const start = chunkIdx * FILE_CHUNK_SIZE;
        const end = Math.min(start + FILE_CHUNK_SIZE, file.size);
        const plainBlob = file.slice(start, end);
        const plainBuffer = await plainBlob.arrayBuffer();
        const encryptedBuffer = await encryptChunk(
            cryptoKey,
            plainBuffer,
            baseIv,
            chunkIdx,
        );
        const encryptedBlob = new Blob([encryptedBuffer]);

        const chunkForm = new FormData();
        chunkForm.append('chunk_index', String(chunkIdx));
        chunkForm.append('chunk', encryptedBlob, 'chunk');

        await apiPostFormData(`/api/files/${uploadId}/chunk`, chunkForm);

        if (onProgress) {
            onProgress(Math.round(((chunkIdx + 1) / totalChunks) * 100));
        }
    }

    const { shared_file_id: fileId } = await apiPost<{
        shared_file_id: string;
    }>(`/api/files/${uploadId}/complete`);

    return { fileId, encryptionKey };
}
