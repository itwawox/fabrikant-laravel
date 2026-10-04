<?php

namespace App\Services\Menu;

/**
 * Лёгкий PDF из картинок JPEG, по одной на страницу (перенос write_image_pdf() из tools/build-menu-web.php).
 * Картинка встраивается как есть (DCTDecode), без перекодирования. Ширина листа — как у PDF типографии,
 * высота — по пропорциям картинки. Внешние программы не нужны.
 */
class WebPdfWriter
{
    /** @param  list<string>  $jpegs */
    public function write(array $jpegs, float $sheetW, string $title, string $dst): void
    {
        $objects = [];
        $kids = [];
        // 1 — каталог, 2 — список страниц, 3 — сведения о файле; дальше по три объекта на страницу
        $id = 4;
        foreach ($jpegs as $file) {
            $size = getimagesize($file);
            $data = file_get_contents($file);
            if (! $size || $data === false) {
                throw new MenuPdfException('Не получилось собрать PDF для скачивания: нет картинки страницы.');
            }
            [$w, $h] = $size;
            $space = ($size['channels'] ?? 3) === 1 ? '/DeviceGray' : '/DeviceRGB';
            $sheetH = round($sheetW * $h / $w, 2);
            $page = $id;
            $content = $id + 1;
            $image = $id + 2;
            $id += 3;
            $kids[] = "{$page} 0 R";
            $draw = sprintf('q %.2F 0 0 %.2F 0 0 cm /Im0 Do Q', $sheetW, $sheetH);
            $objects[$page] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$sheetW} {$sheetH}]"
                ." /Resources << /XObject << /Im0 {$image} 0 R >> >> /Contents {$content} 0 R >>";
            $objects[$content] = '<< /Length '.strlen($draw)." >>\nstream\n{$draw}\nendstream";
            $objects[$image] = "<< /Type /XObject /Subtype /Image /Width {$w} /Height {$h} /ColorSpace {$space}"
                .' /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($data)." >>\nstream\n{$data}\nendstream";
        }
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($kids).' >>';
        // Название в UTF-16 с меткой порядка байтов — так PDF понимает кириллицу
        $objects[3] = '<< /Title <FEFF'.strtoupper(bin2hex(mb_convert_encoding($title, 'UTF-16BE', 'UTF-8'))).'>'
            .' /Producer (fabrikant-laravel) >>';
        ksort($objects);

        $out = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $n => $body) {
            $offsets[$n] = strlen($out);
            $out .= "{$n} 0 obj\n{$body}\nendobj\n";
        }
        $xref = strlen($out);
        $out .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $out .= sprintf("%010d 00000 n \n", $offset);
        }
        $out .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R /Info 3 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        // Пишем рядом и переименовываем: сайт не должен отдать недописанный файл
        if (file_put_contents($dst.'.part', $out) === false || ! rename($dst.'.part', $dst)) {
            throw new MenuPdfException('Не получилось записать PDF для скачивания — возможно, на сервере кончилось место.');
        }
    }
}
