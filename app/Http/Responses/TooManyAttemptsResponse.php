<?php

namespace App\Http\Responses;

use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;

final class TooManyAttemptsResponse
{
    /**
     * @param  array<string, string|int>  $headers
     */
    public static function make(string $error, string $reason, int $retryAfter, array $headers = []): JsonResponse
    {
        $wait = CarbonInterval::seconds($retryAfter)
            ->cascade()
            ->forHumans(['parts' => 1, 'options' => CarbonInterface::CEIL]);

        return response()->json([
            'error' => $error,
            'message' => "{$reason} Try again in {$wait}.",
            'retry_after' => $retryAfter,
        ], 429, [...$headers, 'Retry-After' => $retryAfter]);
    }
}
