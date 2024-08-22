<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 * @mixin Builder
 */
class User extends Model
{
    use HasFactory;

    public string $username;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uid',
        'state',
        'type',
        'data',
    ];

    public function cards(): HasMany{
        return $this->hasMany(Card::class)->limit(5);
    }
}
