<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'replyto_id',
        'author_id',
        'body',
        'path',
        'level',
        'number',
        'status',
    ];

    protected $casts = [
        'children_count' => 'integer',
        'level' => 'integer',
        'number' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replyto_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'replyto_id');
    }
}
