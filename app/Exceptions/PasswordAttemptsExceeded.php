<?php

namespace App\Exceptions;

use App\Http\Responses\TooManyAttemptsResponse;
use Exception;
use Illuminate\Http\JsonResponse;

final class PasswordAttemptsExceeded extends Exception
{
    private function __construct(
        private readonly string $error,
        private readonly string $reason,
        public readonly int $retryAfter,
    ) {
        parent::__construct($reason);
    }

    public static function forClient(int $retryAfter): self
    {
        return new self('too_many_wrong_passwords', 'Too many wrong passwords.', $retryAfter);
    }

    public static function forSecret(int $retryAfter): self
    {
        return new self('secret_locked', 'This secret is temporarily locked after too many wrong passwords.', $retryAfter);
    }

    public function render(): JsonResponse
    {
        return TooManyAttemptsResponse::make($this->error, $this->reason, $this->retryAfter);
    }
}
