<?php

namespace App\Http\Controllers;

use App\Http\Requests\DayTemplateRequest;
use App\Models\DayTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DayTemplateController extends Controller
{
    public function index(Request $request): View
    {
        return view('templates.index', [
            'templates' => DayTemplate::visibleTo($request->user())->with('muscles')->orderByRaw('user_id is null')->orderBy('id')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        // ?from=ID — создать копию стандартного шаблона.
        $source = $request->integer('from') ? DayTemplate::with('muscles')->find($request->integer('from')) : null;
        if ($source) {
            $this->authorize('view', $source);
        }

        return view('templates.form', [
            'template' => new DayTemplate([
                'name' => $source ? $source->name.' (моя версия)' : '',
                'description' => $source?->description,
                'patterns' => $source?->patterns ?? [],
            ]),
            'selectedMuscles' => $source?->muscleSlugs() ?? [],
        ]);
    }

    public function store(DayTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $template = $request->user()->dayTemplates()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'patterns' => array_values($data['patterns'] ?? []),
        ]);
        $template->syncMuscles($data['muscles']);

        return redirect()->route('templates.index')->with('status', 'Шаблон создан.');
    }

    public function edit(DayTemplate $template): View
    {
        $this->authorize('update', $template);
        $template->load('muscles');

        return view('templates.form', ['template' => $template, 'selectedMuscles' => $template->muscleSlugs()]);
    }

    public function update(DayTemplateRequest $request, DayTemplate $template): RedirectResponse
    {
        $data = $request->validated();
        $template->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'patterns' => array_values($data['patterns'] ?? []),
        ]);
        $template->syncMuscles($data['muscles']);

        return redirect()->route('templates.index')->with('status', 'Шаблон сохранён.');
    }

    public function destroy(DayTemplate $template): RedirectResponse
    {
        $this->authorize('delete', $template);
        $template->delete();

        return redirect()->route('templates.index')->with('status', 'Шаблон удалён. Тренировки по нему сохранились.');
    }
}
