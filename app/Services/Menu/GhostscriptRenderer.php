<?php

namespace App\Services\Menu;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class GhostscriptRenderer implements PdfRenderer
{
    public function __construct(
        private readonly string $binary = 'gs',
        private readonly int $timeout = 120,
    ) {}

    public function info(string $pdf): PdfInfo
    {
        // PDF приходит из админки, поэтому gs всегда в режиме SAFER. С 9.50 SAFER запрещает и чтение файлов —
        // нужный файл разрешаем явно; на хостинге 9.27, там такого ключа нет и чтение не запрещено.
        $permit = version_compare($this->version(), '9.50', '>=') ? ['--permit-file-read='.$pdf] : [];
        $script = sprintf('(%s) (r) file runpdfbegin pdfpagecount = 1 pdfgetpage /MediaBox pget pop == quit', $this->psString($pdf));
        $result = $this->run([$this->binary, '-q', '-dNODISPLAY', '-dSAFER', ...$permit, '-dBATCH', '-dNOPAUSE', '-c', $script]);

        // Ответ: «8» и «[0.0 0.0 841.889771 1190.55115]»
        if (! preg_match('/^(\d+)\s*\n\s*\[\s*([-\d.]+)\s+([-\d.]+)\s+([-\d.]+)\s+([-\d.]+)\s*\]/m', $result->output(), $m) || (int) $m[1] < 1) {
            throw new MenuPdfException('Не получилось прочитать PDF — он точно не битый? Попробуйте сохранить его заново.');
        }

        return new PdfInfo((int) $m[1], (float) $m[4] - (float) $m[2], (float) $m[5] - (float) $m[3]);
    }

    public function renderPng(string $pdf, PdfInfo $info, int $page, int $width, string $out): void
    {
        // Ghostscript задаёт размер точками на дюйм: ширина листа в дюймах = пункты / 72
        $dpi = $width / ($info->widthPt / 72);

        $this->run([
            $this->binary, '-q', '-dSAFER', '-dBATCH', '-dNOPAUSE', '-sDEVICE=png16m',
            '-r'.number_format($dpi, 4, '.', ''), '-dFirstPage='.$page, '-dLastPage='.$page,
            // Сглаживание текста и линий — иначе мелкий шрифт цен рваный
            '-dTextAlphaBits=4', '-dGraphicsAlphaBits=4',
            '-o', $out, $pdf,
        ]);

        if (! is_file($out)) {
            throw new MenuPdfException("Не получилось нарисовать страницу {$page}.");
        }
    }

    private function version(): string
    {
        return trim($this->run([$this->binary, '--version'])->output());
    }

    /** @param  list<string>  $command */
    private function run(array $command): ProcessResult
    {
        $result = Process::timeout($this->timeout)->run($command);
        $output = $result->output().$result->errorOutput();

        if (str_contains($output, 'requires a password') || str_contains($output, 'Password did not work')) {
            throw new MenuPdfException('PDF защищён паролем. Попросите типографию прислать файл без пароля.');
        }
        if ($result->exitCode() === 127) {
            throw new MenuPdfException('На сервере нет программы Ghostscript (gs) — сообщите разработчику.');
        }
        if (! $result->successful()) {
            // Подробности программы человеку ничего не скажут — они в журнале для разработчика
            Log::warning('Menu PDF: '.implode(' ', $command), ['output' => mb_substr($output, 0, 2000)]);

            throw new MenuPdfException('Не получилось прочитать PDF — он точно не битый? Попробуйте сохранить его заново.');
        }

        return $result;
    }

    // Путь внутри строки PostScript: скобки и обратную косую черту экранируем
    private function psString(string $value): string
    {
        return addcslashes($value, '()\\');
    }
}
