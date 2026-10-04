<?php

namespace App\Services\Menu;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class PopplerRenderer implements PdfRenderer
{
    public function __construct(
        private readonly string $pdftoppm = 'pdftoppm',
        private readonly string $pdfinfo = 'pdfinfo',
        private readonly int $timeout = 120,
    ) {}

    public function info(string $pdf): PdfInfo
    {
        $output = $this->run([$this->pdfinfo, $pdf], 'pdfinfo')->output();

        if (! preg_match('/^Pages:\s+(\d+)/m', $output, $pages) || (int) $pages[1] < 1
            || ! preg_match('/^Page size:\s+([\d.]+) x ([\d.]+) pts/m', $output, $size)) {
            throw new MenuPdfException('Не получилось прочитать PDF — он точно не битый? Попробуйте сохранить его заново.');
        }

        return new PdfInfo((int) $pages[1], (float) $size[1], (float) $size[2]);
    }

    public function renderPng(string $pdf, PdfInfo $info, int $page, int $width, string $out): void
    {
        // pdftoppm сам добавляет расширение .png
        $base = preg_replace('/\.png$/', '', $out);
        $this->run([
            $this->pdftoppm, '-f', (string) $page, '-l', (string) $page, '-singlefile', '-png',
            '-scale-to-x', (string) $width, '-scale-to-y', '-1', $pdf, (string) $base,
        ], 'pdftoppm');

        if (! is_file($out)) {
            throw new MenuPdfException("Не получилось нарисовать страницу {$page}.");
        }
    }

    /** @param  list<string>  $command */
    private function run(array $command, string $tool): ProcessResult
    {
        $result = Process::timeout($this->timeout)->run($command);
        $output = $result->output().$result->errorOutput();

        if ($result->exitCode() === 127) {
            throw new MenuPdfException("На сервере нет программы {$tool} — сообщите разработчику (или включите Ghostscript: MENU_RENDERER=gs).");
        }
        if (str_contains($output, 'Incorrect password')) {
            throw new MenuPdfException('PDF защищён паролем. Попросите типографию прислать файл без пароля.');
        }
        if (! $result->successful()) {
            // Подробности программы человеку ничего не скажут — они в журнале для разработчика
            Log::warning('Menu PDF: '.implode(' ', $command), ['output' => mb_substr($output, 0, 2000)]);

            throw new MenuPdfException('Не получилось прочитать PDF — он точно не битый? Попробуйте сохранить его заново.');
        }

        return $result;
    }
}
