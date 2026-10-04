<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

// Праздник без года: month_day — «01-01», «05-09»
#[Fillable(['month_day', 'title'])]
class Holiday extends Model {}
