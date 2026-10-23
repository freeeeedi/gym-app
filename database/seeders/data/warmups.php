<?php

/*
 * Библиотека разминки.
 * type: general — общая (кардио), mobility — суставная/специфическая активация.
 * muscles — группы мышц, joints — суставы, которые готовит упражнение.
 */

return [
    // Общая разминка
    ['slug' => 'bike', 'name' => 'Велотренажёр', 'type' => 'general', 'minutes' => 5, 'reps' => null, 'muscles' => ['quads', 'hamstrings', 'glutes', 'calves'], 'joints' => ['knee', 'hip', 'ankle'], 'icon' => 'cardio', 'description' => 'Лёгкий темп, пульс 110–130.'],
    ['slug' => 'rower', 'name' => 'Гребной тренажёр', 'type' => 'general', 'minutes' => 5, 'reps' => null, 'muscles' => ['back', 'biceps', 'quads', 'shoulders'], 'joints' => ['shoulder', 'hip', 'knee'], 'icon' => 'cardio', 'description' => 'Спокойный темп, акцент на технике.'],
    ['slug' => 'elliptical', 'name' => 'Эллипс', 'type' => 'general', 'minutes' => 5, 'reps' => null, 'muscles' => ['quads', 'glutes', 'chest', 'shoulders'], 'joints' => ['hip', 'knee', 'shoulder'], 'icon' => 'cardio', 'description' => 'Работайте и руками, и ногами.'],
    ['slug' => 'incline-walk', 'name' => 'Ходьба в горку на дорожке', 'type' => 'general', 'minutes' => 5, 'reps' => null, 'muscles' => ['calves', 'glutes', 'hamstrings'], 'joints' => ['ankle', 'hip'], 'icon' => 'cardio', 'description' => 'Наклон 6–10 %, скорость 5 км/ч.'],
    ['slug' => 'jump-rope', 'name' => 'Скакалка', 'type' => 'general', 'minutes' => 3, 'reps' => null, 'muscles' => ['calves', 'forearms', 'shoulders'], 'joints' => ['ankle', 'wrist'], 'icon' => 'cardio', 'description' => 'Короткие прыжки на носках.'],

    // Специфическая разминка и мобильность
    ['slug' => 'arm-circles', 'name' => 'Вращения в плечевых суставах', 'type' => 'mobility', 'minutes' => 1, 'reps' => '10 + 10', 'muscles' => ['shoulders', 'chest', 'back'], 'joints' => ['shoulder'], 'icon' => 'mobility', 'description' => 'Вперёд и назад, постепенно увеличивая амплитуду.'],
    ['slug' => 'band-dislocates', 'name' => 'Проводка палки/резинки за спину', 'type' => 'mobility', 'minutes' => 1, 'reps' => '10–12', 'muscles' => ['shoulders', 'chest'], 'joints' => ['shoulder'], 'icon' => 'mobility', 'description' => 'Широкий хват, руки прямые.'],
    ['slug' => 'band-pull-apart', 'name' => 'Разведение резинки перед грудью', 'type' => 'mobility', 'minutes' => 1, 'reps' => '15–20', 'muscles' => ['shoulders', 'back'], 'joints' => ['shoulder'], 'icon' => 'mobility', 'description' => 'Активирует заднюю дельту и мышцы лопаток.'],
    ['slug' => 'scapular-push-up', 'name' => 'Отжимания лопатками', 'type' => 'mobility', 'minutes' => 1, 'reps' => '10–12', 'muscles' => ['chest', 'shoulders'], 'joints' => ['shoulder'], 'icon' => 'mobility', 'description' => 'Руки прямые, двигаются только лопатки.'],
    ['slug' => 'scapular-pull-up', 'name' => 'Подтягивания лопатками в висе', 'type' => 'mobility', 'minutes' => 1, 'reps' => '8–10', 'muscles' => ['back', 'forearms'], 'joints' => ['shoulder'], 'icon' => 'mobility', 'description' => 'Опускайте и сводите лопатки без сгибания рук.'],
    ['slug' => 'cat-cow', 'name' => 'Кошка — корова', 'type' => 'mobility', 'minutes' => 1, 'reps' => '8–10', 'muscles' => ['back', 'abs'], 'joints' => ['spine'], 'icon' => 'mobility', 'description' => 'Плавно прогибайте и округляйте спину.'],
    ['slug' => 'thoracic-rotation', 'name' => 'Ротации грудного отдела на четвереньках', 'type' => 'mobility', 'minutes' => 1, 'reps' => '8 + 8', 'muscles' => ['back', 'shoulders', 'chest'], 'joints' => ['spine', 'shoulder'], 'icon' => 'mobility', 'description' => 'Рука за головой, поворачивайте корпус вверх.'],
    ['slug' => 'wrist-circles', 'name' => 'Вращения кистями и локтями', 'type' => 'mobility', 'minutes' => 1, 'reps' => '10 + 10', 'muscles' => ['forearms', 'biceps', 'triceps'], 'joints' => ['wrist', 'elbow'], 'icon' => 'mobility', 'description' => 'В обе стороны.'],
    ['slug' => 'band-curl', 'name' => 'Сгибания рук с лёгкой резинкой', 'type' => 'mobility', 'minutes' => 1, 'reps' => '15–20', 'muscles' => ['biceps', 'forearms'], 'joints' => ['elbow'], 'icon' => 'mobility', 'description' => 'Прогрев локтевых суставов.'],
    ['slug' => 'band-pushdown', 'name' => 'Разгибания рук с лёгкой резинкой', 'type' => 'mobility', 'minutes' => 1, 'reps' => '15–20', 'muscles' => ['triceps'], 'joints' => ['elbow'], 'icon' => 'mobility', 'description' => 'Прогрев локтей перед жимами.'],
    ['slug' => 'hip-circles', 'name' => 'Вращения тазом', 'type' => 'mobility', 'minutes' => 1, 'reps' => '10 + 10', 'muscles' => ['glutes', 'abs'], 'joints' => ['hip', 'spine'], 'icon' => 'mobility', 'description' => 'Руки на поясе, широкая амплитуда.'],
    ['slug' => 'leg-swings', 'name' => 'Махи ногами вперёд-назад и в стороны', 'type' => 'mobility', 'minutes' => 1, 'reps' => '10 + 10', 'muscles' => ['hamstrings', 'glutes', 'quads'], 'joints' => ['hip'], 'icon' => 'mobility', 'description' => 'Держитесь за опору.'],
    ['slug' => 'bodyweight-squat', 'name' => 'Приседания без веса с паузой внизу', 'type' => 'mobility', 'minutes' => 1, 'reps' => '10', 'muscles' => ['quads', 'glutes'], 'joints' => ['knee', 'hip', 'ankle'], 'icon' => 'mobility', 'description' => 'Задержка 2–3 секунды в нижней точке.'],
    ['slug' => 'glute-bridge', 'name' => 'Ягодичный мост без веса', 'type' => 'mobility', 'minutes' => 1, 'reps' => '12–15', 'muscles' => ['glutes', 'hamstrings'], 'joints' => ['hip'], 'icon' => 'mobility', 'description' => 'Пауза вверху на 1 секунду.'],
    ['slug' => 'good-morning-bw', 'name' => 'Наклоны «гуд морнинг» без веса', 'type' => 'mobility', 'minutes' => 1, 'reps' => '10–12', 'muscles' => ['hamstrings', 'back', 'glutes'], 'joints' => ['hip', 'spine'], 'icon' => 'mobility', 'description' => 'Спина ровная, таз назад.'],
    ['slug' => 'worlds-greatest-stretch', 'name' => 'Выпад с ротацией корпуса', 'type' => 'mobility', 'minutes' => 1, 'reps' => '5 + 5', 'muscles' => ['quads', 'glutes', 'hamstrings', 'back'], 'joints' => ['hip', 'spine'], 'icon' => 'mobility', 'description' => 'Локоть к стопе, затем рука вверх.'],
    ['slug' => 'ankle-rocks', 'name' => 'Перекаты на голеностопе у стены', 'type' => 'mobility', 'minutes' => 1, 'reps' => '10 + 10', 'muscles' => ['calves'], 'joints' => ['ankle'], 'icon' => 'mobility', 'description' => 'Колено тянется к стене, пятка на полу.'],
    ['slug' => 'dead-bug', 'name' => 'Мёртвый жук', 'type' => 'mobility', 'minutes' => 1, 'reps' => '8 + 8', 'muscles' => ['abs'], 'joints' => ['spine'], 'icon' => 'mobility', 'description' => 'Поясница прижата к полу.'],
];
