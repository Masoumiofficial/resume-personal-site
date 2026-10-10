<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;

/** No percentages, ever (D6): an optional word label and/or years of use. */
class Skill extends Model
{
    use Sortable;

    public const GROUPS = ['development' => 'توسعهٔ وب و نرم‌افزار', 'data_ai' => 'داده و هوش مصنوعی', 'design' => 'طراحی', 'management' => 'مدیریت و رشد', 'tools' => 'ابزارها و پلتفرم‌ها', 'language' => 'زبان‌ها'];

    protected $fillable = ['name', 'group', 'level_label', 'years', 'is_visible', 'sort_order'];

    protected $attributes = ['is_visible' => true, 'sort_order' => 0, 'level_label' => null, 'years' => null];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean', 'years' => 'integer'];
    }

    public function sortScope(): array
    {
        return ['group'];
    }
}
