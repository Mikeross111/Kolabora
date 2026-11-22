<?php

namespace Tests\Feature\Phase12;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Phase1\Concerns\BuildsPhase1Data;
use Tests\TestCase;

/**
 * kode QR dibuat di server (tanpa layanan luar) dan portofolio dapat diunduh sebagai PDF sungguhan.
 */
class PortfolioQrPdfTest extends TestCase
{
    use BuildsPhase1Data;
    use RefreshDatabase;

    public function test_profile_page_offers_qr_dialog_and_pdf_link_without_external_requests(): void
    {
        $this->seedRegion();
        $student = $this->makeStudent('Alya', 'Putri');

        $html = $this->actingAs($student->user)->get(route('student.profile.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-kb-open="kb-qr"', $html);
        $this->assertStringContainsString(route('student.profile.export-pdf'), $html);
        $this->assertMatchesRegularExpression('/<dialog id="kb-qr".*?<img src="data:image\/svg\+xml;base64,/s', $html);
        $this->assertStringNotContainsString('api.qrserver.com', $html);
    }

    public function test_qr_endpoint_returns_a_local_image_for_the_public_profile_url(): void
    {
        $this->seedRegion();
        $student = $this->makeStudent('Bima', 'Pratama');

        $json = $this->actingAs($student->user)->getJson(route('student.profile.qr-code'))->assertOk()->json();

        $this->assertTrue($json['success']);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $json['qr_url']);
        $this->assertStringContainsString('/profile/', $json['profile_url']);
    }

    public function test_pdf_export_returns_a_real_pdf_file(): void
    {
        $this->seedRegion();
        $student = $this->makeStudent('Citra', 'Lestari');

        $response = $this->actingAs($student->user)->get(route('student.profile.export-pdf'))->assertOk();

        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }
}
