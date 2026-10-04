<?php

namespace App\Jobs\Menu;

use App\Services\Menu\MenuFiles;
use App\Services\Menu\PdfRenderer;
use Illuminate\Support\Facades\Bus;

class InspectMenuPdf extends MenuJob
{
    public function handle(PdfRenderer $renderer): void
    {
        $info = $renderer->info((new MenuFiles($this->menu))->source());

        $this->menu->update([
            'pages_count' => $info->pages,
            'sheet_w_pt' => round($info->widthPt, 2),
            'sheet_h_pt' => round($info->heightPt, 2),
            'progress_step' => 'pages',
            'progress_done' => 0,
            'progress_total' => $info->pages,
        ]);
        for ($n = 1; $n <= $info->pages; $n++) {
            $this->menu->pages()->firstOrCreate(['number' => $n], ['title' => 'Страница '.$n]);
        }
        $this->menu->pages()->where('number', '>', $info->pages)->delete();

        // Каждая страница — отдельная задача: на хостинге очередь запускается cron'ом на 50 секунд,
        // и восемь страниц A3 подряд в одну задачу могут не уложиться
        $jobs = [];
        for ($n = 1; $n <= $info->pages; $n++) {
            $jobs[] = new RenderMenuPage($this->menu, $n);
        }
        Bus::chain([...$jobs, new BuildMenuWebPdf($this->menu), new CarryOverMenuMarkup($this->menu), new FinishMenu($this->menu)])
            ->dispatch();
    }
}
