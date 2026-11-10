@php
    $total = $week['total'];
    $weekTarget = $week['target'];
    $remaining = $week['remaining'];
    $average = $week['average'];
    $today = \Illuminate\Support\Carbon::today();
    // Цвет калорий дня: в пределах ±10% от нормы — в цель, выше — перебор, ниже — недобор.
    $kcalClass = function ($kcal, bool $logged) use ($targets) {
        if (! $logged) {
            return 'text-zinc-600';
        }
        if (! $targets) {
            return 'text-zinc-100';
        }

        return $kcal > $targets->kcal * 1.1 ? 'text-red-400' : ($kcal < $targets->kcal * 0.9 ? 'text-amber-300' : 'text-lime-300');
    };
    $chartDays = collect($week['days'])->map(fn ($d) => [
        'label' => $d['date']->translatedFormat('D'),
        'kcal' => round($d['totals']->kcal),
    ])->all();
    $weightPoints = $weights->map(fn ($w) => ['date' => $w->measured_on->format('d.m'), 'kg' => $w->weight_kg])->values()->all();
    $weightDelta = $weights->count() >= 2 ? round($weights->last()->weight_kg - $weights->first()->weight_kg, 1) : null;
@endphp
<x-app-layout>
    <x-slot name="title">Статистика питания</x-slot>
    <x-slot name="header">
        <h1 class="flex-1 font-semibold">Питание</h1>
    </x-slot>

    <x-nutrition-tabs />

    <div class="space-y-5">
        {{-- Неделя --}}
        <div class="flex items-center gap-2">
            <a href="{{ route('nutrition.stats', ['week' => $prev->toDateString()]) }}" class="btn-secondary btn-sm !w-11 !px-0 text-lg" aria-label="Предыдущая неделя">‹</a>
            <div class="flex-1 text-center">
                <div class="font-semibold">{{ $isCurrentWeek ? 'Эта неделя' : 'Неделя' }}</div>
                <div class="text-xs text-zinc-400">{{ $week['start']->translatedFormat('j M') }} — {{ $week['end']->translatedFormat('j M Y') }}</div>
            </div>
            <a href="{{ route('nutrition.stats', ['week' => $next->toDateString()]) }}" class="btn-secondary btn-sm !w-11 !px-0 text-lg" aria-label="Следующая неделя">›</a>
        </div>

        <section class="card space-y-3" aria-label="Итог недели">
            <div class="flex items-baseline justify-between">
                <h2 class="h2">За неделю</h2>
                <span class="text-xs text-zinc-500">дней с записями: {{ $week['logged_days'] }} из 7</span>
            </div>
            @if ($weekTarget)
                <x-macro-bar label="Калории" :value="$total->kcal" :target="$weekTarget->kcal" unit="ккал" :warn-over="true" />
                <div class="grid grid-cols-3 gap-3">
                    <x-macro-bar label="Белки" :value="$total->protein" :target="$weekTarget->protein" color="bg-sky-400" />
                    <x-macro-bar label="Жиры" :value="$total->fat" :target="$weekTarget->fat" color="bg-amber-400" />
                    <x-macro-bar label="Углеводы" :value="$total->carbs" :target="$weekTarget->carbs" color="bg-violet-400" />
                </div>
                <p class="text-sm {{ $remaining->kcal < 0 ? 'text-red-300' : 'text-zinc-300' }}">
                    @if ($remaining->kcal >= 0)
                        До недельной нормы осталось {{ format_int($remaining->kcal) }} ккал.
                    @else
                        Недельная норма превышена на {{ format_int(abs($remaining->kcal)) }} ккал.
                    @endif
                </p>
            @else
                <div class="text-sm text-zinc-300">
                    Съедено: <span class="font-semibold">{{ format_int($total->kcal) }} ккал</span> · Б {{ format_int($total->protein) }} · Ж {{ format_int($total->fat) }} · У {{ format_int($total->carbs) }}
                </div>
                <a href="{{ route('nutrition.profile.edit') }}" class="block rounded-xl border border-amber-900 bg-amber-950/40 p-3 text-sm text-amber-200">
                    Заполните профиль питания, чтобы сравнивать съеденное с нормой ›
                </a>
            @endif
            @if ($week['logged_days'] > 0)
                <div class="rounded-xl bg-zinc-800 p-3 text-sm">
                    <div class="text-xs text-zinc-400">В среднем за день с записями</div>
                    <div class="mt-0.5">
                        <span class="font-semibold">{{ format_int($average->kcal) }} ккал</span>@if ($targets) <span class="text-zinc-500">из {{ format_int($targets->kcal) }}</span>@endif
                        · Б {{ format_int($average->protein) }} · Ж {{ format_int($average->fat) }} · У {{ format_int($average->carbs) }}
                    </div>
                </div>
            @endif
        </section>

        <section class="card space-y-3" aria-label="Калории по дням">
            <h2 class="h2">Калории по дням</h2>
            <div class="relative h-48"><canvas id="chart-kcal" aria-label="График калорий по дням недели"></canvas></div>
            <div class="-mx-1 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-zinc-500">
                            <th class="px-1 py-1 text-left font-normal">День</th>
                            <th class="px-1 py-1 text-right font-normal">Ккал</th>
                            <th class="px-1 py-1 text-right font-normal">Б</th>
                            <th class="px-1 py-1 text-right font-normal">Ж</th>
                            <th class="px-1 py-1 text-right font-normal">У</th>
                            @if ($targets)<th class="px-1 py-1 text-right font-normal">Остаток</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($week['days'] as $d)
                            @php($t = $d['totals'])
                            <tr class="border-t border-zinc-800 {{ $d['date']->equalTo($today) ? 'bg-zinc-800/40' : '' }}">
                                <td class="px-1 py-1.5">
                                    <a href="{{ route('nutrition.diary', ['date' => $d['date']->toDateString()]) }}" class="text-zinc-300 underline decoration-zinc-700 underline-offset-2">
                                        <span class="capitalize">{{ $d['date']->translatedFormat('D') }}</span>, {{ $d['date']->format('d.m') }}
                                    </a>
                                </td>
                                <td class="px-1 py-1.5 text-right tabular-nums {{ $kcalClass($t->kcal, $d['logged']) }}">{{ $d['logged'] ? format_int($t->kcal) : '—' }}</td>
                                <td class="px-1 py-1.5 text-right tabular-nums text-zinc-400">{{ $d['logged'] ? format_int($t->protein) : '—' }}</td>
                                <td class="px-1 py-1.5 text-right tabular-nums text-zinc-400">{{ $d['logged'] ? format_int($t->fat) : '—' }}</td>
                                <td class="px-1 py-1.5 text-right tabular-nums text-zinc-400">{{ $d['logged'] ? format_int($t->carbs) : '—' }}</td>
                                @if ($targets)
                                    <td class="px-1 py-1.5 text-right tabular-nums text-zinc-400">{{ $d['logged'] ? format_int($targets->kcal - $t->kcal) : '—' }}</td>
                                @endif
                            </tr>
                        @endforeach
                        <tr class="border-t border-zinc-700 font-semibold">
                            <td class="px-1 py-1.5">Итого</td>
                            <td class="px-1 py-1.5 text-right tabular-nums">{{ format_int($total->kcal) }}</td>
                            <td class="px-1 py-1.5 text-right tabular-nums">{{ format_int($total->protein) }}</td>
                            <td class="px-1 py-1.5 text-right tabular-nums">{{ format_int($total->fat) }}</td>
                            <td class="px-1 py-1.5 text-right tabular-nums">{{ format_int($total->carbs) }}</td>
                            @if ($targets)<td class="px-1 py-1.5 text-right tabular-nums">{{ format_int($remaining->kcal) }}</td>@endif
                        </tr>
                    </tbody>
                </table>
            </div>
            @if ($targets)
                <p class="text-xs text-zinc-500">Зелёным — в пределах ±10% от нормы {{ format_int($targets->kcal) }} ккал, жёлтым — недобор, красным — перебор.</p>
            @endif
        </section>

        {{-- Вес --}}
        <section class="card space-y-3" aria-label="Вес">
            <div class="flex items-baseline justify-between">
                <h2 class="h2">Вес</h2>
                @if ($weightDelta !== null)
                    <span class="text-xs text-zinc-500">за период: {{ $weightDelta > 0 ? '+' : '' }}{{ format_kg($weightDelta) }} кг</span>
                @endif
            </div>
            @if ($profile->weightKg)
                <div class="grid grid-cols-2 gap-2 text-center">
                    <div class="rounded-xl bg-zinc-800 p-2">
                        <div class="text-lg font-bold">{{ format_kg($profile->weightKg) }} кг</div>
                        <div class="text-xs text-zinc-400">текущий вес</div>
                    </div>
                    <div class="rounded-xl bg-zinc-800 p-2">
                        @if ($bmi)
                            <div class="text-lg font-bold">{{ format_kg($bmi) }}</div>
                            <div class="text-xs text-zinc-400">ИМТ · {{ mb_strtolower($bmiCategory['label']) }}</div>
                        @else
                            <div class="text-lg font-bold">—</div>
                            <div class="text-xs text-zinc-400">ИМТ: укажите рост в профиле</div>
                        @endif
                    </div>
                </div>
            @endif
            @if ($weights->count() >= 2)
                <div class="relative h-48"><canvas id="chart-body-weight" aria-label="График веса"></canvas></div>
            @else
                <p class="muted">График появится после двух записей веса.</p>
            @endif

            <form method="POST" action="{{ route('nutrition.weight.store') }}" class="flex items-end gap-2">
                @csrf
                <div class="flex-1">
                    <label class="label" for="measured_on">Дата</label>
                    <input id="measured_on" type="date" name="measured_on" value="{{ old('measured_on', $today->toDateString()) }}" max="{{ $today->toDateString() }}" class="input min-h-[3rem]" required>
                </div>
                <div class="w-24">
                    <label class="label" for="weight_kg">Вес, кг</label>
                    <input id="weight_kg" type="text" inputmode="decimal" name="weight_kg" value="{{ old('weight_kg') }}" class="input min-h-[3rem]" required>
                </div>
                <button class="btn-primary">Записать</button>
            </form>

            @if ($weights->isNotEmpty())
                <details>
                    <summary class="cursor-pointer text-sm text-zinc-400">Записи веса ({{ $weights->count() }})</summary>
                    <ul class="mt-2">
                        @foreach ($weights->reverse() as $entry)
                            <li class="flex items-center justify-between gap-2 border-b border-zinc-800 py-1.5 text-sm last:border-0">
                                <span class="text-zinc-400">{{ $entry->measured_on->format('d.m.Y') }}</span>
                                <span class="ml-auto tabular-nums">{{ format_kg($entry->weight_kg) }} кг</span>
                                <form method="POST" action="{{ route('nutrition.weight.destroy', $entry->id) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn-ghost btn-sm !min-h-[2.25rem] !px-2 text-zinc-500" aria-label="Удалить запись за {{ $entry->measured_on->format('d.m.Y') }}">✕</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </section>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const axis = { ticks: { color: '#a1a1aa' }, grid: { color: '#27272a' } };
                const days = @json($chartDays);
                const target = @json($targets?->kcal);
                const datasets = [{
                    type: 'bar',
                    label: 'Съедено, ккал',
                    data: days.map(d => d.kcal),
                    backgroundColor: days.map(d => target && d.kcal > target * 1.1 ? '#f87171' : '#a3e635'),
                    borderRadius: 4,
                }];
                if (target) {
                    datasets.push({
                        type: 'line', label: 'Норма', data: days.map(() => target),
                        borderColor: '#facc15', borderDash: [6, 4], borderWidth: 2, pointRadius: 0,
                    });
                }
                new window.Chart(document.getElementById('chart-kcal'), {
                    data: { labels: days.map(d => d.label), datasets },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: !!target, labels: { color: '#a1a1aa', boxWidth: 12 } } },
                        scales: { x: axis, y: { ...axis, beginAtZero: true } },
                    },
                });

                const weightCanvas = document.getElementById('chart-body-weight');
                if (weightCanvas) {
                    const points = @json($weightPoints);
                    new window.Chart(weightCanvas, {
                        type: 'line',
                        data: { labels: points.map(p => p.date), datasets: [{ data: points.map(p => p.kg), borderColor: '#38bdf8', backgroundColor: '#38bdf8', tension: 0.2, pointRadius: 3 }] },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: { x: axis, y: axis },
                        },
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>
