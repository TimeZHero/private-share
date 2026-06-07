import { apiPost } from '@/lib/api';
import { copyToClipboard } from '@/lib/clipboard';

interface GuestLinkResponse {
    id: string;
    url: string;
    expires_at: string;
    ttl_hours: number;
}

export async function createAndCopyGuestLink(): Promise<{
    url: string;
    ttlHours: number;
    copied: boolean;
}> {
    const data = await apiPost<GuestLinkResponse>('/api/guest-links');
    const copied = await copyToClipboard(data.url);
    return { url: data.url, ttlHours: data.ttl_hours, copied };
}
