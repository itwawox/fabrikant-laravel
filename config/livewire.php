<?php

// Только то, что отличается от настроек Livewire по умолчанию (остальное берётся из пакета)
return [

    // Загрузка файлов в админке. PDF типографии весит около 40 МБ — лимит 100 МБ, как в config/menu.php,
    // и 15 минут на загрузку по медленному интернету. На хостинге ещё нужны upload_max_filesize и
    // post_max_size не меньше 100M (панель reg.ru → PHP), см. docs/hosting.md.
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),
        'rules' => ['required', 'file', 'max:102400'],
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 15,
        'cleanup' => true,
    ],
];
