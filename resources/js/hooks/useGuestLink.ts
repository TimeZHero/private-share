import { copyToClipboard } from '@/lib/clipboard';
import { createAndCopyGuestLink } from '@/services/guestLink';
import { useCallback, useState } from 'react';
import { toast } from 'sonner';

interface UseGuestLinkReturn {
    creating: boolean;
    create: () => Promise<void>;
}

function formatDuration(hours: number): string {
    return hours === 1 ? '1 hour' : `${hours} hours`;
}

export function useGuestLink(): UseGuestLinkReturn {
    const [creating, setCreating] = useState(false);

    const create = useCallback(async () => {
        setCreating(true);
        try {
            const { url, ttlHours, copied } = await createAndCopyGuestLink();
            const duration = `Lasts ${formatDuration(ttlHours)}`;

            if (copied) {
                toast.success('Guest link copied to clipboard', {
                    description: duration,
                });
            } else {
                toast.warning('Guest link created', {
                    description: `${duration}. Couldn't copy automatically.`,
                    action: {
                        label: 'Copy',
                        onClick: () => void copyToClipboard(url),
                    },
                });
            }
        } catch (err) {
            toast.error(
                err instanceof Error
                    ? err.message
                    : 'Failed to create guest link',
            );
        } finally {
            setCreating(false);
        }
    }, []);

    return { creating, create };
}
