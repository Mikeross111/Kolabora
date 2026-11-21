<?php

namespace App\Services\AI;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * satu pintu ke model bahasa. penyedia dipilih lewat AI_PROVIDER (bawaan: gemini, tersedia paket gratis);
 * anthropic tetap didukung. semua layanan AI memanggil kelas ini, bukan API penyedia secara langsung.
 *
 * tier: 'chat' (jawaban panjang, Kora, verifikasi KTP/NPWP) atau 'fast' (tugas ringan dan murah).
 */
class LlmClient
{
    public function provider(): string
    {
        return config('services.llm.provider', 'gemini') === 'anthropic' ? 'anthropic' : 'gemini';
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey());
    }

    /**
     * @param  array{system?: string, max_tokens?: int, timeout?: int, tier?: string, attachment?: array{mime: string, data: string}}  $options  attachment.data = base64
     * @return array{content: string, usage: ?array}
     */
    public function complete(string $prompt, array $options = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Kunci API AI belum diatur ('.$this->provider().')');
        }

        return $this->provider() === 'anthropic'
            ? $this->viaAnthropic($prompt, $options)
            : $this->viaGemini($prompt, $options);
    }

    /**
     * sama dengan complete(), tetapi bentuk jawabannya meniru Anthropic (content[0].text) untuk kode lama yang membacanya begitu
     *
     * @return array{content: array<int, array{type: string, text: string}>, usage: ?array}
     */
    public function completeShaped(string $prompt, array $options = []): array
    {
        $r = $this->complete($prompt, $options);

        return ['content' => [['type' => 'text', 'text' => $r['content']]], 'usage' => $r['usage']];
    }

    private function apiKey(): ?string
    {
        return $this->provider() === 'anthropic'
            ? config('services.claude.api_key')
            : config('services.gemini.api_key');
    }

    public function model(string $tier = "chat"): string
    {
        $key = $tier === 'fast' ? 'model_fast' : 'model_chat';

        return (string) config('services.'.($this->provider() === 'anthropic' ? 'claude' : 'gemini').'.'.$key);
    }

    private function http(int $timeout): PendingRequest
    {
        $http = Http::timeout($timeout)->retry(2, 2000, fn ($e) => in_array(optional($e->response ?? null)->status(), [429, 503], true), throw: false);

        return config('app.env') === 'local' ? $http->withoutVerifying() : $http;
    }

    private function viaGemini(string $prompt, array $o): array
    {
        $model = $this->model($o['tier'] ?? 'chat');
        $parts = [];
        if (! empty($o['attachment'])) {
            $parts[] = ['inline_data' => ['mime_type' => $o['attachment']['mime'], 'data' => $o['attachment']['data']]];
        }
        $parts[] = ['text' => $prompt];

        $body = [
            'contents' => [['role' => 'user', 'parts' => $parts]],
            'generationConfig' => ['maxOutputTokens' => $o['max_tokens'] ?? 4096],
        ];
        // keluarga 2.5: matikan "thinking" agar anggaran token dipakai untuk jawaban, bukan penalaran
        if (str_contains($model, '2.5')) {
            $body['generationConfig']['thinkingConfig'] = ['thinkingBudget' => 0];
        }
        if (! empty($o['system'])) {
            $body['systemInstruction'] = ['parts' => [['text' => $o['system']]]];
        }

        $url = rtrim((string) config('services.gemini.base_url'), '/')."/models/{$model}:generateContent";
        $response = $this->http($o['timeout'] ?? 120)
            ->withHeaders(['x-goog-api-key' => $this->apiKey(), 'Content-Type' => 'application/json'])
            ->post($url, $body);

        if (! $response->successful()) {
            Log::error('Gemini API gagal', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('Gemini API gagal: '.$response->status());
        }

        $data = $response->json();
        $text = collect($data['candidates'][0]['content']['parts'] ?? [])->pluck('text')->filter()->implode('');
        if ($text === '') {
            Log::warning('Gemini tidak mengembalikan teks', ['finish' => $data['candidates'][0]['finishReason'] ?? null, 'feedback' => $data['promptFeedback'] ?? null]);
            throw new RuntimeException('Gemini tidak mengembalikan jawaban');
        }

        $u = $data['usageMetadata'] ?? null;

        return [
            'content' => $text,
            'usage' => $u ? ['input_tokens' => $u['promptTokenCount'] ?? null, 'output_tokens' => $u['candidatesTokenCount'] ?? null] : null,
        ];
    }

    private function viaAnthropic(string $prompt, array $o): array
    {
        $content = [];
        if (! empty($o['attachment'])) {
            $content[] = [
                'type' => $o['attachment']['mime'] === 'application/pdf' ? 'document' : 'image',
                'source' => ['type' => 'base64', 'media_type' => $o['attachment']['mime'], 'data' => $o['attachment']['data']],
            ];
        }
        $content[] = ['type' => 'text', 'text' => $prompt];

        $body = [
            'model' => $this->model($o['tier'] ?? 'chat'),
            'max_tokens' => $o['max_tokens'] ?? 4096,
            'messages' => [['role' => 'user', 'content' => $content]],
        ];
        if (! empty($o['system'])) {
            $body['system'] = $o['system'];
        }

        $response = $this->http($o['timeout'] ?? 120)
            ->withHeaders(['x-api-key' => $this->apiKey(), 'anthropic-version' => '2023-06-01', 'content-type' => 'application/json'])
            ->post('https://api.anthropic.com/v1/messages', $body);

        if (! $response->successful()) {
            Log::error('Anthropic API gagal', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('Anthropic API gagal: '.$response->status());
        }

        $data = $response->json();

        return ['content' => (string) ($data['content'][0]['text'] ?? ''), 'usage' => $data['usage'] ?? null];
    }
}
