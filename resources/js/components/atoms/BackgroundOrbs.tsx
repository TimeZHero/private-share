export function BackgroundOrbs() {
    return (
        <div className="pointer-events-none absolute inset-0 overflow-hidden">
            <div className="absolute top-1/4 -left-20 h-96 w-96 rounded-full bg-[var(--color-primary-600)]/20 blur-3xl" />
            <div className="absolute -right-20 bottom-1/4 h-80 w-80 rounded-full bg-[var(--color-secondary-600)]/20 blur-3xl" />
            <div className="absolute top-1/2 left-1/2 h-[600px] w-[600px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-[var(--color-accent-600)]/10 blur-3xl" />
        </div>
    );
}
