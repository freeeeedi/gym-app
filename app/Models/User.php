<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\NutritionProfile;
use App\Support\TrainingSettings;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'training_settings',
        'nutrition_profile',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'training_settings' => 'array',
            'nutrition_profile' => 'array',
        ];
    }

    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public function dayTemplates(): HasMany
    {
        return $this->hasMany(DayTemplate::class);
    }

    /** Настройки тренировок: значения по умолчанию из конфига + переопределения пользователя. */
    public function trainingSettings(): TrainingSettings
    {
        return new TrainingSettings($this->training_settings ?? []);
    }

    public function foods(): HasMany
    {
        return $this->hasMany(Food::class);
    }

    public function favoriteFoods(): BelongsToMany
    {
        return $this->belongsToMany(Food::class, 'favorite_foods')->withPivot('created_at');
    }

    public function mealEntries(): HasMany
    {
        return $this->hasMany(MealEntry::class);
    }

    public function mealTemplates(): HasMany
    {
        return $this->hasMany(MealTemplate::class);
    }

    public function weightEntries(): HasMany
    {
        return $this->hasMany(WeightEntry::class);
    }

    public function waterLogs(): HasMany
    {
        return $this->hasMany(WaterLog::class);
    }

    public function shoppingListItems(): HasMany
    {
        return $this->hasMany(ShoppingListItem::class);
    }

    /** Текущий вес — последняя запись в журнале веса. */
    public function currentWeight(): ?float
    {
        $weight = $this->weightEntries()->orderByDesc('measured_on')->value('weight_kg');

        return $weight === null ? null : (float) $weight;
    }

    /** Профиль питания: анкета из nutrition_profile + текущий вес. */
    public function nutritionProfile(): NutritionProfile
    {
        return NutritionProfile::fromArray($this->nutrition_profile ?? [], $this->currentWeight());
    }
}
