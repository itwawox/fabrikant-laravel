<?php

namespace App\Jobs\Menu;

use App\Models\Menu;
use App\Services\Menu\MenuFiles;
use App\Services\Menu\MenuPageBuilder;
use App\Services\Menu\PdfInfo;

class RenderMenuPage extends MenuJob
{
    public function __construct(Menu $menu, public int $number)
    {
        parent::__construct($menu);
    }

    public function handle(MenuPageBuilder $builder): void
    {
        $files = new MenuFiles($this->menu);

        // Повтор после ошибки: готовые страницы не рисуем заново
        if (! $files->pageReady($this->number) || ! is_file($files->webPdfPage($this->number))) {
            $info = new PdfInfo($this->menu->pages_count, (float) $this->menu->sheet_w_pt, (float) $this->menu->sheet_h_pt);
            [$width, $height] = $builder->build($files, $info, $this->number);
            $this->menu->pages()->where('number', $this->number)->update(['width' => $width, 'height' => $height]);
        }

        $this->menu->update(['progress_done' => $this->number]);
    }
}
