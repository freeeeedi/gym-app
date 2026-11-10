# Схема базы данных (PostgreSQL)

```
users ─┬─< workouts ─┬─< workout_exercises ─┬─< workout_sets
       │             │                      └── exercises
       │             └── day_templates
       ├─< exercises (свои, user_id) ──< exercise_muscle >── muscles
       └─< day_templates (свои) ──< day_template_muscle >── muscles
warmup_exercises ──< muscle_warmup_exercise >── muscles

Питание:
users ─┬─< meal_entries ──(food_id, dish_id: nullable)──> foods, dishes
       ├─< meal_templates ──< meal_template_items
       ├─< foods (свои, user_id)
       ├─< favorite_foods >── foods
       ├─< weight_entries
       ├─< water_logs
       └─< shopping_list_items >── dishes
dishes ──< dish_ingredients >── foods
```

## Таблицы

### users
Стандартная таблица Breeze + `training_settings` (json, nullable) — переопределения `config('training.defaults')`; `nutrition_profile` (json, nullable) — анкета питания: `sex`, `age`, `height_cm`, `activity`, `goal`, `restrictions`. Вес в анкете не хранится — берётся последняя запись `weight_entries`.

### muscles
| поле | тип | |
|---|---|---|
| id | bigint PK | |
| slug | varchar(32) unique | chest, back, shoulders, biceps, triceps, quads, hamstrings, glutes, calves, abs, forearms |
| name | varchar(64) | название |
| sort | smallint | порядок вывода |

### exercises
| поле | тип | |
|---|---|---|
| id | bigint PK | |
| user_id | FK users, nullable, cascade | NULL — общий каталог |
| slug | varchar(80) unique, nullable | ключ сидера |
| name, description | varchar / text | |
| pattern | varchar(32), index | паттерн движения (`training.patterns`) |
| equipment | varchar(16), index | machine, barbell, dumbbell, cable, bodyweight |
| mechanic | varchar(16) | compound / isolation |
| fatigue | tinyint | утомляемость 1–5 |
| icon | varchar(32), nullable | ключ SVG, запасная иконка по умолчанию |
| rep_min, rep_max | tinyint | рабочий диапазон повторений |

### exercise_muscle
PK (exercise_id, muscle_id); `role` — primary / secondary; индекс (muscle_id, role). Каскадное удаление.

### day_templates
`user_id` (nullable, cascade; NULL — стандартный), `slug` unique nullable, `name`, `description`, `patterns` (json — обязательные паттерны).

### day_template_muscle
PK (day_template_id, muscle_id) — целевые мышцы шаблона.

### workouts
| поле | тип | |
|---|---|---|
| id | bigint PK | |
| user_id | FK users, cascade | |
| day_template_id | FK day_templates, nullable, set null | |
| title | varchar(120) | |
| scheduled_on | date | индекс (user_id, scheduled_on) |
| status | varchar(16) | planned / in_progress / completed; индекс (user_id, status) |
| started_at, finished_at | timestamp, nullable | |
| notes | text, nullable | |

### workout_exercises
`workout_id` (FK, cascade), `exercise_id` (FK, restrict — нельзя удалить упражнение с историей), `position`, `planned_sets`. Уникально (workout_id, exercise_id); индекс exercise_id.

### workout_sets
`workout_exercise_id` (FK, cascade), `position`, `weight_kg` decimal(6,2), `reps` smallint, `is_warmup` bool. Индекс (workout_exercise_id, position).

### warmup_exercises
`slug` unique, `name`, `type` (general / mobility, index), `minutes`, `reps` (строка, например «10 + 10»), `joints` (json), `description`, `icon`.

### muscle_warmup_exercise
PK (warmup_exercise_id, muscle_id).

### foods
| поле | тип | |
|---|---|---|
| id | bigint PK | |
| user_id | FK users, nullable, cascade | NULL — общий каталог, иначе — продукт пользователя |
| slug | varchar(80) unique, nullable | ключ сидера |
| name | varchar(120) | |
| category | varchar(16), индекс | ключ `nutrition.food_categories` |
| kcal | decimal(6,1) | на 100 г |
| protein, fat, carbs | decimal(5,1) | на 100 г |
| tags | json, nullable | признаки «содержит»: meat, fish, dairy, lactose, eggs, gluten, nuts; occasional — «не на каждый день» |
| serving_g, serving_name | decimal(6,1), varchar(40), nullable | типичная порция: «1 шт.» = 55 г |

### favorite_foods
PK (user_id, food_id), `created_at`. Каскадное удаление с обеих сторон.

### dishes
`slug` unique, `name`, `meals` (json — для каких приёмов пищи), `servings` (на сколько порций рецепт), `cook_minutes`, `recipe` (text). КБЖУ не хранится — считается из ингредиентов.

### dish_ingredients
`dish_id` (FK, cascade), `food_id` (FK, restrict), `grams` decimal(7,1) — на весь рецепт, `position`. Уникально (dish_id, food_id).

### meal_entries
| поле | тип | |
|---|---|---|
| id | bigint PK | |
| user_id | FK users, cascade | |
| eaten_on | date | индекс (user_id, eaten_on) |
| meal | varchar(16) | breakfast / lunch / dinner / snack |
| food_id | FK foods, nullable, set null | индекс (user_id, food_id) — для «недавних» |
| dish_id | FK dishes, nullable, set null | |
| name | varchar(120) | снимок названия |
| grams | decimal(7,1), nullable | для продуктов |
| servings | decimal(4,2), nullable | для блюд |
| kcal | decimal(7,1) | снимок КБЖУ за всю съеденную порцию |
| protein, fat, carbs | decimal(6,1) | |

Ручной ввод «за порцию» — запись без `food_id`, `dish_id`, `grams` и `servings`.

### meal_templates, meal_template_items
Шаблон приёма пищи: `user_id` (FK, cascade), `name`. Позиции повторяют поля записи дневника (`food_id`, `dish_id`, `name`, `grams`, `servings`, КБЖУ) без даты и приёма пищи.

### weight_entries
`user_id` (FK, cascade), `measured_on` date, `weight_kg` decimal(5,1). Уникально (user_id, measured_on) — одна запись в день.

### water_logs
`user_id` (FK, cascade), `logged_on` date, `ml` integer — сумма выпитого за день. Уникально (user_id, logged_on).

### shopping_list_items
`user_id` (FK, cascade), `dish_id` (FK, cascade), `servings`. Уникально (user_id, dish_id). Сам список продуктов не хранится — собирается из ингредиентов выбранных блюд.

Служебные таблицы Laravel: `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, `migrations`.
