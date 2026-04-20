import { apiPost } from '@/lib/api';
import { copyToClipboard } from '@/lib/clipboard';

interface GuestLinkResponse {
    id: string;
    url: string;
    expires_at: string;
}

export async function createAndCopyGuestLink(): Promise<{ url: string; expiresAt: string }> {
    const data = await apiPost<GuestLinkResponse>('/api/guest-links');
    await copyToClipboard(data.url);
    return { url: data.url, expiresAt: data.expires_at };
}
