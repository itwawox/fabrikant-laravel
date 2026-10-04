<?php

namespace App\Jobs\Menu;

use App\Enums\MenuStatus;
use App\Services\Menu\MenuFiles;

class FinishMenu extends MenuJob
{
    public function handle(): void
    {
        (new MenuFiles($this->menu))->deleteTmp();

        $this->menu->update([
            'status' => MenuStatus::Ready,
            'processed_at' => now(),
            'progress_step' => null,
            'error' => null,
        ]);
    }
}
