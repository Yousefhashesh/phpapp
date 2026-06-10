<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginSession;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if ($token) {
            $sessionId = $token->id;
            LoginSession::query()
                ->where('user_id', $user->id)
                ->where('session_id', $sessionId)
                ->update([
                    'logout_at' => now(),
                    'is_active' => false,
                    'is_current' => false,
                ]);

            $token->delete();
        }

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function logoutSession(Request $request, LoginSession $session): JsonResponse
    {
        $user = $request->user();
        
        // Authorization: User can logout their own session, or admin can logout any
        if ($session->user_id !== $user->id && !$user->can('user.update')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $session->update([
            'logout_at' => now(),
            'is_active' => false,
            'is_current' => false,
        ]);

        // Revoke sanctum token
        if ($session->session_id) {
            \Illuminate\Support\Facades\DB::table('personal_access_tokens')
                ->where('id', $session->session_id)
                ->delete();
        }

        return response()->json(['message' => 'Session logged out successfully.']);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()
            ->where('username', $credentials['login'])
            ->orWhere('phone', $credentials['login'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        if ($user->is_blocked) {
            return response()->json([
                'message' => 'This account is blocked.',
            ], 403);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'api-token')->plainTextToken;
        $sessionId = explode('|', $token)[0] ?? null;

        $ipAddress = (string) $request->ip();
        $userAgent = (string) $request->userAgent();
        $deviceName = $credentials['device_name'] ?? 'unknown-device';
        $location = $this->resolveGeoLocationFromIp($ipAddress);

        $user->loginSessions()->update(['is_current' => false]);

        LoginSession::query()->create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_name' => $deviceName,
            'device_type' => $this->detectDeviceType($userAgent),
            'browser' => $this->detectBrowser($userAgent),
            'platform' => $this->detectPlatform($userAgent),
            'country' => $location['country'],
            'city' => $location['city'],
            'login_at' => now(),
            'last_seen_at' => now(),
            'logout_at' => null,
            'is_active' => true,
            'is_current' => true,
        ]);

        $user->load('roles:id,name');

        return response()->json([
            'message' => 'Login successful.',
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => array_merge($user->toArray(), [
                'role' => $user->roles->first()?->name,
                'roles' => $user->roles->map(static fn ($role): array => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])->values()->all(),
            ]),
        ]);
    }

    private function detectDeviceType(string $userAgent): string
    {
        $agent = strtolower($userAgent);

        if (str_contains($agent, 'mobile') || str_contains($agent, 'android') || str_contains($agent, 'iphone')) {
            return 'mobile';
        }

        if (str_contains($agent, 'tablet') || str_contains($agent, 'ipad')) {
            return 'tablet';
        }

        return 'desktop';
    }

    private function detectBrowser(string $userAgent): string
    {
        $agent = strtolower($userAgent);

        return match (true) {
            str_contains($agent, 'edg') => 'Edge',
            str_contains($agent, 'opr') || str_contains($agent, 'opera') => 'Opera',
            str_contains($agent, 'chrome') => 'Chrome',
            str_contains($agent, 'firefox') => 'Firefox',
            str_contains($agent, 'safari') => 'Safari',
            default => 'Unknown',
        };
    }

    private function detectPlatform(string $userAgent): string
    {
        $agent = strtolower($userAgent);

        return match (true) {
            str_contains($agent, 'windows') => 'Windows',
            str_contains($agent, 'mac os') || str_contains($agent, 'macintosh') => 'macOS',
            str_contains($agent, 'android') => 'Android',
            str_contains($agent, 'iphone') || str_contains($agent, 'ipad') || str_contains($agent, 'ios') => 'iOS',
            str_contains($agent, 'linux') => 'Linux',
            default => 'Unknown',
        };
    }

    /**
     * @return array{country:?string,city:?string}
     */
    private function resolveGeoLocationFromIp(string $ipAddress): array
    {
        $publicIp = filter_var(
            $ipAddress,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if (! $publicIp) {
            return ['country' => null, 'city' => null];
        }

        try {
            $response = Http::timeout(3)
                ->acceptJson()
                ->get("http://ip-api.com/json/{$publicIp}", [
                    'fields' => 'status,country,city',
                ]);

            if (! $response->successful()) {
                return ['country' => null, 'city' => null];
            }

            $data = $response->json();

            if (($data['status'] ?? null) !== 'success') {
                return ['country' => null, 'city' => null];
            }

            return [
                'country' => $data['country'] ?? null,
                'city' => $data['city'] ?? null,
            ];
        } catch (\Throwable) {
            return ['country' => null, 'city' => null];
        }
    }
}
