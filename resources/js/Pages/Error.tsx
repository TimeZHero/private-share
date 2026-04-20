import { ErrorLayout } from '@/components/layouts/ErrorLayout';
import { Button } from '@/components/atoms/Button';
import type { ErrorPageProps } from '@/types';

const statusTitles: Record<number, string> = {
    400: 'Bad Request',
    401: 'Unauthorized',
    403: 'Forbidden',
    404: 'Page not found',
    405: 'Method Not Allowed',
    408: 'Request Timeout',
    419: 'Page Expired',
    422: 'Unprocessable Entity',
    429: 'Too Many Requests',
    500: 'Server Error',
    501: 'Not Implemented',
    502: 'Bad Gateway',
    503: 'Under Maintenance',
    504: 'Gateway Timeout',
};

const statusDescriptions: Record<number, string> = {
    401: "You need to be authenticated to access this resource.",
    403: "You don't have permission to access this resource.",
    404: "Looking for a secret? Secrets are deleted after viewing. Ask the sender for a new link.",
    419: 'Your session has expired. Please refresh the page and try again.',
    429: "You've made too many requests. Please wait a moment before trying again.",
    500: "Something went wrong on our end. Don't worry, your secrets are safe. Please try again in a few moments.",
    503: "We're currently performing scheduled maintenance to improve your experience. We'll be back online shortly. Your secrets are safe.",
};

export default function Error({ status, message }: ErrorPageProps) {
    const title = statusTitles[status] ?? (status >= 500 ? 'Server Error' : 'Client Error');
    const description = statusDescriptions[status]
        ?? (message || 'The request could not be processed. Please check the URL and try again.');

    const showRefreshAction = [419, 429, 500, 501, 502, 503, 504].includes(status);

    const actions = showRefreshAction ? (
        <div className="flex items-center gap-4">
            <Button onClick={() => location.reload()}>
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                {status === 503 ? 'Check Again' : 'Try Again'}
            </Button>
            {status !== 503 && (
                <Button variant="secondary" as="a" href="/">
                    Home
                </Button>
            )}
        </div>
    ) : undefined;

    return <ErrorLayout code={status} title={title} description={description} actions={actions} />;
}
