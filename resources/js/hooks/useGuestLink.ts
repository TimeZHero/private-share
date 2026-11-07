import {
    createAndCopyGuestLink,
    type CreatedGuestLink,
} from '@/services/guestLink';
import { useCallback, useState } from 'react';
import { toast } from 'sonner';

const LAST_DURATION_KEY = 'privateshare:guest-link-duration';

interface UseGuestLinkReturn {
    creating: boolean;
    created: CreatedGuestLink | null;
    lastDurationDays: number | null;
    create: (durationDays: number) => Promise<void>;
    reset: () => void;
}

function readLastDuration(): number | null {
    const stored = Number(localStorage.getItem(LAST_DURATION_KEY));

    return Number.isInteger(stored) && stored > 0 ? stored : null;
}

export function useGuestLink(): UseGuestLinkReturn {
    const [creating, setCreating] = useState(false);
    const [created, setCreated] = useState<CreatedGuestLink | null>(null);
    const [lastDurationDays, setLastDurationDays] = useState(readLastDuration);

    const create = useCallback(async (durationDays: number) => {
        setCreating(true);
        try {
            setCreated(await createAndCopyGuestLink(durationDays));
            localStorage.setItem(LAST_DURATION_KEY, String(durationDays));
            setLastDurationDays(durationDays);
        } catch (error) {
            toast.error(
                error instanceof Error
                    ? error.message
                    : 'Failed to create guest link',
            );
        } finally {
            setCreating(false);
        }
    }, []);

    const reset = useCallback(() => setCreated(null), []);

    return { creating, created, lastDurationDays, create, reset };
}
