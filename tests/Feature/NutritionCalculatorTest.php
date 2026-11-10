<?php

use App\Services\Nutrition\NutritionCalculator;
use App\Support\Macros;
use App\Support\NutritionProfile;

function nutritionProfile(array $overrides = []): NutritionProfile
{
    return new NutritionProfile(...[
        'sex' => 'male', 'age' => 30, 'heightCm' => 180.0, 'weightKg' => 80.0,
        'activity' => 'moderate', 'goal' => 'maintain', 'restrictions' => [],
        ...$overrides,
    ]);
}

beforeEach(function () {
    $this->calculator = new NutritionCalculator;
});

it('считает ИМТ и определяет категорию', function () {
    expect($this->calculator->bmi(80, 180))->toBe(24.7)
        ->and($this->calculator->bmi(60, 165))->toBe(22.0)
        ->and($this->calculator->bmi(0, 180))->toBe(0.0)
        ->and($this->calculator->bmiCategory(15.9)['key'])->toBe('severe_underweight')
        ->and($this->calculator->bmiCategory(18.4)['key'])->toBe('underweight')
        ->and($this->calculator->bmiCategory(18.5)['key'])->toBe('normal')
        ->and($this->calculator->bmiCategory(24.9)['label'])->toBe('Норма')
        ->and($this->calculator->bmiCategory(25.0)['key'])->toBe('overweight')
        ->and($this->calculator->bmiCategory(30.0)['key'])->toBe('obese_1')
        ->and($this->calculator->bmiCategory(45.0)['key'])->toBe('obese_3');
});

it('считает диапазон нормального веса по росту', function () {
    expect($this->calculator->normalWeightRange(180))->toBe([59.9, 81.0]);
});

it('считает базовый обмен по Миффлину — Сан Жеору для мужчин и женщин', function () {
    // 10 × 80 + 6,25 × 180 − 5 × 30 + 5
    expect($this->calculator->bmr(nutritionProfile()))->toBe(1780.0)
        // 10 × 60 + 6,25 × 165 − 5 × 25 − 161
        ->and($this->calculator->bmr(nutritionProfile(['sex' => 'female', 'age' => 25, 'heightCm' => 165.0, 'weightKg' => 60.0])))->toBe(1345.25);
});

it('умножает базовый обмен на коэффициент активности', function () {
    expect($this->calculator->tdee(nutritionProfile(['activity' => 'sedentary'])))->toBe(1780 * 1.2)
        ->and($this->calculator->tdee(nutritionProfile(['activity' => 'moderate'])))->toBe(1780 * 1.55)
        ->and($this->calculator->tdee(nutritionProfile(['activity' => 'extreme'])))->toBe(1780 * 1.9);
});

it('норма для поддержания: расход без поправки, белок и жиры на кг, углеводы — остаток', function () {
    $targets = $this->calculator->targets(nutritionProfile());

    expect($targets->bmr)->toBe(1780)
        ->and($targets->tdee)->toBe(2759)
        ->and($targets->kcal)->toBe(2760)
        ->and($targets->protein)->toBe(128)   // 1,6 г/кг
        ->and($targets->fat)->toBe(72)        // 0,9 г/кг
        ->and($targets->carbs)->toBe(400)     // (2760 − 128×4 − 72×9) / 4
        ->and($targets->waterMl)->toBe(2400)  // 30 мл/кг
        ->and($targets->notes)->toBe([]);
});

it('набор массы: +10% калорий и больше белка', function () {
    $targets = $this->calculator->targets(nutritionProfile(['goal' => 'gain']));

    expect($targets->kcal)->toBe(3030)
        ->and($targets->protein)->toBe(144)
        ->and($targets->fat)->toBe(80)
        ->and($targets->carbs)->toBe(434)
        ->and($targets->notes[0])->toContain('+10%');
});

it('сушка: −15% калорий, белок 2 г/кг', function () {
    $targets = $this->calculator->targets(nutritionProfile(['goal' => 'cut']));

    expect($targets->kcal)->toBe(2350)
        ->and($targets->protein)->toBe(160)
        ->and($targets->fat)->toBe(64)
        ->and($targets->carbs)->toBe(284)
        ->and($targets->notes[0])->toContain('-15%');
});

it('калории нормы сходятся с суммой калорий макронутриентов', function () {
    foreach (['gain', 'maintain', 'cut'] as $goal) {
        foreach (['sedentary', 'moderate', 'extreme'] as $activity) {
            $t = $this->calculator->targets(nutritionProfile(['goal' => $goal, 'activity' => $activity]));
            expect(abs($t->kcal - ($t->protein * 4 + $t->fat * 9 + $t->carbs * 4)))->toBeLessThanOrEqual(2);
        }
    }
});

it('не опускает норму ниже безопасного минимума', function () {
    $targets = $this->calculator->targets(nutritionProfile([
        'sex' => 'female', 'age' => 60, 'heightCm' => 150.0, 'weightKg' => 45.0, 'activity' => 'sedentary', 'goal' => 'cut',
    ]));

    expect($targets->kcal)->toBe(1200)
        ->and($targets->protein)->toBe(90)
        ->and($targets->fat)->toBe(36)
        ->and($targets->carbs)->toBe(129)
        ->and(implode(' ', $targets->notes))->toContain('безопасного минимума');
});

it('при ожирении считает белок и жиры от веса при ИМТ 25', function () {
    $profile = nutritionProfile(['weightKg' => 120.0]);
    $targets = $this->calculator->targets($profile);

    expect($this->calculator->referenceWeight($profile))->toBe(81.0)
        ->and($targets->protein)->toBe(130)  // 1,6 × 81, а не 1,6 × 120
        ->and($targets->fat)->toBe(73)
        ->and(implode(' ', $targets->notes))->toContain('ИМТ 25');
});

it('урезает жиры до нижней границы, если углеводов слишком мало', function () {
    $targets = $this->calculator->targets(nutritionProfile([
        'sex' => 'female', 'age' => 50, 'heightCm' => 160.0, 'weightKg' => 75.0, 'activity' => 'sedentary', 'goal' => 'cut',
    ]));

    expect($targets->kcal)->toBe(1370)
        ->and($targets->protein)->toBe(150)
        ->and($targets->fat)->toBe(45)       // 0,6 г/кг вместо 0,8
        ->and($targets->carbs)->toBe(91)
        ->and(implode(' ', $targets->notes))->toContain('Жиры снижены');
});

it('без полного профиля норма не считается', function () {
    expect($this->calculator->targets(nutritionProfile(['weightKg' => null])))->toBeNull()
        ->and($this->calculator->targets(nutritionProfile(['sex' => null])))->toBeNull()
        ->and($this->calculator->targets(new NutritionProfile))->toBeNull()
        ->and($this->calculator->waterMl(null))->toBe(2000);
});

it('профиль собирается из массива и отбрасывает неизвестные значения', function () {
    $profile = NutritionProfile::fromArray([
        'sex' => 'female', 'age' => '28', 'height_cm' => '170', 'activity' => 'нет такого', 'goal' => 'cut',
        'restrictions' => ['vegan', 'лишнее', 'no_nuts'],
    ], 62.5);

    expect($profile->isComplete())->toBeTrue()
        ->and($profile->age)->toBe(28)
        ->and($profile->activity)->toBe('moderate')
        ->and($profile->goal)->toBe('cut')
        ->and($profile->restrictions)->toBe(['vegan', 'no_nuts'])
        ->and($profile->excludedTags())->toBe(['meat', 'fish', 'dairy', 'eggs', 'nuts'])
        ->and($profile->allows(['gluten']))->toBeTrue()
        ->and($profile->allows(['gluten', 'eggs']))->toBeFalse();
});

it('складывает, масштабирует и округляет КБЖУ', function () {
    $a = new Macros(100, 10, 5, 2.5);
    $sum = Macros::sum([$a, $a->times(0.5)]);

    expect($sum->toArray())->toBe(['kcal' => 150.0, 'protein' => 15.0, 'fat' => 7.5, 'carbs' => 3.75])
        ->and($sum->rounded()->carbs)->toBe(3.8)
        ->and($sum->minus($a)->kcal)->toBe(50.0)
        ->and((new Macros)->isZero())->toBeTrue()
        ->and(round((new Macros(200, 25, 0, 0))->share('protein'), 2))->toBe(0.5);
});
