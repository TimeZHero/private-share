<?php

namespace App\Features;

class FileUploads
{
    /**
     * Resolve the feature's initial value.
     *
     * Controlled via the FEATURE_FILE_UPLOADS env variable.
     * Defaults to disabled; activate per-environment without a deploy.
     */
    public function resolve(mixed $scope): bool
    {
        return (bool) config('features.file_uploads', false);
    }
}
