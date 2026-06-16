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
                    'message' => 'WhatsApp API service is not configured yet. Please check WHATSAPP_SERVICE_URL and WHATSAPP_API_SECRET in your settings/environment.',
                    'status' => 'unconfigured',
                ]);
            }

            $isUltraMsg = str_contains(strtolower($url), 'ultramsg.com');

            if ($isUltraMsg) {
                $response = Http::timeout(15)->get("{$url}/instance/status", [
                    'token' => $token,
                ]);
            } else {
                $response = Http::timeout(15)
                    ->withHeaders(['X-Api-Secret' => $token])
                    ->get("{$url}/status");
            }

            if (! $response->successful()) {
                return response()->json([
                    'ready' => false,
                    'message' => $response->json('message') ?? $response->json('error') ?? 'WhatsApp service returned an error.',
                    'status_code' => $response->status(),
                    'body' => $response->json(),
                ], 502);
            }

            $data = $response->json();
            $accountStatus = null;
            $ready = false;
            $hasQr = false;

            if ($isUltraMsg) {
                if (is_array($data)) {
                    if (isset($data['accountStatus'])) {
                        $accountStatus = is_array($data['accountStatus']) 
                            ? ($data['accountStatus']['status'] ?? null) 
                            : $data['accountStatus'];
                    } elseif (isset($data['status']['accountStatus'])) {
                        $accountStatus = is_array($data['status']['accountStatus']) 
                            ? ($data['status']['accountStatus']['status'] ?? null) 
                            : $data['status']['accountStatus'];
                    }
                }

                if ($accountStatus === null && is_array($data) && isset($data['status']['status'])) {
                    $accountStatus = $data['status']['status'];
                }

                $ready = in_array($accountStatus, ['authenticated', 'standby'], true);
                $hasQr = ! $ready;
            } else {
                $ready = !empty($data['ready']);
                $hasQr = !empty($data['hasQr']);
                $accountStatus = $data['status'] ?? null;
            }

            return response()->json([
                'ready' => $ready,
                'has_qr' => $hasQr,
                'status' => $accountStatus,
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
                'message' => 'WhatsApp service is not running or unreachable.',
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

            $isUltraMsg = str_contains(strtolower($url), 'ultramsg.com');

            if ($isUltraMsg) {
                $response = Http::timeout(15)->post("{$url}/instance/restart", [
                    'token' => $token,
                ]);
            } else {
                $response = Http::timeout(15)
                    ->withHeaders(['X-Api-Secret' => $token])
                    ->post("{$url}/restart");
            }

            return response()->json($response->json() ?? [
                'success' => $response->successful(),
                'message' => $response->body(),
            ], $response->status());
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp service is not running or unreachable.',
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

            $isUltraMsg = str_contains(strtolower($url), 'ultramsg.com');

            if ($isUltraMsg) {
                $response = Http::timeout(15)->get("{$url}/instance/qr", [
                    'token' => $token,
                ]);

                if (! $response->successful()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'WhatsApp service returned an error.',
                    ], 502);
                }

                return response()->json([
                    'success' => true,
                    'qr' => 'data:image/png;base64,' . base64_encode($response->body()),
                ]);
            } else {
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
            }
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp service is not running or unreachable.',
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

            $isUltraMsg = str_contains(strtolower($url), 'ultramsg.com');

            if ($isUltraMsg) {
                $response = Http::timeout(20)->get("{$url}/groups", [
                    'token' => $token,
                ]);
            } else {
                $response = Http::timeout(20)
                    ->withHeaders(['X-Api-Secret' => $token])
                    ->get("{$url}/groups");
            }

            if ($response->status() === 503) {
                return response()->json([
                    'success' => false,
                    'message' => 'WhatsApp client is not ready yet.',
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

            if ($isUltraMsg) {
                if (is_array($groups)) {
                    foreach ($groups as $group) {
                        if (isset($group['id']) && isset($group['name'])) {
                            $formattedGroups[] = [
                                'id' => $group['id'],
                                'name' => $group['name'],
                            ];
                        }
                    }
                }
            } else {
                $groupsList = $groups['groups'] ?? [];
                if (is_array($groupsList)) {
                    foreach ($groupsList as $group) {
                        if (isset($group['id']) && isset($group['name'])) {
                            $formattedGroups[] = [
                                'id' => $group['id'],
                                'name' => $group['name'],
                            ];
                        }
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
                'message' => 'WhatsApp service is not running or unreachable.',
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