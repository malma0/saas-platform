<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('booking:prune-holds')]
#[Description('Удалить истёкшие reservation holds')]
class PruneExpiredHolds extends Command
{
    public function handle(): int
    {
        $deleted = \App\Domain\Booking\Models\ReservationHold::where('expires_at', '<', now())->delete();
        $this->info("Удалено истёкших holds: {$deleted}");

        return self::SUCCESS;
    }
}
