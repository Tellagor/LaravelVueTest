<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'yandex_url',
        'yandex_id',
        'name',
        'rating_avg',
        'ratings_count',
        'reviews_count',
        'status',
        'last_error',
        'last_parsed_at',
    ];

    protected function casts(): array
    {
        return [
            'rating_avg' => 'decimal:1',
            'ratings_count' => 'integer',
            'reviews_count' => 'integer',
            'last_parsed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(OrganizationSnapshot::class);
    }

    public function parsingRuns(): HasMany
    {
        return $this->hasMany(ParsingRun::class);
    }
}
