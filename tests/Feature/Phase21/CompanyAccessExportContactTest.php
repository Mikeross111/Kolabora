<?php

namespace Tests\Feature\Phase21;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Phase1\Concerns\BuildsPhase1Data;
use Tests\TestCase;

/**
 * perusahaan hanya mengakses lowongannya sendiri (bukan IDOR), ekspor mengembalikan CSV, dan "Hubungi talenta"
 * mengirim notifikasi ke akun mahasiswa.
 */
class CompanyAccessExportContactTest extends TestCase
{
    use BuildsPhase1Data;
    use RefreshDatabase;

    private function job(int $companyId, string $title): int
    {
        return DB::table('job_postings')->insertGetId([
            'company_id' => $companyId, 'title' => $title, 'slug' => 'uji-' . md5($title),
            'status' => 'posted', 'job_type' => 'internship', 'description' => 'Deskripsi uji',
            'published_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_company_cannot_open_edit_or_list_another_companys_jobs(): void
    {
        $this->seedRegion();
        $a = $this->makeCompany(['name' => 'PT Alfa']);
        $b = $this->makeCompany(['name' => 'PT Beta']);
        $mine = $this->job($a->id, 'Lowongan Milik Alfa');
        $theirs = $this->job($b->id, 'Lowongan Milik Beta');
        $user = User::find($a->user_id);

        $this->actingAs($user)->get("/company/jobs/{$mine}")->assertOk();
        $this->actingAs($user)->get("/company/jobs/{$theirs}")->assertNotFound();
        $this->actingAs($user)->get("/company/jobs/{$theirs}/edit")->assertNotFound();
        $payload = ['title' => 'dibajak', 'department' => 'Produk', 'location' => 'Bandung', 'job_type' => 'internship', 'description' => 'x',
            'responsibilities' => 'x', 'qualifications' => 'x', 'skills' => ['Laravel']];
        $this->actingAs($user)->put("/company/jobs/{$theirs}", $payload)->assertNotFound();
        $this->actingAs($user)->delete("/company/jobs/{$theirs}");
        $this->assertNull(DB::table('job_postings')->where('id', $theirs)->value('deleted_at'), 'lowongan perusahaan lain tidak boleh terhapus');

        $list = $this->actingAs($user)->get('/company/jobs')->assertOk()->getContent();
        $this->assertStringContainsString('Lowongan Milik Alfa', $list);
        $this->assertStringNotContainsString('Lowongan Milik Beta', $list);
        $this->assertSame('Lowongan Milik Beta', DB::table('job_postings')->where('id', $theirs)->value('title'));
    }

    public function test_exports_return_csv_files(): void
    {
        $this->seedRegion();
        $co = $this->makeCompany(['name' => 'PT Ekspor']);
        $user = User::find($co->user_id);

        foreach (['/company/applications/export', '/company/talents/export-saved', '/company/talents/export'] as $path) {
            $response = $this->actingAs($user)->get($path);
            $response->assertOk();
            $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'), $path);
        }
    }

    public function test_contact_talent_sends_a_notification_to_the_student(): void
    {
        $this->seedRegion();
        $co = $this->makeCompany(['name' => 'PT Hubung']);
        $student = $this->makeStudent('Alya', 'Putri');
        $user = User::find($co->user_id);

        $this->actingAs($user)->postJson("/company/talents/{$student->user_id}/contact", ['message' => 'Kami tertarik dengan portofolio Anda.', 'type' => 'interview_request'])
            ->assertOk()->assertJson(['success' => true]);

        $note = Notification::where('user_id', $student->user_id)->where('type', 'talent_contact')->first();
        $this->assertNotNull($note);
        $this->assertStringContainsString('PT Hubung', $note->title);
        $this->assertStringContainsString('Undangan wawancara', $note->title);
        $this->assertSame('Kami tertarik dengan portofolio Anda.', $note->message);
    }

    public function test_contact_talent_validates_the_message_and_target(): void
    {
        $this->seedRegion();
        $co = $this->makeCompany(['name' => 'PT Hubung']);
        $other = $this->makeCompany(['name' => 'PT Lain']);
        $user = User::find($co->user_id);

        $this->actingAs($user)->postJson("/company/talents/{$other->user_id}/contact", ['message' => 'Halo', 'type' => 'message'])->assertNotFound();
        $this->actingAs($user)->postJson('/company/talents/999/contact', ['message' => '', 'type' => 'message'])->assertUnprocessable();
        $this->assertSame(0, Notification::count());
    }
}
