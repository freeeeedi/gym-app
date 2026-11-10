<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class WaterLog extends Model
{
    protected $fillable = ['user_id', 'logged_on', 'ml'];

    protected function casts(): array
    {
        return ['logged_on' => 'date', 'ml' => 'integer'];
    }

    /** Храним чистую дату (Y-m-d) в любой СУБД — важно для сравнений в SQLite. */
    protected function loggedOn(): Attribute
    {
        return Attribute::set(fn ($value) => Carbon::parse($value)->toDateString());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
