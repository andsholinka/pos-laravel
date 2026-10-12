<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSettingRequest;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StoreSettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', ['setting' => StoreSetting::current()]);
    }

    /** Saving the model fires `saved`, which clears the cached settings. */
    public function update(StoreSettingRequest $request): RedirectResponse
    {
        $setting = StoreSetting::query()->firstOrCreate([], ['store_name' => config('app.name')]);
        $data = $request->safe()->except(['logo', 'remove_logo']);
        $oldLogo = $setting->logo_path;

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('settings', 'public');
        } elseif ($request->boolean('remove_logo')) {
            $data['logo_path'] = null;
        }

        $setting->update($data);

        if ($oldLogo && $oldLogo !== $setting->logo_path) {
            Storage::disk('public')->delete($oldLogo);
        }

        return redirect()->route('settings.edit')->with('success', 'Pengaturan toko disimpan.');
    }
}
