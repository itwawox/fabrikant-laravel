<?php

// Отладочные вызовы не должны попасть на прод.
arch('no debugging calls')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();
