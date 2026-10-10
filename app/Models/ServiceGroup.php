<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceGroup extends Model
{
    use Sortable;

    protected $fillable = ['title', 'slug', 'summary', 'icon', 'sort_order'];

    protected $attributes = ['sort_order' => 0];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
