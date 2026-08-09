<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the Anthropic Messages API (api.anthropic.com/v1/messages).
 * Mirrors SmsService/PaystackService's shape (dependency-free, uses Laravel's
 * Http facade) for consistency with the rest of the app.
 *
 * Unlike SmsService, this DOES throw on missing config - a research
 * assistant response that silently degrades to nothing would look like a
 * generic failure to the teacher, when the real problem is a setup issue
 * the platform admin needs to fix. Callers (TeachingMaterialGenerationService)
 * should let that exception surface as a 'failed' research_requests row
 * rather than swallowing it.
 */
class ClaudeClient
{
    private const BASE_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';

    private string $apiKey;
    private string $model;
    private int $maxTokens;

    public function __construct()
    {
        $apiKey = config('services.anthropic.api_key');

        if (empty($apiKey)) {
            throw new \RuntimeException(
                'ANTHROPIC_API_KEY is not set. Add it to .env - see config/services.php.'
            );
        }

        $this->apiKey = $apiKey;
        $this->model = config('services.anthropic.model');
        $this->maxTokens = (int) config('services.anthropic.max_tokens');
    }

    /**
     * Sends a single-turn request with a system prompt and returns the
     * concatenated text of the response. Deliberately narrow (no
     * multi-turn history, no tool use) - the research assistant is a
     * one-shot "here's your grounding context + request, generate the
     * material" call, not a conversation. Widen this if a future feature
     * genuinely needs multi-turn.
     */
    public function generate(string $systemPrompt, string $userMessage): string
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => self::API_VERSION,
                'content-type' => 'application/json',
            ])->timeout(120)->post(self::BASE_URL, [
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

            if (! $response->successful()) {
                Log::error('ClaudeClient: API request failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \RuntimeException("Anthropic API request failed with status {$response->status()}.");
            }

            $blocks = $response->json('content', []);

            $text = collect($blocks)
                ->where('type', 'text')
                ->pluck('text')
                ->implode("\n");

            if ($text === '') {
                Log::warning('ClaudeClient: response contained no text blocks.', ['blocks' => $blocks]);
            }

            return $text;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('ClaudeClient: connection exception.', ['message' => $e->getMessage()]);

            throw new \RuntimeException('Could not reach the Anthropic API. Check network connectivity.', previous: $e);
        }
    }
}
