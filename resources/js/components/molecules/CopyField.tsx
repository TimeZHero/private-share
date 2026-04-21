import { Button } from '@/components/atoms/Button';
import { copyToClipboard } from '@/lib/clipboard';
import { useCallback, useState } from 'react';

interface CopyFieldProps {
    value: string;
    className?: string;
}

export function CopyField({ value, className }: CopyFieldProps) {
    const [copied, setCopied] = useState(false);

    const handleCopy = useCallback(async () => {
        await copyToClipboard(value);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    }, [value]);

    return (
        <div className={`relative ${className ?? ''}`}>
            <input
                type="text"
                readOnly
                value={value}
                className="w-full rounded-xl border border-white/10 bg-[var(--color-surface-light)]/80 px-4 py-3 pr-24 font-mono text-sm text-[var(--color-text)] focus:ring-2 focus:ring-[var(--color-primary-500)]/50 focus:outline-none"
            />
            <Button
                size="sm"
                onClick={handleCopy}
                className="absolute top-1/2 right-2 -translate-y-1/2"
            >
                {copied ? 'Copied!' : 'Copy'}
            </Button>
        </div>
    );
}
