<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

// Тексты и SEO одной страницы сайта (key: about, contacts…). Что можно править и тексты по умолчанию — PageTexts
#[Fillable(['key', 'seo_title', 'seo_description', 'og_image', 'content'])]
class Page extends Model
{
    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }
}
