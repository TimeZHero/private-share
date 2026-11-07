import { apiPost } from '@/lib/api';
import { copyToClipboard } from '@/lib/clipboard';

interface GuestLinkResponse {
    id: string;
    url: string;
    expires_at: string;
    duration_days: number;
}

export interface CreatedGuestLink {
    url: string;
    expiresAt: string;
    durationDays: number;
    copied: boolean;
}

export async function createAndCopyGuestLink(
    durationDays: number,
): Promise<CreatedGuestLink> {
    const data = await apiPost<GuestLinkResponse>('/api/guest-links', {
        duration_days: durationDays,
    });
    const copied = await copyToClipboard(data.url);

    return {
        url: data.url,
        expiresAt: data.expires_at,
        durationDays: data.duration_days,
        copied,
    };
}

export function formatDurationDays(days: number): string {
    return days === 1 ? '1 day' : `${days} days`;
}

export function formatExpiry(isoDate: string): string {
    return new Date(isoDate).toLocaleString(undefined, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}
