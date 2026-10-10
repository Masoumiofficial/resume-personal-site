<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    use Sortable;

    protected $table = 'educations';

    protected $fillable = ['institution', 'degree', 'field', 'start_date', 'end_date', 'date_precision', 'description', 'is_visible', 'sort_order'];

    protected $attributes = ['date_precision' => 'year', 'is_visible' => true, 'sort_order' => 0];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'is_visible' => 'boolean'];
    }
}
