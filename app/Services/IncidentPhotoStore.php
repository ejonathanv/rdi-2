<?php

namespace App\Services;

use App\Models\Incident;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

class IncidentPhotoStore
{
    private const int MaxSide = 1920;

    private const int JpegQuality = 75;

    /**
     * @param  list<UploadedFile>  $files
     */
    public function store(Incident $incident, array $files): void
    {
        if ($files === []) {
            return;
        }

        $manager = new ImageManager(new Driver);

        foreach (array_values($files) as $index => $file) {
            $position = $index + 1;
            $path = sprintf(
                'incident-evidence/%d/%02d.jpg',
                $incident->id,
                $position,
            );

            $encoded = $manager
                ->decode($file->getRealPath())
                ->orient()
                ->scaleDown(width: self::MaxSide, height: self::MaxSide)
                ->encode(new JpegEncoder(quality: self::JpegQuality));

            Storage::disk('public')->put($path, (string) $encoded);

            $incident->photos()->create([
                'path' => $path,
                'position' => $position,
            ]);
        }
    }
}
