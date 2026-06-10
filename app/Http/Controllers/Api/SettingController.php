<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'setting.page');

        $settings = Setting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get(['group', 'key', 'value']);

        $grouped = $settings
            ->groupBy('group')
            ->map(fn ($items): array => $items->pluck('value', 'key')->all())
            ->all();

        return response()->json($grouped);
    }

    public function publicConfig(): JsonResponse
    {
        $settings = Setting::query()
            ->whereIn('group', ['site_identity', 'site_logos', 'site_theme'])
            ->get(['group', 'key', 'value']);

        $grouped = $settings
            ->groupBy('group')
            ->map(fn ($items): array => $items->pluck('value', 'key')->all())
            ->all();

        return response()->json($grouped);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'setting.page');

        $data = $request->validate([
            'settings' => ['sometimes', 'array'],
            'settings.*' => ['nullable'],
        ]);

        $settings = $data['settings'] ?? [];

        // Handle file uploads for branding keys
        $brandingKeys = [
            'site_logo_32_light',
            'site_logo_32_dark',
            'site_logo_512_light',
            'site_logo_512_dark',
        ];

        foreach ($brandingKeys as $key) {
            if ($request->hasFile($key)) {
                $file = $request->file($key);
                $path = $file->store('branding', 'public');
                $settings[$key] = '/storage/' . $path;
            }
        }

        foreach ($settings as $key => $value) {
            Setting::query()
                ->where('key', $key)
                ->update(['value' => $value]);
        }

        $allSettings = Setting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get(['group', 'key', 'value']);

        $grouped = $allSettings
            ->groupBy('group')
            ->map(fn ($items): array => $items->pluck('value', 'key')->all())
            ->all();

        return response()->json([
            'message' => 'Settings updated successfully.',
            'data' => $grouped,
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }
}
