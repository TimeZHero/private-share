<?php

namespace App\Http\Middleware;

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
        $guestExpiresAt = $request->session()->get('guest_link_expires_at');

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
                'logoSize' => config('branding.logo_size'),
            ],

            'features' => [
                'auth' => Feature::active(Authentication::class),
                'fileUploads' => Feature::active(FileUploads::class),
            ],

            'appName' => config('app.name'),

            'helpText' => config('support.help_text'),

            'debug' => app()->isLocal() && config('app.debug'),

            'flash' => [
                'error' => fn () => $request->session()->get('error'),
                'success' => fn () => $request->session()->get('success'),
            ],
        ];
    }
}
