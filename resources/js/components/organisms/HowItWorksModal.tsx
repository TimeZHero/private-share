import { Modal } from '@/components/atoms/Modal';
import type { SharedPageProps } from '@/types';
import { usePage } from '@inertiajs/react';
import type { ReactElement, ReactNode } from 'react';

interface HowItWorksModalProps {
    open: boolean;
    onClose: () => void;
}

interface Fact {
    title: string;
    subtitle: string;
    icon: ReactElement;
}

const iconClass = 'h-5 w-5 shrink-0';

const lockIcon: ReactElement = (
    <svg className={iconClass} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
        />
    </svg>
);

const keyIcon: ReactElement = (
    <svg className={iconClass} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
        />
    </svg>
);

const eyeIcon: ReactElement = (
    <svg className={iconClass} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
        />
    </svg>
);

const clockIcon: ReactElement = (
    <svg className={iconClass} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
        />
    </svg>
);

const facts: Fact[] = [
    {
        title: 'Burn after reading',
        subtitle: 'Deleted forever after a single view',
        icon: eyeIcon,
    },
    {
        title: 'Expires in 30 days',
        subtitle: 'If the secret is never opened',
        icon: clockIcon,
    },
    {
        title: 'End-to-end encrypted',
        subtitle: 'Scrambled on your own device',
        icon: lockIcon,
    },
    {
        title: 'Private to you two',
        subtitle: 'Only you and the recipient can read it',
        icon: keyIcon,
    },
];

const steps: string[] = [
    'You write your secret and click Share.',
    'It is encrypted on your device before anything is sent.',
    'You get a private link to share with the recipient.',
    'They open the link and confirm to reveal the secret.',
    'After it is viewed once, the secret is permanently deleted.',
];

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="space-y-3">
            <h3 className="text-xs font-semibold tracking-wider text-[var(--color-text)]/50 uppercase">
                {title}
            </h3>
            {children}
        </section>
    );
}

export function HowItWorksModal({ open, onClose }: HowItWorksModalProps) {
    const { features, helpText } = usePage<SharedPageProps>().props;

    const options: string[] = [
        'Password protection — add a second layer that the recipient must enter.',
        'Markdown — write formatted notes with live preview.',
    ];

    if (features?.fileUploads) {
        options.push(
            'Encrypted file attachments — files are encrypted on your device and deleted after a single download.',
        );
    }

    return (
        <Modal
            open={open}
            onClose={onClose}
            title="How it works"
            maxWidth="max-w-3xl"
        >
            <div className="space-y-6 text-sm text-[var(--color-text)]/70">
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    {facts.map((fact) => (
                        <div
                            key={fact.title}
                            className="flex items-start gap-3 rounded-xl border border-[var(--color-text)]/10 bg-[var(--color-surface-light)] p-4"
                        >
                            <span className="text-[var(--color-button)]">
                                {fact.icon}
                            </span>
                            <div>
                                <p className="font-semibold text-[var(--color-text)]">
                                    {fact.title}
                                </p>
                                <p className="text-xs leading-relaxed text-[var(--color-text)]/60">
                                    {fact.subtitle}
                                </p>
                            </div>
                        </div>
                    ))}
                </div>

                {features?.auth && (
                    <div className="rounded-xl border border-[var(--color-button)]/25 bg-[var(--color-button)]/8 p-4">
                        <div className="flex items-center gap-2.5">
                            <svg
                                className="h-5 w-5 shrink-0 text-[var(--color-button)]"
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
                            <h3 className="font-semibold text-[var(--color-text)]">
                                Create a guest link
                            </h3>
                        </div>
                        <p className="mt-2 leading-relaxed">
                            Need someone without an account to share a secret
                            with you? Use the{' '}
                            <span className="font-semibold text-[var(--color-text)]">
                                Create Guest Link
                            </span>{' '}
                            button at the top and send them the link. It grants
                            temporary access to create secrets — no sign-up
                            needed, and it expires automatically.
                        </p>
                    </div>
                )}

                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <Section title="Sharing a secret">
                        <ol className="space-y-3">
                            {steps.map((step, index) => (
                                <li key={index} className="flex gap-3">
                                    <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[var(--color-surface-light)] text-xs font-semibold text-[var(--color-text)]/80">
                                        {index + 1}
                                    </span>
                                    <span className="leading-relaxed">
                                        {step}
                                    </span>
                                </li>
                            ))}
                        </ol>
                    </Section>

                    <Section title="Options you can enable">
                        <ul className="space-y-2">
                            {options.map((option) => (
                                <li key={option} className="flex gap-2.5">
                                    <svg
                                        className="mt-0.5 h-4 w-4 shrink-0 text-[var(--color-button)]"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                    <span className="leading-relaxed">
                                        {option}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </Section>
                </div>

                {helpText && (
                    <div className="flex items-center gap-2.5 border-t border-[var(--color-text)]/10 pt-5 text-[var(--color-text)]/60">
                        <svg
                            className="h-5 w-5 shrink-0 text-[var(--color-button)]"
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
                        <p className="leading-relaxed whitespace-pre-line">
                            {helpText}
                        </p>
                    </div>
                )}
            </div>
        </Modal>
    );
}
