<?php

namespace App\Http\Middleware;

use App\Enums\GuestLinkDuration;
use App\Features\Authentication;
use App\Features\FileUploads;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Laravel\Pennant\Feature;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();
        // Error pages reuse these props, and unmatched routes never start a session.
        $session = $request->hasSession() ? $request->session() : null;
        $guestExpiresAt = $session?->get('guest_link_expires_at');

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ] : null,
                'guest' => $guestExpiresAt ? [
                    'expiresAt' => $guestExpiresAt,
                ] : null,
            ],

            'branding' => [
                'primary' => config('branding.primary'),
                'secondary' => config('branding.secondary'),
                'accent' => config('branding.accent'),
                'action' => config('branding.action'),
                'background' => config('branding.background'),
                'foreground' => config('branding.foreground'),
                'logo' => config('branding.logo'),
            ],

            'features' => [
                'auth' => Feature::active(Authentication::class),
                'fileUploads' => Feature::active(FileUploads::class),
            ],

            'guestLinkDurations' => array_column(GuestLinkDuration::cases(), 'value'),

            'appName' => config('app.name'),

            'helpText' => config('support.help_text'),

            'debug' => app()->isLocal() && config('app.debug'),

            'flash' => [
                'error' => fn () => $session?->get('error'),
                'success' => fn () => $session?->get('success'),
            ],
        ];
    }
}
