import { cn } from '@/lib/utils';
import { forwardRef, useState, type InputHTMLAttributes } from 'react';

interface PasswordInputProps extends Omit<
    InputHTMLAttributes<HTMLInputElement>,
    'type'
> {
    fullWidth?: boolean;
}

export const PasswordInput = forwardRef<HTMLInputElement, PasswordInputProps>(
    ({ className, fullWidth = true, ...props }, ref) => {
        const [visible, setVisible] = useState(false);

        return (
            <div className="relative">
                <input
                    ref={ref}
                    type={visible ? 'text' : 'password'}
                    autoComplete="one-time-code"
                    className={cn(
                        'rounded-lg border border-white/10 bg-[var(--color-surface)]/80 px-3 py-1.5 pr-8 text-sm text-[var(--color-text)] placeholder-[var(--color-text)]/40 focus:border-[var(--color-primary-500)] focus:ring-2 focus:ring-[var(--color-primary-500)]/50 focus:outline-none',
                        fullWidth ? 'w-full' : 'w-full sm:w-52',
                        className,
                    )}
                    {...props}
                />
                <button
                    type="button"
                    onClick={() => setVisible((prev) => !prev)}
                    className="absolute top-1/2 right-2 -translate-y-1/2 text-[var(--color-text)]/40 transition-colors hover:text-[var(--color-text)]/80"
                >
                    {visible ? (
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
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                            />
                        </svg>
                    ) : (
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
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                            />
                        </svg>
                    )}
                </button>
            </div>
        );
    },
);

PasswordInput.displayName = 'PasswordInput';
