import { useState, useCallback } from 'react';
import { createAndCopyGuestLink } from '@/services/guestLink';

interface UseGuestLinkReturn {
    creating: boolean;
    copied: boolean;
    error: string | null;
    create: () => Promise<void>;
}

export function useGuestLink(): UseGuestLinkReturn {
    const [creating, setCreating] = useState(false);
    const [copied, setCopied] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const create = useCallback(async () => {
        setCreating(true);
        setCopied(false);
        setError(null);
        try {
            await createAndCopyGuestLink();
            setCopied(true);
            setTimeout(() => setCopied(false), 2500);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Failed to create guest link');
            setTimeout(() => setError(null), 2500);
        } finally {
            setCreating(false);
        }
    }, []);

    return { creating, copied, error, create };
}
