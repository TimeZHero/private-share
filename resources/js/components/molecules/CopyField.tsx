import { useState, useCallback } from 'react';
import { copyToClipboard } from '@/lib/clipboard';
import { Button } from '@/components/atoms/Button';

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
                className="w-full px-4 py-3 pr-24 bg-[var(--color-surface-light)]/80 border border-white/10 rounded-xl text-[var(--color-text)] text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-500)]/50"
            />
            <Button
                size="sm"
                onClick={handleCopy}
                className="absolute right-2 top-1/2 -translate-y-1/2"
            >
                {copied ? 'Copied!' : 'Copy'}
            </Button>
        </div>
    );
}
