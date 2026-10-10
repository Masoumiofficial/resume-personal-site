<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMedia extends Model
{
    protected $table = 'project_media';

    protected $fillable = ['project_id', 'media_id', 'caption', 'sort_order'];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
