<?php

namespace App\Enums;

use App\Support\ClientIdentity;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

/**
 * Every route is throttled by one of these (enforced by RateLimitCoverageTest).
 * Limits are per client: the account when signed in, otherwise the network.
 */
enum RateLimit: string
{
    case Pages = 'pages';
    case SignIn = 'sign-in';
    case GuestAccess = 'guest-access';
    case SecretCreation = 'secret-creation';
    case SecretViewing = 'secret-viewing';
    case SecretRetrieval = 'secret-retrieval';
    case FileDownload = 'file-download';
    case UploadSession = 'upload-session';
    case UploadChunk = 'upload-chunk';
    case GuestLinkCreation = 'guest-link-creation';

    public function middleware(): string
    {
        return "throttle:{$this->value}";
    }

    public function limitFor(Request $request): Limit
    {
        return Limit::perMinute($this->requestsPerMinute())->by(ClientIdentity::of($request));
    }

    private function requestsPerMinute(): int
    {
        return match ($this) {
            self::Pages => 120,
            self::SignIn => 10,
            self::GuestAccess => 30,
            self::SecretCreation => 30,
            // A recipient opening a secret costs two hits: the page and the check call.
            self::SecretViewing => 30,
            // Password guessing has its own backoff in SecretPasswordAttempts.
            self::SecretRetrieval => 20,
            self::FileDownload => 30,
            self::UploadSession => 30,
            // 1000 × 8 MB chunks ≈ 1 Gbit/s sustained: never reached by a real upload.
            self::UploadChunk => 1000,
            self::GuestLinkCreation => 10,
        };
    }
}
