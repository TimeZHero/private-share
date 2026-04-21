import { ErrorAlert } from '@/components/atoms/ErrorAlert';
import { Logo } from '@/components/atoms/Logo';
import { AppLayout } from '@/components/layouts/AppLayout';
import { GlowCard } from '@/components/molecules/GlowCard';
import type { LoginPageProps } from '@/types';

export default function Login({
    error,
    debugLoginUrl,
    googleRedirectUrl,
}: LoginPageProps) {
    return (
        <AppLayout title="Sign In" maxWidth="max-w-sm" showTrustBadges={false}>
            <div className="mb-8 text-center">
                <Logo linked={false} />
                <h1 className="mb-1 text-3xl font-semibold tracking-tight">
                    Sign in
                </h1>
                <p className="text-sm text-[var(--color-text)]/60">
                    Sign in to continue
                </p>
            </div>

            {error && <ErrorAlert message={error} className="mb-4" />}

            <GlowCard>
                <a
                    href={googleRedirectUrl}
                    className="inline-flex w-full items-center justify-center gap-3 rounded-xl bg-white px-6 py-3 font-medium text-slate-800 shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:bg-slate-100"
                >
                    <svg className="h-5 w-5" viewBox="0 0 24 24">
                        <path
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"
                            fill="#4285F4"
                        />
                        <path
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                            fill="#34A853"
                        />
                        <path
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                            fill="#FBBC05"
                        />
                        <path
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                            fill="#EA4335"
                        />
                    </svg>
                    <span>Continue with Google</span>
                </a>

                <p className="mt-4 text-center text-xs text-[var(--color-text)]/40">
                    Only authorized accounts can access this app
                </p>

                {debugLoginUrl && (
                    <div className="mt-4 border-t border-white/10 pt-4">
                        <a
                            href={debugLoginUrl}
                            className="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-6 py-2.5 text-sm font-medium text-amber-400 transition-all duration-200 hover:bg-amber-500/20"
                        >
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
                                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
                                />
                            </svg>
                            Debug Login (dev only)
                        </a>
                    </div>
                )}
            </GlowCard>
        </AppLayout>
    );
}
