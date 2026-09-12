<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'rating_avg',
        'ratings_count',
        'reviews_count',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'rating_avg' => 'decimal:1',
            'ratings_count' => 'integer',
            'reviews_count' => 'integer',
            'captured_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
