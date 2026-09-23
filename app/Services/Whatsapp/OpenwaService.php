<?php

namespace App\Services\Whatsapp;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class OpenwaService
{
    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('openwa.base_url'), '/'))
            ->withHeaders(['X-API-Key' => (string) config('openwa.api_key')])
            ->timeout((int) config('openwa.timeout', 20))
            ->throw();
    }

    /**
     * @return array{id: string, name: string, status: string}
     */
    public function createSession(string $name): array
    {
        return $this->call(fn () => $this->client()->post('/sessions', ['name' => $name]));
    }

    public function startSession(string $sessionId): array
    {
        return $this->call(fn () => $this->client()->post("/sessions/{$sessionId}/start"));
    }

    /**
     * @return array{qrCode: string, status: string}
     */
    public function getQr(string $sessionId): array
    {
        return $this->call(fn () => $this->client()->get("/sessions/{$sessionId}/qr"));
    }

    public function getStatus(string $sessionId): array
    {
        return $this->call(fn () => $this->client()->get("/sessions/{$sessionId}"));
    }

    public function stopSession(string $sessionId): void
    {
        $this->call(fn () => $this->client()->post("/sessions/{$sessionId}/stop"));
    }

    public function logoutSession(string $sessionId): void
    {
        $this->call(fn () => $this->client()->post("/sessions/{$sessionId}/logout"));
    }

    public function deleteSession(string $sessionId): void
    {
        $this->call(fn () => $this->client()->delete("/sessions/{$sessionId}"));
    }

    /**
     * @return array{messageId: string, timestamp: int}
     */
    public function sendText(string $sessionId, string $chatId, string $text): array
    {
        return $this->call(fn () => $this->client()->post("/sessions/{$sessionId}/messages/send-text", [
            'chatId' => $chatId,
            'text' => $text,
        ]));
    }

    private function call(\Closure $request): array
    {
        try {
            return $request()->json() ?? [];
        } catch (RequestException $e) {
            $body = (array) ($e->response->json() ?? []);

            throw new OpenwaException(
                (string) ($body['message'] ?? $e->getMessage()),
                (int) $e->response->status(),
                isset($body['error']) ? (string) $body['error'] : null,
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new OpenwaException('Tidak bisa terhubung ke server WhatsApp: '.$e->getMessage());
        }
    }
}
