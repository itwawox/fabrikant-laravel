<?php

namespace App\Services\Menu;

/**
 * Рисует страницы PDF картинками. На хостинге нет poppler, а ImageMagick не читает PDF (policy.xml),
 * поэтому реализаций две: Ghostscript (прод) и poppler (локально). Выбор — config('menu.renderer').
 */
interface PdfRenderer
{
    /** @throws MenuPdfException если PDF битый, защищён паролем или программы нет */
    public function info(string $pdf): PdfInfo;

    /**
     * Страница $page шириной $width px в PNG-файл $out. Высота — по пропорциям листа ($info — из info()).
     *
     * @throws MenuPdfException
     */
    public function renderPng(string $pdf, PdfInfo $info, int $page, int $width, string $out): void;
}
