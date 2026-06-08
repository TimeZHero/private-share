import { Button } from '@/components/atoms/Button';
import { HowItWorksModal } from '@/components/organisms/HowItWorksModal';
import { useGuestLink } from '@/hooks/useGuestLink';
import type { SharedPageProps } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

export function ProfileMenu() {
    const { auth, features } = usePage<SharedPageProps>().props;
    const user = auth?.user;
    const guest = auth?.guest;
    const [open, setOpen] = useState(false);
    const [infoOpen, setInfoOpen] = useState(false);
    const wrapperRef = useRef<HTMLDivElement>(null);
    const { creating, create } = useGuestLink();

    const openInfo = useCallback(() => {
        setOpen(false);
        setInfoOpen(true);
    }, []);

    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            if (
                wrapperRef.current &&
                !wrapperRef.current.contains(event.target as Node)
            ) {
                setOpen(false);
            }
        }
        document.addEventListener('click', handleClickOutside);
        return () => document.removeEventListener('click', handleClickOutside);
    }, []);

    const handleLogout = useCallback(() => {
        router.post('/auth/logout');
    }, []);

    const isAuthenticated = user || guest;
    if (!isAuthenticated || !features?.auth) return null;

    const displayName = user?.name ?? 'Guest';
    const displayEmail = user?.email ?? null;
    const initial = displayName.charAt(0).toUpperCase();

    const guestLinkLabel = creating ? 'Creating...' : 'Create Guest Link';

    return (
        <div ref={wrapperRef} className="flex items-center gap-3">
            {user && (
                <Button size="sm" onClick={create} disabled={creating}>
                    <svg
                        className="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
                        />
                    </svg>
                    {guestLinkLabel}
                </Button>
            )}

            <div className="relative">
                <button
                    onClick={() => setOpen((prev) => !prev)}
                    aria-label="Account menu"
                    className="cursor-pointer rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-white/30"
                >
                    {user?.avatar ? (
                        <img
                            src={user.avatar}
                            alt={displayName}
                            className="h-10 w-10 rounded-full ring-2 ring-[var(--color-text)]/15 transition-opacity hover:opacity-80"
                            referrerPolicy="no-referrer"
                        />
                    ) : (
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-[var(--color-primary-500)] text-base font-semibold text-[var(--color-primary-contrast)] ring-2 ring-[var(--color-text)]/15">
                            {initial}
                        </div>
                    )}
                </button>

                {open && (
                    <div className="animate-fadeIn absolute top-full right-0 mt-2 w-64 overflow-hidden rounded-lg border border-white/10 bg-[var(--color-surface-light)] shadow-xl shadow-black/40">
                        <div className="flex items-center gap-3 px-4 py-3">
                            {user?.avatar ? (
                                <img
                                    src={user.avatar}
                                    alt=""
                                    className="h-10 w-10 shrink-0 rounded-full"
                                    referrerPolicy="no-referrer"
                                />
                            ) : (
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[var(--color-primary-500)] text-sm font-semibold text-[var(--color-primary-contrast)]">
                                    {initial}
                                </div>
                            )}
                            <div className="min-w-0">
                                <p className="truncate text-sm font-semibold text-[var(--color-text)]">
                                    {displayName}
                                </p>
                                {displayEmail && (
                                    <p className="truncate text-xs text-[var(--color-text)]/60">
                                        {displayEmail}
                                    </p>
                                )}
                                {guest && !user && (
                                    <p className="text-xs text-[var(--color-text)]/40">
                                        Expires{' '}
                                        {new Date(
                                            guest.expiresAt,
                                        ).toLocaleString()}
                                    </p>
                                )}
                            </div>
                        </div>
                        <div className="border-t border-white/10">
                            <button
                                onClick={openInfo}
                                className="flex w-full cursor-pointer items-center gap-3 px-4 py-2.5 text-sm text-[var(--color-text)]/80 transition-colors hover:bg-white/5"
                            >
                                <svg
                                    className="h-4 w-4 text-[var(--color-text)]/60"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                How it works
                            </button>
                            <button
                                onClick={handleLogout}
                                className="flex w-full cursor-pointer items-center gap-3 px-4 py-2.5 text-sm text-[var(--color-text)]/80 transition-colors hover:bg-white/5"
                            >
                                <svg
                                    className="h-4 w-4 text-[var(--color-text)]/60"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                    />
                                </svg>
                                Sign out
                            </button>
                        </div>
                    </div>
                )}
            </div>

            <HowItWorksModal
                open={infoOpen}
                onClose={() => setInfoOpen(false)}
            />
        </div>
    );
}
