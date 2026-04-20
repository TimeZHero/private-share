<?php

namespace App\Features;

class Authentication
{
    /**
     * Resolve the feature's initial value.
     *
     * Controlled via the FEATURE_AUTH env variable.
     * When enabled, Google SSO login, guest links, and profile menu are active.
     * Independent of the FileUploads feature flag.
     */
    public function resolve(mixed $scope): bool
    {
        return (bool) config('features.auth', false);
    }
}
