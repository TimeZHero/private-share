import { apiPost, apiPostFormData } from '@/lib/api';
import { createFileEncryptionContext, FILE_CHUNK_SIZE } from '@/lib/crypto';

interface UploadResult {
    fileId: string;
    encryptionKey: string;
}

export async function uploadFile(
    file: File,
    onProgress?: (percent: number) => void,
): Promise<UploadResult> {
    const context = await createFileEncryptionContext();
    const totalChunks = Math.ceil(file.size / FILE_CHUNK_SIZE);

    const { upload_id: uploadId } = await apiPost<{ upload_id: string }>(
        '/api/files/initiate',
        {
            name: file.name,
            mime_type: file.type || 'application/octet-stream',
            size: file.size,
            total_chunks: totalChunks,
            encryption_salt: context.encryptionSalt,
            client_iv: context.clientIv,
        },
    );

    for (let chunkIdx = 0; chunkIdx < totalChunks; chunkIdx++) {
        const start = chunkIdx * FILE_CHUNK_SIZE;
        const end = Math.min(start + FILE_CHUNK_SIZE, file.size);
        const plainBuffer = await file.slice(start, end).arrayBuffer();
        const encryptedBuffer = await context.processChunk(
            plainBuffer,
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

    return { fileId, encryptionKey: context.encryptionKey };
}
