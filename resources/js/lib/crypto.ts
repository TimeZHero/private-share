const PBKDF2_ITERATIONS = 100000;
const SALT_LENGTH = 16;
const IV_LENGTH = 12;
const FILE_CHUNK_SIZE = 8 * 1024 * 1024;
const GCM_TAG_LENGTH = 16;
const ENCRYPTED_CHUNK_SIZE = FILE_CHUNK_SIZE + GCM_TAG_LENGTH;

export { ENCRYPTED_CHUNK_SIZE, FILE_CHUNK_SIZE, GCM_TAG_LENGTH };

export function generateKey(): string {
    const chars =
        'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let key = '';
    for (let index = 0; index < 8; index++) {
        key += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return key;
}

export async function deriveKeyWithSalt(
    password: string,
    salt: Uint8Array,
): Promise<CryptoKey> {
    const encoder = new TextEncoder();
    const keyMaterial = await crypto.subtle.importKey(
        'raw',
        encoder.encode(password),
        'PBKDF2',
        false,
        ['deriveKey'],
    );
    return crypto.subtle.deriveKey(
        {
            name: 'PBKDF2',
            salt: new Uint8Array(salt.buffer) as BufferSource,
            iterations: PBKDF2_ITERATIONS,
            hash: 'SHA-256',
        },
        keyMaterial,
        { name: 'AES-GCM', length: 256 },
        false,
        ['encrypt', 'decrypt'],
    );
}

export async function encryptContent(
    content: string,
    password: string,
): Promise<string> {
    const encoder = new TextEncoder();
    const salt = crypto.getRandomValues(new Uint8Array(SALT_LENGTH));
    const key = await deriveKeyWithSalt(password, salt);
    const iv = crypto.getRandomValues(new Uint8Array(IV_LENGTH));
    const encrypted = await crypto.subtle.encrypt(
        { name: 'AES-GCM', iv },
        key,
        encoder.encode(content),
    );
    const combined = new Uint8Array(
        salt.length + iv.length + encrypted.byteLength,
    );
    combined.set(salt);
    combined.set(iv, salt.length);
    combined.set(new Uint8Array(encrypted), salt.length + iv.length);
    return btoa(String.fromCharCode(...combined));
}

export async function decryptContent(
    encryptedBase64: string,
    password: string,
): Promise<string> {
    const decoder = new TextDecoder();
    const combined = Uint8Array.from(atob(encryptedBase64), (char) =>
        char.charCodeAt(0),
    );
    const salt = combined.slice(0, SALT_LENGTH);
    const iv = combined.slice(SALT_LENGTH, SALT_LENGTH + IV_LENGTH);
    const encrypted = combined.slice(SALT_LENGTH + IV_LENGTH);
    const key = await deriveKeyWithSalt(password, salt);
    const decrypted = await crypto.subtle.decrypt(
        { name: 'AES-GCM', iv },
        key,
        encrypted,
    );
    return decoder.decode(decrypted);
}

export function deriveChunkIv(
    baseIv: Uint8Array,
    chunkIndex: number,
): Uint8Array {
    const indexBytes = new Uint8Array(12);
    const view = new DataView(indexBytes.buffer);
    view.setUint32(0, chunkIndex, true);
    const derived = new Uint8Array(12);
    for (let byte = 0; byte < 12; byte++) {
        derived[byte] = baseIv[byte] ^ indexBytes[byte];
    }
    return derived;
}

export async function deriveFileKey(
    keyString: string,
    saltBase64: string,
): Promise<CryptoKey> {
    const salt = Uint8Array.from(atob(saltBase64), (char) =>
        char.charCodeAt(0),
    );
    const encoder = new TextEncoder();
    const keyMaterial = await crypto.subtle.importKey(
        'raw',
        encoder.encode(keyString),
        'PBKDF2',
        false,
        ['deriveKey'],
    );
    return crypto.subtle.deriveKey(
        {
            name: 'PBKDF2',
            salt: new Uint8Array(salt.buffer) as BufferSource,
            iterations: PBKDF2_ITERATIONS,
            hash: 'SHA-256',
        },
        keyMaterial,
        { name: 'AES-GCM', length: 256 },
        false,
        ['encrypt', 'decrypt'],
    );
}

export async function encryptChunk(
    cryptoKey: CryptoKey,
    plaintext: ArrayBuffer,
    baseIv: Uint8Array,
    chunkIndex: number,
): Promise<ArrayBuffer> {
    const iv = deriveChunkIv(baseIv, chunkIndex);
    return crypto.subtle.encrypt(
        { name: 'AES-GCM', iv: iv as BufferSource, tagLength: 128 },
        cryptoKey,
        plaintext,
    );
}

export async function decryptChunk(
    cryptoKey: CryptoKey,
    ciphertext: ArrayBuffer,
    baseIv: Uint8Array,
    chunkIndex: number,
): Promise<ArrayBuffer> {
    const iv = deriveChunkIv(baseIv, chunkIndex);
    return crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: iv as BufferSource, tagLength: 128 },
        cryptoKey,
        ciphertext,
    );
}
