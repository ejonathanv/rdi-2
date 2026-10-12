<?php

namespace App\Services;

use App\Models\Checkpoint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckpointTokenRotator
{
    /**
     * Replaces the QR token of every matching checkpoint, keeping the old one for late offline syncs.
     *
     * @param  Builder<Checkpoint>  $checkpoints
     */
    public function rotate(Builder $checkpoints): int
    {
        return DB::transaction(function () use ($checkpoints): int {
            $rotatedAt = now();
            $count = 0;

            $checkpoints->lockForUpdate()->get()->each(function (Checkpoint $checkpoint) use ($rotatedAt, &$count): void {
                $checkpoint->forceFill([
                    'previous_token' => $checkpoint->token,
                    'token' => (string) Str::uuid(),
                    'token_rotated_at' => $rotatedAt,
                ])->save();

                $count++;
            });

            return $count;
        });
    }
}
