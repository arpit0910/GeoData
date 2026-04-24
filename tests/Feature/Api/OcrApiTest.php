<?php

namespace Tests\Feature\Api;

use App\Services\OcrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class OcrApiTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected array $authData;
    protected array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->authData = $this->createAuthenticatedApiUser();
        $this->headers = ['Authorization' => 'Bearer ' . $this->authData['token']];
    }

    /** @test */
    public function it_reports_ocr_health_status_from_the_microservice_bridge()
    {
        $mock = Mockery::mock(OcrService::class);
        $mock->shouldReceive('ping')->once()->andReturnTrue();
        $this->app->instance(OcrService::class, $mock);

        $response = $this->getJson('/api/v1/ocr/health');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'OCR service is online.',
            ]);
    }

    /** @test */
    public function it_forwards_ocr_requests_and_deducts_a_credit_on_success()
    {
        $file = UploadedFile::fake()->image('pan-card.jpg');

        $mock = Mockery::mock(OcrService::class);
        $mock->shouldReceive('extract')
            ->once()
            ->withArgs(function (UploadedFile $uploadedFile, ?string $documentType) use ($file) {
                return $uploadedFile->getClientOriginalName() === $file->getClientOriginalName()
                    && $documentType === 'pan';
            })
            ->andReturn([
                'success' => true,
                'message' => 'Values extracted successfully.',
                'document_type' => 'pan',
                'raw_text' => 'INCOME TAX DEPARTMENT',
                'extracted_fields' => [
                    'pan_number' => 'ABCDE1234F',
                    'name' => 'John Doe',
                ],
                'confidence' => 'high',
                'validation' => [
                    'is_valid' => true,
                    'missing_fields' => [],
                    'required_fields' => ['pan_number', 'name', 'father_name', 'dob'],
                    'raw_text_length' => 120,
                    'message' => 'Values extracted successfully.',
                ],
            ]);
        $this->app->instance(OcrService::class, $mock);

        $subscription = $this->authData['subscription'];
        $startingCredits = $subscription->available_credits;

        $response = $this->postJson('/api/v1/ocr/extract', [
            'image' => $file,
            'document_type' => 'pan',
        ], $this->headers);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Values extracted successfully.',
                'data' => [
                    'document_type' => 'pan',
                    'confidence' => 'high',
                ],
            ]);

        $subscription->refresh();
        $this->assertSame($startingCredits - 1, $subscription->available_credits);
        $this->assertDatabaseHas('api_logs', [
            'endpoint' => 'api/v1/ocr/extract',
            'status_code' => 200,
            'credit_deducted' => true,
        ]);
    }

    /** @test */
    public function it_returns_a_clear_message_when_the_image_is_blurry_or_incomplete()
    {
        $file = UploadedFile::fake()->image('blurry-pan.jpg');

        $mock = Mockery::mock(OcrService::class);
        $mock->shouldReceive('extract')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'We could not extract all required values. Please upload a clearer, properly aligned image. The current image may be blurry or incomplete.',
                'document_type' => 'pan',
                'raw_text' => 'ABCDE1234F',
                'extracted_fields' => [
                    'pan_number' => 'ABCDE1234F',
                    'name' => null,
                    'father_name' => null,
                    'dob' => null,
                ],
                'confidence' => 'low',
                'validation' => [
                    'is_valid' => false,
                    'missing_fields' => ['name', 'father_name', 'dob'],
                    'required_fields' => ['pan_number', 'name', 'father_name', 'dob'],
                    'raw_text_length' => 10,
                    'message' => 'We could not extract all required values. Please upload a clearer, properly aligned image. The current image may be blurry or incomplete.',
                ],
            ]);
        $this->app->instance(OcrService::class, $mock);

        $response = $this->postJson('/api/v1/ocr/extract', [
            'image' => $file,
            'document_type' => 'pan',
        ], $this->headers);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'We could not extract all required values. Please upload a clearer, properly aligned image. The current image may be blurry or incomplete.',
                'data' => [
                    'document_type' => 'pan',
                    'confidence' => 'low',
                    'validation' => [
                        'is_valid' => false,
                    ],
                ],
            ]);
    }
}
