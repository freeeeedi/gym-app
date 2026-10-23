# Схема базы данных (PostgreSQL)

```
users ─┬─< workouts ─┬─< workout_exercises ─┬─< workout_sets
       │             │                      └── exercises
       │             └── day_templates
       ├─< exercises (свои, user_id) ──< exercise_muscle >── muscles
       └─< day_templates (свои) ──< day_template_muscle >── muscles
warmup_exercises ──< muscle_warmup_exercise >── muscles
```

## Таблицы

### users
Стандартная таблица Breeze + `training_settings` (json, nullable) — переопределения `config('training.defaults')`.

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

Служебные таблицы Laravel: `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, `migrations`.
