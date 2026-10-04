<?php

namespace App\Jobs\Gallery;

use App\Models\GalleryPhoto;
use App\Services\Gallery\GalleryImages;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Нарезка копий одного фото галереи. Пока копий нет, фото на сайте не показывается. */
class BuildGalleryPhoto implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public GalleryPhoto $photo) {}

    public function handle(GalleryImages $images): void
    {
        $images->build($this->photo);
    }

    public function failed(?Throwable $e): void
    {
        Log::error('Gallery photo build failed', ['photo' => $this->photo->getKey(), 'exception' => $e]);
    }
}
