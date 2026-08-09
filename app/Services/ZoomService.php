<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around Zoom's Server-to-Server OAuth API. Mirrors
 * ClaudeClient/SmsService's shape (dependency-free, Laravel's Http
 * facade) for consistency with the rest of the app.
 *
 * Unlike ClaudeClient, this does NOT throw on missing config - Zoom is
 * one of three virtual-class platform options (see the virtual_classes
 * migration), not the only path. A school without Zoom credentials set
 * still has Jitsi (zero setup) and pasting their own link. Callers should
 * check isConfigured() first and offer the Zoom option only when true -
 * see VirtualClassController.
 */
class ZoomService
{
    private const TOKEN_URL = 'https://zoom.us/oauth/token';
    private const API_BASE = 'https://api.zoom.us/v2';

    public function isConfigured(): bool
    {
        return filled(config('services.zoom.account_id'))
            && filled(config('services.zoom.client_id'))
            && filled(config('services.zoom.client_secret'));
    }

    /**
     * @return array{join_url: string, meeting_id: string}
     */
    public function createMeeting(string $topic, \DateTimeInterface $start, int $durationMinutes): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Zoom is not configured. Add ZOOM_ACCOUNT_ID, ZOOM_CLIENT_ID, and ZOOM_CLIENT_SECRET to .env.');
        }

        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->timeout(30)
            ->post(self::API_BASE.'/users/me/meetings', [
                'topic' => $topic,
                'type' => 2, // scheduled meeting
                'start_time' => $start->format('Y-m-d\TH:i:s'),
                'duration' => $durationMinutes,
                'timezone' => config('app.timezone'),
                'settings' => [
                    'join_before_host' => true, // students can wait in the meeting even if the teacher joins a minute late
                    'waiting_room' => false,
                    'approval_type' => 2, // no registration required - the join_url alone is enough
                ],
            ]);

        if (! $response->successful()) {
            Log::error('ZoomService: failed to create meeting.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Zoom rejected the meeting request. Check your Zoom app credentials and scopes.');
        }

        return [
            'join_url' => $response->json('join_url'),
            'meeting_id' => (string) $response->json('id'),
        ];
    }

    // Cached for 55 minutes - Zoom's S2S tokens last 1 hour, and creating
    // meetings is infrequent enough that re-authenticating on every call
    // would just be wasted round trips. Cache::remember rather than a
    // static/instance property since ZoomService isn't a singleton
    // across requests.
    private function getAccessToken(): string
    {
        return Cache::remember('zoom_s2s_access_token', now()->addMinutes(55), function () {
            $response = Http::asForm()
                ->withBasicAuth(config('services.zoom.client_id'), config('services.zoom.client_secret'))
                ->timeout(15)
                ->post(self::TOKEN_URL, [
                    'grant_type' => 'account_credentials',
                    'account_id' => config('services.zoom.account_id'),
                ]);

            if (! $response->successful()) {
                Log::error('ZoomService: failed to obtain access token.', ['status' => $response->status(), 'body' => $response->body()]);

                throw new \RuntimeException('Could not authenticate with Zoom. Check ZOOM_ACCOUNT_ID/ZOOM_CLIENT_ID/ZOOM_CLIENT_SECRET.');
            }

            return $response->json('access_token');
        });
    }
}
