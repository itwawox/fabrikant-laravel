<?php

namespace App\Console\Commands;

use App\Services\LegacyImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

#[Signature('legacy:import {path? : Папка старого сайта, по умолчанию ~/Herd/fabrikant}')]
#[Description('Переносит меню, акции и галерею старого сайта в базу и storage (можно запускать повторно)')]
class LegacyImport extends Command
{
    public function handle(LegacyImporter $importer): int
    {
        $path = $this->argument('path') ?: ($_SERVER['HOME'] ?? '').'/Herd/fabrikant';
        $path = (string) preg_replace('#^~(?=/|$)#', (string) ($_SERVER['HOME'] ?? '~'), $path);

        try {
            $report = $importer->import($path);
        } catch (InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($report['warnings'] as $warning) {
            $this->components->warn($warning);
        }
        $this->components->info("Перенесено из {$path}");
        foreach ($report['counts'] as $label => $count) {
            $this->components->twoColumnDetail($label, (string) $count);
        }

        return self::SUCCESS;
    }
}
