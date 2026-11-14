import { Button } from '@/components/atoms/Button';
import { copyToClipboard } from '@/lib/clipboard';
import { cn } from '@/lib/utils';
import { useCallback, useState } from 'react';

interface CopyFieldProps {
    value: string;
    className?: string;
}

const fieldClasses =
    'w-full rounded-xl border border-white/10 bg-[var(--color-surface-light)]/80 font-mono text-[var(--color-text)]';

export function CopyField({ value, className }: CopyFieldProps) {
    const [copied, setCopied] = useState(false);

    const handleCopy = useCallback(async () => {
        await copyToClipboard(value);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    }, [value]);

    return (
        <div className={cn('flex flex-col gap-2 sm:relative', className)}>
            {/* A one-line input would cut the key off on phones, so they get the whole link wrapped. */}
            <p
                className={cn(
                    fieldClasses,
                    'px-3 py-2.5 text-left text-xs break-all select-all sm:hidden',
                )}
            >
                {value}
            </p>
            <input
                type="text"
                readOnly
                value={value}
                className={cn(
                    fieldClasses,
                    'hidden px-4 py-3 pr-24 text-sm focus:ring-2 focus:ring-[var(--color-primary-500)]/50 focus:outline-none sm:block',
                )}
            />
            <Button
                size="sm"
                onClick={handleCopy}
                className="w-full sm:absolute sm:top-1/2 sm:right-2 sm:w-auto sm:-translate-y-1/2"
            >
                {copied ? 'Copied!' : 'Copy'}
            </Button>
        </div>
    );
}
