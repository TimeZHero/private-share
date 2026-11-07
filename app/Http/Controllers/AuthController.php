<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GoogleAccountPolicy;
use App\Services\GuestAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function redirect(string $provider, GoogleAccountPolicy $policy): RedirectResponse
    {
        $driver = Socialite::driver($provider);
        $hostedDomain = $policy->hostedDomainHint();

        if ($hostedDomain !== null) {
            $driver->with(['hd' => $hostedDomain]);
        }

        return $driver->redirect();
    }

    public function callback(string $provider, GoogleAccountPolicy $policy): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception) {
            return redirect()->route('login')->with('error', 'Authentication failed. Please try again.');
        }

        $email = $socialUser->getEmail();

        if (! $email) {
            return redirect()->route('login')->with('error', 'Could not retrieve your email address.');
        }

        if (! $policy->permits($socialUser)) {
            return redirect()->route('login')->with('error', 'Your account is not authorized to access this application.');
        }

        $user = User::query()->updateOrCreate(
            ['provider' => $provider, 'provider_id' => $socialUser->getId()],
            [
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? explode('@', $email)[0],
                'email' => $email,
                'avatar' => $socialUser->getAvatar(),
                'email_verified_at' => now(),
            ],
        );

        Auth::login($user, remember: true);
        session()->regenerate();

        return redirect()->route('home');
    }

    public function logout(Request $request, GuestAccess $guestAccess): JsonResponse|RedirectResponse
    {
        if ($request->user()) {
            Auth::guard('web')->logout();
        }

        if ($guestAccess->isGuest($request)) {
            $guestAccess->end($request, 'signed_out');
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Logged out.']);
        }

        return redirect()->route('home');
    }

    public function debugLogin(): RedirectResponse
    {
        abort_unless(app()->isLocal() && config('app.debug'), 404);

        $user = User::query()->firstOrCreate(
            ['email' => 'dev@localhost'],
            [
                'name' => 'Dev User',
                'provider' => 'debug',
                'provider_id' => 'debug-0',
                'email_verified_at' => now(),
            ],
        );

        Auth::login($user, remember: true);
        session()->regenerate();

        return redirect()->route('home');
    }
}
