<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\AbstractUser;

/**
 * Only verified accounts managed by an allowed Google Workspace domain may sign in.
 * Every misconfiguration closes sign-in instead of opening it.
 */
final class GoogleAccountPolicy
{
    private const string DOMAIN_PATTERN = '/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/';

    public function permits(AbstractUser $account): bool
    {
        $allowedDomains = $this->allowedDomains();

        if ($allowedDomains === null) {
            return false;
        }

        $email = Str::lower((string) $account->getEmail());
        $emailDomain = Str::afterLast($email, '@');
        $claims = $account->getRaw();

        $permitted = in_array($emailDomain, $allowedDomains, true)
            && ($claims['email_verified'] ?? false) === true
            // `hd` is only present for Workspace-managed accounts; a personal Google account
            // registered with a company address does not carry it.
            && Str::lower((string) ($claims['hd'] ?? '')) === $emailDomain;

        if (! $permitted) {
            Log::info('Google sign-in rejected', ['email_domain' => $emailDomain, 'hosted_domain' => $claims['hd'] ?? null]);
        }

        return $permitted;
    }

    /**
     * Restricts Google's account picker when there is exactly one domain to choose from.
     */
    public function hostedDomainHint(): ?string
    {
        $allowedDomains = $this->allowedDomains();

        return $allowedDomains !== null && count($allowedDomains) === 1 ? $allowedDomains[0] : null;
    }

    /**
     * @return list<string>|null null when sign-in must stay closed
     */
    private function allowedDomains(): ?array
    {
        if (config('features.legacy_allowed_email_patterns')) {
            Log::error('Google sign-in is closed: AUTH_ALLOWED_EMAILS is no longer supported, move the domains to AUTH_ALLOWED_DOMAINS.');

            return null;
        }

        $domains = config('features.allowed_email_domains', []);
        $invalid = array_values(array_filter($domains, fn (string $domain) => preg_match(self::DOMAIN_PATTERN, $domain) !== 1));

        if ($invalid !== []) {
            Log::error('Google sign-in is closed: AUTH_ALLOWED_DOMAINS contains entries that are not plain domains.', ['invalid' => $invalid]);

            return null;
        }

        if ($domains === []) {
            Log::warning('Google sign-in is closed: AUTH_ALLOWED_DOMAINS is empty.');

            return null;
        }

        return $domains;
    }
}
