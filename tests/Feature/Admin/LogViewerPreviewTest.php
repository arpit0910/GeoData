<?php
namespace Tests\Feature\Admin;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use App\Models\User;
class LogViewerPreviewTest extends TestCase {
 use RefreshDatabase;
 public function test_render_preview(): void {
  $path = storage_path('logs/log-viewer-preview.log');
  File::put($path, "[2026-10-29 00:00:00] production.INFO: Daily stock and mutual-fund master sync completed.\n[2026-10-29 03:31:02] production.WARNING: Token approval is pending. Dependent jobs were skipped.\n[2026-10-29 06:45:17] production.ERROR: Mutual-fund history download failed: upstream connection timed out.\n[stacktrace]\n#0 app/Services/MarketDataService.php(84): request()\n#1 app/Console/Commands/SyncMarketCommand.php(32): sync()\n[2026-10-29 09:16:03] production.ERROR: Quote synchronization failed: provider returned HTTP 503.\n[stacktrace]\n#0 app/Services/MarketDataService.php(124): fetchQuotes()\n#1 app/Console/Commands/SyncQuotesCommand.php(45): sync()\n");
  try {
   $response = $this->actingAs(User::factory()->create(['is_admin'=>true]))->get(route('admin.logs',['file'=>'log-viewer-preview.log']));
   $response->assertOk();
   File::put(storage_path('app/log-viewer-preview.html'), $response->getContent());
  } finally { File::delete($path); }
 }
}
