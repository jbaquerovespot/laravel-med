<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObservationComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'observation_id',
        'code',
        'display',
        'value_quantity',
        'value_unit',
        'reference_range_low',
        'reference_range_high',
    ];

    protected $casts = [
        'value_quantity' => 'float',
        'reference_range_low' => 'float',
        'reference_range_high' => 'float',
    ];

    public function observation(): BelongsTo
    {
        return $this->belongsTo(Observation::class);
    }
}
