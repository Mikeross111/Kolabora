<?php

namespace Tests\Feature\AI;

use App\Models\ChatMessage;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Phase1\Concerns\BuildsPhase1Data;
use Tests\TestCase;

/**
 * alur Kora dari awal sampai akhir dengan Gemini ditiru: saring konten, cari dokumen, jawab, simpan pesan.
 * regresi: variabel pesan asisten dan metadata model pernah rusak tanpa terdeteksi karena alur ini tidak diuji.
 */
class KoraChatFlowTest extends TestCase
{
    use BuildsPhase1Data;
    use RefreshDatabase;

    public function test_message_flow_saves_the_assistant_reply_with_model_metadata(): void
    {
        config(['services.gemini.api_key' => 'kunci-uji', 'services.gemini.model_chat' => 'model-uji', 'services.llm.provider' => 'gemini']);
        $this->seedRegion();
        $student = $this->makeStudent('Alya', 'Putri');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push(['candidates' => [['content' => ['parts' => [['text' => '{"is_appropriate": true, "reason": "", "category": "ok"}']]]]]])
                ->push(['candidates' => [['content' => ['parts' => [['text' => 'Halo, saya Kora. Informasi tidak tersedia dalam dokumentasi Kolabora.']]]], 'usageMetadata' => ['promptTokenCount' => 10, 'candidatesTokenCount' => 7]]]),
            'api.cohere.ai/*' => Http::response(['embeddings' => [array_fill(0, 8, 0.1)]]),
        ]);

        $service = app(ChatbotService::class);
        $conversation = $service->createConversation($student->user, 'Uji');
        $result = $service->sendMessage($conversation, 'Apa itu bank sampah di desa?');

        $this->assertTrue($result['success'], json_encode($result));
        $this->assertStringContainsString('Kora', $result['message']);

        $reply = ChatMessage::where('conversation_id', $conversation->id)->where('role', 'assistant')->latest('id')->first();
        $this->assertNotNull($reply);
        $this->assertSame('model-uji', $reply->metadata['model']);
        $this->assertNotSame("", trim((string) $reply->content));
    }
}
