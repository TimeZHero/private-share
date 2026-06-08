export interface User {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
}

export interface Branding {
    primary: string;
    secondary: string;
    accent: string;
    action: string | null;
    background: string;
    foreground: string;
    logo: {
        image: string | null;
        show_container: boolean;
        svg: string;
    };
    logoSize: {
        large: string;
        small: string;
    };
}

export interface Features {
    auth: boolean;
    fileUploads: boolean;
}

export type FlashToastType = 'success' | 'info' | 'warning' | 'error';

export interface FlashToast {
    type: FlashToastType;
    message: string;
    description?: string;
}

export interface FlashMessages {
    error: string | null;
    success: string | null;
    toast?: FlashToast | null;
}

export interface GuestSession {
    expiresAt: string;
}

export interface SharedPageProps {
    auth: {
        user: User | null;
        guest: GuestSession | null;
    };
    branding: Branding;
    features: Features;
    appName: string;
    helpText: string | null;
    debug: boolean;
    flash: FlashMessages;
    [key: string]: unknown;
}

export interface HomePageProps extends SharedPageProps {
    fileUploadsEnabled: boolean;
    maxSizeGb: number;
}

export interface SecretPageProps extends SharedPageProps {
    secretId: string;
    createdAt: string;
}

export interface LoginPageProps extends SharedPageProps {
    error: string | null;
    debugLoginUrl: string | null;
    googleRedirectUrl: string;
}

export interface ErrorPageProps extends SharedPageProps {
    status: number;
    message: string;
}

export interface FileInfo {
    original_name: string;
    size: number;
    formatted_size: string;
    mime_type: string;
    client_encrypted: boolean;
    encryption_salt: string | null;
    client_iv: string | null;
}

export interface SecretCheckResponse {
    requires_password: boolean;
    markdown_enabled: boolean;
    has_file: boolean;
    file?: FileInfo;
}

export interface SecretRetrieveResponse {
    content: string | null;
    created_at: string;
    markdown_enabled: boolean;
    has_file: boolean;
    file_id?: string;
}
