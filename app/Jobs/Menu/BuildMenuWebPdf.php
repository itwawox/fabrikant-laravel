<?php

namespace App\Jobs\Menu;

use App\Services\Menu\MenuFiles;
use App\Services\Menu\WebPdfWriter;
use Illuminate\Support\Facades\Storage;

class BuildMenuWebPdf extends MenuJob
{
    public function handle(WebPdfWriter $writer): void
    {
        $this->menu->update(['progress_step' => 'web_pdf']);
        $files = new MenuFiles($this->menu);

        $jpegs = array_map($files->webPdfPage(...), range(1, $this->menu->pages_count));
        $title = 'Меню ресторана ФабрикантЪ, '.mb_strtolower($this->menu->season);
        $writer->write($jpegs, (float) $this->menu->sheet_w_pt, $title, Storage::disk('public')->path($files->webPdfRelative()));

        $this->menu->update(['web_pdf_path' => $files->webPdfRelative()]);
    }
}
