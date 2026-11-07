import { Button } from '@/components/atoms/Button';
import { Modal } from '@/components/atoms/Modal';
import { CopyField } from '@/components/molecules/CopyField';
import { useGuestLink } from '@/hooks/useGuestLink';
import { cn } from '@/lib/utils';
import { formatDurationDays, formatExpiry } from '@/services/guestLink';
import type { SharedPageProps } from '@/types';
import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';

interface GuestLinkModalProps {
    open: boolean;
    onClose: () => void;
}

export function GuestLinkModal({ open, onClose }: GuestLinkModalProps) {
    const { guestLinkDurations } = usePage<SharedPageProps>().props;
    const { creating, created, lastDurationDays, create, reset } =
        useGuestLink();

    const close = useCallback(() => {
        onClose();
        reset();
    }, [onClose, reset]);

    return (
        <Modal
            open={open}
            onClose={close}
            title="Invite a guest"
            maxWidth="max-w-md"
        >
            {created === null ? (
                <div className="flex flex-col gap-4">
                    <p className="text-sm text-[var(--color-text)]/70">
                        How long should the guest have access?
                    </p>
                    <div className="grid grid-cols-3 gap-3">
                        {guestLinkDurations.map((days) => (
                            <Button
                                key={days}
                                variant="secondary"
                                disabled={creating}
                                onClick={() => void create(days)}
                                className={cn(
                                    'py-4',
                                    days === lastDurationDays &&
                                        'border-[var(--color-button)]/60',
                                )}
                            >
                                {formatDurationDays(days)}
                            </Button>
                        ))}
                    </div>
                </div>
            ) : (
                <div className="flex flex-col gap-4">
                    {created.copied && (
                        <p className="text-sm font-semibold text-[var(--color-text)]">
                            Copied to clipboard
                        </p>
                    )}
                    <CopyField value={created.url} />
                    <dl className="flex flex-col gap-1 text-sm text-[var(--color-text)]/70">
                        <div>
                            <dt className="inline">Expires </dt>
                            <dd className="inline font-semibold text-[var(--color-text)]">
                                {formatExpiry(created.expiresAt)}
                            </dd>
                        </div>
                        <div>Works only once</div>
                    </dl>
                    <Button className="w-full" onClick={close}>
                        Done
                    </Button>
                </div>
            )}
        </Modal>
    );
}
