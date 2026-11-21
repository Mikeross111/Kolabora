<?php

namespace Tests\Feature\AI;

use App\Services\AI\LlmClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * klien model bahasa: Gemini sebagai bawaan, Anthropic sebagai cadangan. semua HTTP ditiru, tanpa jaringan.
 */
class LlmClientTest extends TestCase
{
    public function test_default_provider_is_gemini_and_unconfigured_without_key(): void
    {
        config(['services.gemini.api_key' => null]);
        $client = new LlmClient;

        $this->assertSame('gemini', $client->provider());
        $this->assertFalse($client->isConfigured());
        $this->expectException(RuntimeException::class);
        $client->complete('halo');
    }

    public function test_gemini_request_shape_and_response_parsing(): void
    {
        config(['services.gemini.api_key' => 'kunci-uji', 'services.gemini.model_chat' => 'gemini-2.5-flash']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Jawaban '], ['text' => 'uji']]]]],
                'usageMetadata' => ['promptTokenCount' => 12, 'candidatesTokenCount' => 5],
            ]),
        ]);

        $r = (new LlmClient)->complete('Apa itu KKN?', ['system' => 'Jawab singkat.', 'max_tokens' => 321]);

        $this->assertSame('Jawaban uji', $r['content']);
        $this->assertSame(['input_tokens' => 12, 'output_tokens' => 5], $r['usage']);
        Http::assertSent(function (Request $req) {
            $b = $req->data();

            return str_contains($req->url(), '/models/gemini-2.5-flash:generateContent')
                && $req->hasHeader('x-goog-api-key', 'kunci-uji')
                && $b['contents'][0]['parts'][0]['text'] === 'Apa itu KKN?'
                && $b['systemInstruction']['parts'][0]['text'] === 'Jawab singkat.'
                && $b['generationConfig']['maxOutputTokens'] === 321
                && $b['generationConfig']['thinkingConfig']['thinkingBudget'] === 0;
        });
    }

    public function test_gemini_attachment_and_fast_tier(): void
    {
        config(['services.gemini.api_key' => 'k', 'services.gemini.model_fast' => 'gemini-2.5-flash-lite']);
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => '{"score":90}']]]]]])]);

        $shaped = (new LlmClient)->completeShaped('periksa', ['tier' => 'fast', 'attachment' => ['mime' => 'image/png', 'data' => 'QUJD']]);

        $this->assertSame('{"score":90}', $shaped['content'][0]['text']);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'gemini-2.5-flash-lite')
            && $r->data()['contents'][0]['parts'][0]['inline_data'] === ['mime_type' => 'image/png', 'data' => 'QUJD']);
    }

    public function test_gemini_error_and_empty_answer_raise(): void
    {
        config(['services.gemini.api_key' => 'k']);
        Http::fake(['*' => Http::response(['error' => 'x'], 400)]);
        try {
            (new LlmClient)->complete('x');
            $this->fail('harus melempar');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('400', $e->getMessage());
        }

        Http::fake(['*' => Http::response(['candidates' => [['finishReason' => 'SAFETY']]])]);
        $this->expectException(RuntimeException::class);
        (new LlmClient)->complete('x');
    }

    public function test_anthropic_provider_still_works(): void
    {
        config(['services.llm.provider' => 'anthropic', 'services.claude.api_key' => 'kunci-a', 'services.claude.model_fast' => 'claude-haiku-5-5']);
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'Halo']], 'usage' => ['input_tokens' => 3, 'output_tokens' => 1]])]);

        $r = (new LlmClient)->complete('hai', ['tier' => 'fast', 'system' => 'S', 'attachment' => ['mime' => 'application/pdf', 'data' => 'QQ==']]);

        $this->assertSame('Halo', $r['content']);
        Http::assertSent(function (Request $req) {
            $b = $req->data();

            return $req->hasHeader('x-api-key', 'kunci-a')
                && $b['model'] === 'claude-haiku-5-5'
                && $b['system'] === 'S'
                && $b['messages'][0]['content'][0]['type'] === 'document';
        });
    }
}
