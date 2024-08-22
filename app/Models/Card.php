<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *
 * @mixin Builder
 */
class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'name'
    ];

    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }
}
