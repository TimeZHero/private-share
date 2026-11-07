<?php

namespace App\Console\Commands;

use App\Models\GuestLink;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Emergency switch: expiring a link ends its guest's access on the next
 * request, because every guest request is checked against the database.
 */
class RevokeGuestLinksCommand extends Command
{
    protected $signature = 'guest-links:revoke
        {guestLink? : Id of one guest link}
        {--user= : Email (or id) of the user who created the links}
        {--all : Every active guest link}';

    protected $description = 'End guest access immediately by expiring guest links';

    public function handle(): int
    {
        $query = GuestLink::query()->active();

        if ($this->argument('guestLink') !== null) {
            $query->whereKey($this->argument('guestLink'));
        } elseif ($this->option('user') !== null) {
            $creator = $this->findUser($this->option('user'));

            if ($creator === null) {
                $this->error("No user matches {$this->option('user')}.");

                return self::FAILURE;
            }

            $query->whereBelongsTo($creator, 'creator');
        } elseif (! $this->option('all')) {
            $this->error('Pass a guest link id, --user or --all.');

            return self::FAILURE;
        }

        return $this->revoke($query);
    }

    /**
     * @param  Builder<GuestLink>  $query
     */
    private function revoke(Builder $query): int
    {
        $guestLinkIds = $query->pluck('id');

        if ($guestLinkIds->isEmpty()) {
            $this->warn('No active guest link matched.');

            return $this->argument('guestLink') !== null ? self::FAILURE : self::SUCCESS;
        }

        if ($this->option('all') && ! $this->confirm("Revoke all {$guestLinkIds->count()} active guest links?")) {
            return self::FAILURE;
        }

        GuestLink::query()->whereKey($guestLinkIds)->update(['expires_at' => now()]);

        foreach ($guestLinkIds as $guestLinkId) {
            Log::warning('Guest link revoked', ['guest_link_id' => $guestLinkId]);
        }

        $this->info("Revoked {$guestLinkIds->count()} guest ".Str::plural('link', $guestLinkIds->count()).'.');

        return self::SUCCESS;
    }

    private function findUser(string $reference): ?User
    {
        if (ctype_digit($reference)) {
            return User::query()->find((int) $reference);
        }

        return User::query()->whereRaw('LOWER(email) = ?', [Str::lower($reference)])->first();
    }
}
