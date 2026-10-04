<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Feature-тесты работают с приложением и чистой базой (SQLite в памяти, см. phpunit.xml).
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
