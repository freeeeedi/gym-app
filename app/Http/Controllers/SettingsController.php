<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTrainingSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('settings', ['settings' => $request->user()->trainingSettings()]);
    }

    public function update(UpdateTrainingSettingsRequest $request): RedirectResponse
    {
        $request->user()->update(['training_settings' => $request->validated()]);

        return back()->with('status', 'Настройки сохранены.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->user()->update(['training_settings' => null]);

        return back()->with('status', 'Настройки сброшены к значениям по умолчанию.');
    }
}
