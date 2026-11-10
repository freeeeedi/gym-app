<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class WeightEntry extends Model
{
    protected $fillable = ['user_id', 'measured_on', 'weight_kg'];

    protected function casts(): array
    {
        return ['measured_on' => 'date', 'weight_kg' => 'float'];
    }

    /** Храним чистую дату (Y-m-d) в любой СУБД — важно для сравнений в SQLite. */
    protected function measuredOn(): Attribute
    {
        return Attribute::set(fn ($value) => Carbon::parse($value)->toDateString());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
