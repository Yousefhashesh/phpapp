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
            $response = $this->whatsappRequest()->get("{$this->serviceUrl()}/status");

            if (! $response->successful()) {
                return response()->json([
                    'ready' => false,
                    'message' => 'WhatsApp service returned an error.',
                ], 502);
            }

            return response()->json([
                'ready' => (bool) $response->json('ready'),
                'has_qr' => (bool) $response->json('hasQr'),
                'default_group_id' => $response->json('defaultGroupId'),
                'configured_group_id' => Setting::getValue('whatsapp_group_id'),
                'enabled' => Setting::getValue('whatsapp_enabled'),
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'ready' => false,
                'message' => 'WhatsApp service is not running.',
                'error' => $exception->getMessage(),
            ], 503);
        }
    }

    public function qr(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'setting.page');

        try {
            $response = $this->whatsappRequest()->get("{$this->serviceUrl()}/qr");

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
                'message' => 'WhatsApp service is not running.',
                'error' => $exception->getMessage(),
            ], 503);
        }
    }

    public function groups(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'setting.page');

        try {
            $response = $this->whatsappRequest()->get("{$this->serviceUrl()}/groups");

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

            return response()->json($response->json());
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp service is not running.',
                'error' => $exception->getMessage(),
            ], 503);
        }
    }

    private function serviceUrl(): string
    {
        return rtrim((string) Setting::getValue('whatsapp_service_url', config('whatsapp.service_url')), '/');
    }

    private function whatsappRequest(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(10)
            ->withHeaders([
                'X-Api-Secret' => (string) config('whatsapp.api_secret'),
            ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }
}
