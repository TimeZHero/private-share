import { useState, useRef, useEffect, useCallback } from 'react';
import { usePage, router } from '@inertiajs/react';
import type { SharedPageProps } from '@/types';
import { Button } from '@/components/atoms/Button';
import { useGuestLink } from '@/hooks/useGuestLink';

export function ProfileMenu() {
    const { auth, features } = usePage<SharedPageProps>().props;
    const user = auth?.user;
    const guest = auth?.guest;
    const [open, setOpen] = useState(false);
    const wrapperRef = useRef<HTMLDivElement>(null);
    const { creating, copied, error, create } = useGuestLink();

    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            if (wrapperRef.current && !wrapperRef.current.contains(event.target as Node)) {
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

    const guestLinkLabel = creating ? 'Creating...' : copied ? 'Copied!' : error ?? 'Create Guest Link';

    return (
        <div ref={wrapperRef} className="flex items-center gap-3">
            {user && (
                <Button size="sm" onClick={create} disabled={creating}>
                    {copied ? (
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                    ) : (
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                    )}
                    {guestLinkLabel}
                </Button>
            )}

            <div className="relative">
                <button
                    onClick={() => setOpen((prev) => !prev)}
                    className="cursor-pointer rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-white/30"
                >
                    {user?.avatar ? (
                        <img src={user.avatar} alt={displayName} className="w-10 h-10 rounded-full hover:opacity-80 transition-opacity ring-2 ring-[var(--color-text)]/15" referrerPolicy="no-referrer" />
                    ) : (
                        <div className="w-10 h-10 rounded-full flex items-center justify-center text-base font-semibold bg-[var(--color-primary-500)] text-[var(--color-primary-contrast)] ring-2 ring-[var(--color-text)]/15">
                            {initial}
                        </div>
                    )}
                </button>

                {open && (
                    <div className="absolute right-0 top-full mt-2 w-64 bg-[var(--color-surface-light)] border border-white/10 rounded-lg shadow-xl shadow-black/40 overflow-hidden animate-fadeIn">
                        <div className="flex items-center gap-3 px-4 py-3">
                            {user?.avatar ? (
                                <img src={user.avatar} alt="" className="w-10 h-10 rounded-full shrink-0" referrerPolicy="no-referrer" />
                            ) : (
                                <div className="w-10 h-10 rounded-full shrink-0 flex items-center justify-center text-sm font-semibold bg-[var(--color-primary-500)] text-[var(--color-primary-contrast)]">
                                    {initial}
                                </div>
                            )}
                            <div className="min-w-0">
                                <p className="text-sm font-semibold text-[var(--color-text)] truncate">{displayName}</p>
                                {displayEmail && <p className="text-xs text-[var(--color-text)]/60 truncate">{displayEmail}</p>}
                                {guest && !user && (
                                    <p className="text-xs text-[var(--color-text)]/40">
                                        Expires {new Date(guest.expiresAt).toLocaleString()}
                                    </p>
                                )}
                            </div>
                        </div>
                        <div className="border-t border-white/10">
                            <button
                                onClick={handleLogout}
                                className="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-[var(--color-text)]/80 hover:bg-white/5 transition-colors cursor-pointer"
                            >
                                <svg className="w-4 h-4 text-[var(--color-text)]/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Sign out
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
