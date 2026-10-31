<?php

namespace App\Support;

use App\Models\PendingUpload;
use Illuminate\Http\Request;

/**
 * Who is uploading: a signed-in user or a guest session. Routes that use it
 * run behind EnsureAuthenticated, so exactly one of the two is set.
 */
final readonly class Uploader
{
    private function __construct(
        private ?int $userId,
        private ?string $guestLinkId,
    ) {}

    public static function from(Request $request): self
    {
        $userId = $request->user()?->getAuthIdentifier();

        if ($userId !== null) {
            return new self((int) $userId, null);
        }

        return new self(null, $request->hasSession() ? $request->session()->get('guest_link_id') : null);
    }

    public function owns(PendingUpload $pendingUpload): bool
    {
        if ($this->userId !== null) {
            return $pendingUpload->user_id === $this->userId;
        }

        return $this->guestLinkId !== null && $pendingUpload->guest_link_id === $this->guestLinkId;
    }

    /**
     * @return array{user_id: ?int, guest_link_id: ?string}
     */
    public function attributes(): array
    {
        return ['user_id' => $this->userId, 'guest_link_id' => $this->guestLinkId];
    }
}
