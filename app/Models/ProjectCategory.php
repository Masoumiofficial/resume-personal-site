<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectCategory extends Model
{
    use Sortable;

    protected $fillable = ['name', 'slug', 'sort_order'];

    protected $attributes = ['sort_order' => 0];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_category');
    }
}
