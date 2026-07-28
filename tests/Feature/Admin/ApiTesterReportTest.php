<?php

namespace Tests\Feature\Admin;

use App\Models\ApiLog;
use App\Models\ApiTestReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class ApiTesterReportTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestData;

    /** @test */
    public function admin_can_run_a_demo_report_for_a_company(): void
    {
        $admin = $this->createAdminUser();
        $this->createGeoHierarchy();
        $company = $this->createUser();

        $response = $this->actingAs($admin)->postJson(route('admin.api-tester.run'), [
            'target_user_id' => $company->id,
            'mode' => 'demo',
            'endpoints' => ['GET api/v1/countries'],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('report.company_email', $company->email);

        $this->assertDatabaseHas('api_test_reports', [
            'target_user_id' => $company->id,
            'generated_by_user_id' => $admin->id,
            'mode' => 'demo',
        ]);
    }

    /** @test */
    public function production_mode_is_read_only_and_still_generates_a_downloadable_report(): void
    {
        $admin = $this->createAdminUser();
        $this->createGeoHierarchy();
        $company = $this->createUser();
        $plan = $this->createPlan();
        $this->createActiveSubscription($company, $plan);
        $company->update(['plan_id' => $plan->id]);
        $startingApiLogCount = ApiLog::where('user_id', $company->id)->count();

        $runResponse = $this->actingAs($admin)->postJson(route('admin.api-tester.run'), [
            'target_user_id' => $company->id,
            'mode' => 'production',
            'endpoints' => ['GET api/v1/countries'],
        ]);

        $runResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.total', 1);

        $reportId = $runResponse->json('report.id');

        $this->assertSame($startingApiLogCount, ApiLog::where('user_id', $company->id)->count());

        $downloadResponse = $this->actingAs($admin)->get(route('admin.api-tester.reports.download', $reportId));

        $downloadResponse->assertOk();
        $downloadResponse->assertHeader('content-type', 'application/json');

        $report = ApiTestReport::findOrFail($reportId);
        $this->assertSame('production', $report->mode);
        $this->assertSame(1, $report->passed_endpoints);
        $this->assertSame(0, $report->failed_endpoints);
    }

    /** @test */
    public function admin_can_override_request_parameters_for_a_single_run(): void
    {
        $admin = $this->createAdminUser();
        $geo = $this->createGeoHierarchy();
        $company = $this->createUser([
            'country_id' => $geo['country']->id,
            'state_id' => $geo['state']->id,
            'city_id' => $geo['city']->id,
            'pincode' => $geo['pincode']->postal_code,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.api-tester.run'), [
            'target_user_id' => $company->id,
            'mode' => 'demo',
            'endpoints' => ['GET api/v1/countries'],
            'overrides' => [
                'GET api/v1/countries' => [
                    'path' => 'api/v1/countries',
                    'params' => [
                        'limit' => '1',
                        'iso2' => 'IN',
                    ],
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('results.0.request_path', '/api/v1/countries')
            ->assertJsonPath('results.0.request_params.limit', '1')
            ->assertJsonPath('results.0.request_params.iso2', 'IN');
    }

    /** @test */
    public function admin_can_generate_a_full_api_report_from_the_users_page(): void
    {
        $admin = $this->createAdminUser();
        $geo = $this->createGeoHierarchy();
        $company = $this->createUser([
            'country_id' => $geo['country']->id,
            'state_id' => $geo['state']->id,
            'city_id' => $geo['city']->id,
            'pincode' => $geo['pincode']->postal_code,
        ]);
        $plan = $this->createPlan();
        $this->createActiveSubscription($company, $plan);
        $company->update(['plan_id' => $plan->id]);

        $response = $this->actingAs($admin)->postJson(route('user.generate-api-report', $company), [
            'mode' => 'production',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('report.mode', 'production');

        $jsonDownloadUrl = $response->json('report.download_urls.json');
        $pdfDownloadUrl = $response->json('report.download_urls.pdf');
        $this->assertNotEmpty($jsonDownloadUrl);
        $this->assertNotEmpty($pdfDownloadUrl);

        $reportId = $response->json('report.id');
        $this->assertDatabaseHas('api_test_reports', [
            'id' => $reportId,
            'target_user_id' => $company->id,
        ]);

        $jsonDownloadResponse = $this->actingAs($admin)->get($jsonDownloadUrl);
        $jsonDownloadResponse->assertOk();
        $jsonDownloadResponse->assertHeader('content-disposition');
        $jsonDownloadResponse->assertHeader('content-type', 'application/json');

        $pdfDownloadResponse = $this->actingAs($admin)->get($pdfDownloadUrl);
        $pdfDownloadResponse->assertOk();
        $pdfDownloadResponse->assertHeader('content-disposition');
        $pdfDownloadResponse->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdfDownloadResponse->getContent());
    }

    /** @test */
    public function unsupported_routes_are_recorded_as_skipped_in_the_report(): void
    {
        $admin = $this->createAdminUser();
        $company = $this->createUser();

        $response = $this->actingAs($admin)->postJson(route('admin.api-tester.run'), [
            'target_user_id' => $company->id,
            'mode' => 'demo',
            'endpoints' => ['GET api/v1/state/{state}'],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('summary.passed', 0)
            ->assertJsonPath('summary.failed', 0)
            ->assertJsonPath('summary.skipped', 1)
            ->assertJsonPath('results.0.outcome', 'skipped');

        $reportId = $response->json('report.id');
        $report = ApiTestReport::findOrFail($reportId);

        $this->assertSame(1, $report->skipped_endpoints);
        $this->assertSame(0, $report->passed_endpoints);
        $this->assertSame(0, $report->failed_endpoints);
    }

    /** @test */
    public function production_mode_runs_post_endpoints_without_persisting_mutation(): void
    {
        $admin = $this->createAdminUser();
        $this->createGeoHierarchy();
        $company = $this->createUser();
        $plan = $this->createPlan();
        $this->createActiveSubscription($company, $plan);
        $company->update(['plan_id' => $plan->id]);
        $startingTokenCount = $company->tokens()->count();

        $response = $this->actingAs($admin)->postJson(route('admin.api-tester.run'), [
            'target_user_id' => $company->id,
            'mode' => 'production',
            'endpoints' => ['POST api/v1/auth/token'],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('summary.passed', 1)
            ->assertJsonPath('summary.failed', 0)
            ->assertJsonPath('summary.skipped', 0)
            ->assertJsonPath('results.0.outcome', 'passed');

        $this->assertSame($startingTokenCount, $company->fresh()->tokens()->count());
    }
}
