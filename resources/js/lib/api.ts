export class ApiError extends Error {
    constructor(
        message: string,
        public status: number,
        public data?: Record<string, unknown>,
    ) {
        super(message);
        this.name = 'ApiError';
    }
}

function getCsrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta?.getAttribute('content') ?? '';
}

async function handleResponse<T>(response: Response): Promise<T> {
    if (!response.ok) {
        let data: Record<string, unknown> | undefined;
        const contentType = response.headers.get('content-type') ?? '';
        if (contentType.includes('application/json')) {
            data = await response.json();
        }
        const message =
            (data?.message as string) ??
            (data?.error as string) ??
            `Request failed (${response.status})`;
        throw new ApiError(message, response.status, data);
    }
    return response.json();
}

export async function apiGet<T>(url: string): Promise<T> {
    const response = await fetch(url, {
        method: 'GET',
        headers: { Accept: 'application/json' },
    });
    return handleResponse<T>(response);
}

export async function apiPost<T>(
    url: string,
    body?: Record<string, unknown>,
): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: body ? JSON.stringify(body) : undefined,
    });
    return handleResponse<T>(response);
}

export async function apiPostFormData<T>(
    url: string,
    formData: FormData,
): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: formData,
    });
    return handleResponse<T>(response);
}

export async function apiPostRaw(url: string): Promise<Response> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/octet-stream',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
    });
    if (!response.ok) {
        const contentType = response.headers.get('content-type') ?? '';
        if (contentType.includes('application/json')) {
            const data = await response.json();
            throw new ApiError(
                (data.message as string) ?? 'Request failed',
                response.status,
                data,
            );
        }
        throw new ApiError(
            `Request failed (${response.status})`,
            response.status,
        );
    }
    return response;
}
