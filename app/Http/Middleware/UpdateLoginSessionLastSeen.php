<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLoginSessionLastSeen
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (! $user || ! $token) {
            return $response;
        }

        $sessionId = (string) $token->id;
        $userAgent = (string) $request->userAgent();

        $updated = $user->loginSessions()
            ->where('session_id', $sessionId)
            ->update([
                'ip_address' => $request->ip(),
                'user_agent' => $userAgent,
                'last_seen_at' => now(),
                'is_active' => true,
                'is_current' => true,
            ]);

        if ($updated === 0) {
            $user->loginSessions()->create([
                'session_id' => $sessionId,
                'ip_address' => $request->ip(),
                'user_agent' => $userAgent,
                'device_name' => $token->name,
                'device_type' => $this->detectDeviceType($userAgent),
                'browser' => $this->detectBrowser($userAgent),
                'platform' => $this->detectPlatform($userAgent),
                'country' => null,
                'city' => null,
                'login_at' => now(),
                'last_seen_at' => now(),
                'logout_at' => null,
                'is_active' => true,
                'is_current' => true,
            ]);
        }

        $user->loginSessions()
            ->where('session_id', '!=', $sessionId)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        return $response;
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
}
