<?php

namespace App\Exceptions;

use App\Http\Responses\TooManyAttemptsResponse;
use Exception;
use Illuminate\Http\JsonResponse;

final class UploadQuotaExceeded extends Exception
{
    public function __construct(
        private readonly int $quotaGb,
        public readonly int $retryAfter,
    ) {
        parent::__construct("Daily upload quota of {$quotaGb} GB exceeded.");
    }

    public function render(): JsonResponse
    {
        return TooManyAttemptsResponse::make(
            'upload_quota_exceeded',
            "This upload would go over your daily upload limit of {$this->quotaGb} GB.",
            $this->retryAfter,
        );
    }
}
