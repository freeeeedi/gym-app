<?php

/*
 * Стандартные шаблоны тренировочных дней.
 * muscles — целевые группы мышц, patterns — обязательные паттерны движения.
 */

return [
    ['slug' => 'pull', 'name' => 'Тяга (спина и бицепс)', 'description' => 'Вертикальная и горизонтальная тяга, сгибания рук, задняя дельта.', 'muscles' => ['back', 'biceps', 'forearms'], 'patterns' => ['vertical_pull', 'horizontal_pull', 'elbow_flexion']],
    ['slug' => 'push', 'name' => 'Жим (грудь, плечи, трицепс)', 'description' => 'Горизонтальный, наклонный и вертикальный жим, махи, разгибания рук.', 'muscles' => ['chest', 'shoulders', 'triceps'], 'patterns' => ['horizontal_push', 'incline_push', 'vertical_push', 'shoulder_abduction', 'elbow_extension']],
    ['slug' => 'legs', 'name' => 'Ноги', 'description' => 'Присед, наклон, сгибания и разгибания ног, икры, пресс.', 'muscles' => ['quads', 'hamstrings', 'glutes', 'calves', 'abs'], 'patterns' => ['squat', 'hinge', 'knee_flexion', 'calf_raise']],
    ['slug' => 'upper', 'name' => 'Верх тела', 'description' => 'Жимы и тяги в двух плоскостях плюс руки.', 'muscles' => ['chest', 'back', 'shoulders', 'biceps', 'triceps'], 'patterns' => ['horizontal_push', 'horizontal_pull', 'vertical_push', 'vertical_pull']],
    ['slug' => 'lower', 'name' => 'Низ тела', 'description' => 'Присед, наклон, выпады, икры.', 'muscles' => ['quads', 'hamstrings', 'glutes', 'calves'], 'patterns' => ['squat', 'hinge', 'lunge', 'calf_raise']],
    ['slug' => 'full-body', 'name' => 'Всё тело', 'description' => 'По одному базовому движению на каждую крупную группу.', 'muscles' => ['chest', 'back', 'shoulders', 'quads', 'hamstrings', 'glutes'], 'patterns' => ['squat', 'hinge', 'horizontal_push', 'horizontal_pull', 'vertical_push']],
];
