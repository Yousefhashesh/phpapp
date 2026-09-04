<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'setting.page');

        try {
            $token = $this->token();
            $url = $this->serviceUrl();

            if (! $url || ! $token) {
                return response()->json([
                    'ready' => false,
                    'message' => 'WhatsApp free bridge is not configured yet. Set WHATSAPP_SERVICE_URL (e.g. http://127.0.0.1:3001) and WHATSAPP_API_SECRET, then run whatsapp-service.',
                    'status' => 'unconfigured',
                ]);
            }

            $response = Http::timeout(15)
                ->withHeaders(['X-Api-Secret' => $token])
                ->get("{$url}/status");

            if (! $response->successful()) {
                return response()->json([
                    'ready' => false,
                    'message' => $response->json('message') ?? $response->json('error') ?? 'WhatsApp service returned an error.',
                    'status_code' => $response->status(),
                    'body' => $response->json(),
                ], 502);
            }

            $data = $response->json();

            return response()->json([
                'ready' => ! empty($data['ready']),
                'has_qr' => ! empty($data['hasQr']),
                'status' => $data['status'] ?? null,
                'last_error' => $response->json('error') ?? null,
                'last_disconnect_reason' => null,
                'api_secret_configured' => filled($token),
                'started_at' => null,
                'default_group_id' => Setting::getValue('whatsapp_group_id'),
                'configured_group_id' => Setting::getValue('whatsapp_group_id'),
                'enabled' => Setting::getValue('whatsapp_enabled'),
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'ready' => false,
                'message' => 'WhatsApp free bridge is not running or unreachable. Start whatsapp-service (PM2).',
                'error' => $exception->getMessage(),
            ], 503);
        }
    }

    public function restart(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'setting.page');

        try {
            $token = $this->token();
            $url = $this->serviceUrl();

            $response = Http::timeout(15)
                ->withHeaders(['X-Api-Secret' => $token])
                ->post("{$url}/restart");

            return response()->json($response->json() ?? [
                'success' => $response->successful(),
                'message' => $response->body(),
            ], $response->status());
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp free bridge is not running or unreachable.',
                'error' => $exception->getMessage(),
            ], 503);
        }
    }

    public function qr(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'setting.page');

        try {
            $token = $this->token();
            $url = $this->serviceUrl();

            $response = Http::timeout(15)
                ->withHeaders(['X-Api-Secret' => $token])
                ->get("{$url}/qr");

            if (! $response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'WhatsApp service returned an error.',
                ], 502);
            }

            return response()->json($response->json());
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp free bridge is not running or unreachable.',
                'error' => $exception->getMessage(),
            ], 503);
        }
    }

    public function groups(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'setting.page');

        try {
            $token = $this->token();
            $url = $this->serviceUrl();

            $response = Http::timeout(20)
                ->withHeaders(['X-Api-Secret' => $token])
                ->get("{$url}/groups");

            if ($response->status() === 503) {
                return response()->json([
                    'success' => false,
                    'message' => 'WhatsApp client is not ready yet. Scan the QR from Settings.',
                ], 503);
            }

            if (! $response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'WhatsApp service returned an error.',
                ], 502);
            }

            $groups = $response->json();
            $formattedGroups = [];
            $groupsList = $groups['groups'] ?? [];

            if (is_array($groupsList)) {
                foreach ($groupsList as $group) {
                    if (isset($group['id'], $group['name'])) {
                        $formattedGroups[] = [
                            'id' => $group['id'],
                            'name' => $group['name'],
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'groups' => $formattedGroups,
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp free bridge is not running or unreachable.',
                'error' => $exception->getMessage(),
            ], 503);
        }
    }

    private function serviceUrl(): string
    {
        return rtrim((string) Setting::getValue('whatsapp_service_url', config('whatsapp.service_url', '')), '/');
    }

    private function token(): string
    {
        return (string) Setting::getValue('whatsapp_api_secret', config('whatsapp.api_secret', ''));
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }
}
