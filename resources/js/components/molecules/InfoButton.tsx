import { HowItWorksModal } from '@/components/organisms/HowItWorksModal';
import { useEffect, useState } from 'react';

const STORAGE_KEY = 'privateshare:how-it-works-seen';

export function InfoButton() {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        if (!localStorage.getItem(STORAGE_KEY)) {
            setOpen(true);
            localStorage.setItem(STORAGE_KEY, '1');
        }
    }, []);

    return (
        <>
            <button
                type="button"
                onClick={() => setOpen(true)}
                aria-label="How it works"
                title="How it works"
                className="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full text-[var(--color-text)]/50 transition-colors hover:bg-white/5 hover:text-[var(--color-text)] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/30"
            >
                <svg
                    className="h-6 w-6"
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
            </button>

            <HowItWorksModal open={open} onClose={() => setOpen(false)} />
        </>
    );
}
