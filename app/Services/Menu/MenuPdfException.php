<?php

namespace App\Services\Menu;

use RuntimeException;

/**
 * Ошибка обработки меню, которую понимает человек: её текст показывается в админке как есть.
 */
class MenuPdfException extends RuntimeException {}
