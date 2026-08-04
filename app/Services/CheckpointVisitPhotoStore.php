<?php

namespace App\Services;

use App\Models\PatrolCheckpointVisit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

class CheckpointVisitPhotoStore
{
    private const int MaxSide = 1920;

    private const int JpegQuality = 75;

    /**
     * @param  list<UploadedFile>  $files
     */
    public function store(PatrolCheckpointVisit $visit, array $files): void
    {
        if ($files === []) {
            return;
        }

        $manager = new ImageManager(new Driver);

        foreach (array_values($files) as $index => $file) {
            $position = $index + 1;
            $path = sprintf(
                'patrol-evidence/%d/%d/%02d.jpg',
                $visit->patrol_run_id,
                $visit->id,
                $position,
            );

            $encoded = $manager
                ->decode($file->getRealPath())
                ->orient()
                ->scaleDown(width: self::MaxSide, height: self::MaxSide)
                ->encode(new JpegEncoder(quality: self::JpegQuality));

            Storage::disk('public')->put($path, (string) $encoded);

            $visit->photos()->create([
                'path' => $path,
                'position' => $position,
            ]);
        }
    }
}
