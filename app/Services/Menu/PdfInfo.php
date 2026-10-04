<?php

namespace App\Services\Menu;

/**
 * Что нужно знать о PDF до отрисовки: число страниц и размер листа в пунктах (1/72 дюйма).
 */
final readonly class PdfInfo
{
    public function __construct(
        public int $pages,
        public float $widthPt,
        public float $heightPt,
    ) {}
}
